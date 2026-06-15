<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: application/json; charset=utf-8');

set_error_handler(function ($severity, $msg, $file, $line) {
    throw new ErrorException($msg, 0, $severity, $file, $line);
});
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'msg' => '重大錯誤: ' . $e['message'], 'file' => $e['file'], 'line' => $e['line']]);
    }
});

require_once __DIR__ . '/config.php';

// ====== GoogleSheetsClient ======
class GoogleSheetsClient
{
    private $ssId;
    private $accessToken = null;
    private $tokenExpires = 0;
    private $sheetIdCache = [];
    private $sheetPropsCache = [];
    private $sheetValueCache = [];

    public function __construct($ssId = null)
    {
        $this->ssId = $ssId ?: SS_ID_MAIN;
    }

    public function readSheet($sheetName)
    {
        if (isset($this->sheetValueCache[$sheetName])) {
            return $this->sheetValueCache[$sheetName];
        }
        $range = urlencode("'{$sheetName}'!A:ZZ");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}";
        $res = $this->api('GET', $url);
        $rows = isset($res['values']) ? $res['values'] : [];
        $this->sheetValueCache[$sheetName] = $rows;
        return $rows;
    }

    public function writeRows($sheetName, $startRow, $rows)
    {
        $this->ensureSheetCapacity($sheetName, $startRow + count($rows) - 1, $this->getMaxColumnCount($rows));
        $range = urlencode("'{$sheetName}'!A{$startRow}");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}?valueInputOption=USER_ENTERED";
        $this->api('PUT', $url, [
            'values' => $rows,
            'majorDimension' => 'ROWS'
        ]);
        unset($this->sheetValueCache[$sheetName]);
    }

    public function appendRows($sheetName, $rows)
    {
        $range = urlencode("'{$sheetName}'!A:A");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}:append?valueInputOption=USER_ENTERED";
        $this->api('POST', $url, [
            'values' => $rows,
            'majorDimension' => 'ROWS'
        ]);
        unset($this->sheetValueCache[$sheetName]);
    }

    public function clearSheet($sheetName)
    {
        $range = urlencode("'{$sheetName}'!A:ZZ");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}:clear";
        $this->api('POST', $url, (object)[]);
        unset($this->sheetValueCache[$sheetName]);
    }

    public function formatSalesYearCacheSheet($sheetName)
    {
        $sheetId = $this->getSheetIdByName($sheetName);
        $requests = [
            $this->buildFormatRequest($sheetId, 0, 1, 'NUMBER', '0'),
            $this->buildFormatRequest($sheetId, 1, 2, 'NUMBER', '0'),
            $this->buildFormatRequest($sheetId, 2, 6, 'TEXT', '@'),
            $this->buildFormatRequest($sheetId, 6, 8, 'NUMBER', '0.00'),
            $this->buildFormatRequest($sheetId, 8, 9, 'NUMBER', '0'),
            $this->buildFormatRequest($sheetId, 9, 10, 'NUMBER', '0'),
            $this->buildFormatRequest($sheetId, 10, 12, 'NUMBER', '0.00'),
            $this->buildFormatRequest($sheetId, 12, 13, 'DATE', 'yyyy/mm/dd'),
            $this->buildFormatRequest($sheetId, 13, 14, 'TEXT', '@'),
            $this->buildFormatRequest($sheetId, 14, 15, 'DATE_TIME', 'yyyy/mm/dd hh:mm:ss'),
            $this->buildFormatRequest($sheetId, 15, 16, 'TEXT', '@')
        ];
        $this->batchUpdate(['requests' => $requests]);
    }


    public function updateCell($sheetName, $row, $col, $value)
    {
        $colLetter = $this->colIndexToLetter($col);
        $range = urlencode("'{$sheetName}'!{$colLetter}{$row}");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}?valueInputOption=USER_ENTERED";
        $this->api('PUT', $url, [
            'values' => [[$value]],
            'majorDimension' => 'ROWS'
        ]);
        unset($this->sheetValueCache[$sheetName]);
    }

    public function updateRowRange($sheetName, $row, $colStart, $values)
    {
        $colLetter = $this->colIndexToLetter($colStart);
        $colEnd = $this->colIndexToLetter($colStart + count($values) - 1);
        $range = urlencode("'{$sheetName}'!{$colLetter}{$row}:{$colEnd}{$row}");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}?valueInputOption=USER_ENTERED";
        $this->api('PUT', $url, [
            'values' => [$values],
            'majorDimension' => 'ROWS'
        ]);
        unset($this->sheetValueCache[$sheetName]);
    }

    public function writeBlock($sheetName, $startRow, $colStart, $rows)
    {
        if (!$rows || !count($rows)) return;
        $this->ensureSheetCapacity($sheetName, $startRow + count($rows) - 1, $colStart + $this->getMaxColumnCount($rows));
        $colLetter = $this->colIndexToLetter($colStart);
        $range = urlencode("'{$sheetName}'!{$colLetter}{$startRow}");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}?valueInputOption=USER_ENTERED";
        $this->api('PUT', $url, [
            'values' => $rows,
            'majorDimension' => 'ROWS'
        ]);
        unset($this->sheetValueCache[$sheetName]);
    }

    private function colIndexToLetter($i)
    {
        $letters = '';
        while ($i >= 0) {
            $letters = chr(65 + ($i % 26)) . $letters;
            $i = (int)($i / 26) - 1;
        }
        return $letters;
    }

    private function getAccessToken()
    {
        if ($this->accessToken && microtime(true) < $this->tokenExpires) {
            return $this->accessToken;
        }

        $sa = json_decode(file_get_contents(SERVICE_ACCOUNT_FILE), true);
        $now = time();

        $header = self::base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = self::base64url(json_encode([
            'iss'   => $sa['client_email'],
            'sub'   => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now
        ]));

        $signature = '';
        openssl_sign("{$header}.{$payload}", $signature, $sa['private_key'], 'sha256WithRSAEncryption');
        $jwt = "{$header}.{$payload}." . self::base64url($signature);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new RuntimeException("Google Auth 失敗 (HTTP {$httpCode}): {$resp}");
        }

        $data = json_decode($resp, true);
        $this->accessToken = $data['access_token'];
        $this->tokenExpires = microtime(true) + ($data['expires_in'] ?? 3600) - 60;
        return $this->accessToken;
    }

    private function api($method, $url, $body = null)
    {
        $token = $this->getAccessToken();
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ]
        ];
        if ($method === 'GET') {
            $opts[CURLOPT_HTTPGET] = true;
        } else {
            $opts[CURLOPT_CUSTOMREQUEST] = $method;
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new RuntimeException("Google Sheets API 錯誤 (HTTP {$httpCode}): {$resp}");
        }

        $result = json_decode($resp, true);
        return $result ?: [];
    }

    public function addSheetPublic($title)
    {
        return $this->batchUpdate(['requests' => [['addSheet' => ['properties' => ['title' => $title]]]]]);
    }

    private function batchUpdate($body)
    {
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}:batchUpdate";
        return $this->api('POST', $url, $body);
    }

    private function getSheetIdByName($sheetName)
    {
        $props = $this->getSheetPropertiesByName($sheetName);
        return $props['sheetId'];
    }

    private function getSheetPropertiesByName($sheetName)
    {
        if (isset($this->sheetPropsCache[$sheetName])) return $this->sheetPropsCache[$sheetName];

        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}?fields=sheets(properties(sheetId,title,gridProperties(rowCount,columnCount)))";
        $res = $this->api('GET', $url);
        foreach (($res['sheets'] ?? []) as $sheet) {
            $props = $sheet['properties'] ?? [];
            if (($props['title'] ?? '') === $sheetName) {
                $normalized = [
                    'sheetId' => (int)($props['sheetId'] ?? 0),
                    'rowCount' => (int)($props['gridProperties']['rowCount'] ?? 0),
                    'columnCount' => (int)($props['gridProperties']['columnCount'] ?? 0)
                ];
                $this->sheetIdCache[$sheetName] = $normalized['sheetId'];
                $this->sheetPropsCache[$sheetName] = $normalized;
                return $normalized;
            }
        }

        throw new RuntimeException("找不到工作表 ID: {$sheetName}");
    }

    private function ensureSheetCapacity($sheetName, $requiredRows, $requiredCols)
    {
        $props = $this->getSheetPropertiesByName($sheetName);
        $requests = [];

        if ($requiredRows > $props['rowCount']) {
            $requests[] = [
                'updateSheetProperties' => [
                    'properties' => [
                        'sheetId' => $props['sheetId'],
                        'gridProperties' => [
                            'rowCount' => $requiredRows
                        ]
                    ],
                    'fields' => 'gridProperties.rowCount'
                ]
            ];
            $props['rowCount'] = $requiredRows;
        }

        if ($requiredCols > $props['columnCount']) {
            $requests[] = [
                'updateSheetProperties' => [
                    'properties' => [
                        'sheetId' => $props['sheetId'],
                        'gridProperties' => [
                            'columnCount' => $requiredCols
                        ]
                    ],
                    'fields' => 'gridProperties.columnCount'
                ]
            ];
            $props['columnCount'] = $requiredCols;
        }

        if ($requests) {
            $this->batchUpdate(['requests' => $requests]);
            $this->sheetPropsCache[$sheetName] = $props;
        }
    }

    private function getMaxColumnCount($rows)
    {
        $max = 0;
        foreach ((array)$rows as $row) {
            if (is_array($row)) $max = max($max, count($row));
        }
        return $max;
    }

    private function buildFormatRequest($sheetId, $startCol, $endCol, $type, $pattern)
    {
        return [
            'repeatCell' => [
                'range' => [
                    'sheetId' => $sheetId,
                    'startRowIndex' => 1,
                    'startColumnIndex' => $startCol,
                    'endColumnIndex' => $endCol
                ],
                'cell' => [
                    'userEnteredFormat' => [
                        'numberFormat' => [
                            'type' => $type,
                            'pattern' => $pattern
                        ]
                    ]
                ],
                'fields' => 'userEnteredFormat.numberFormat'
            ]
        ];
    }

    private static function base64url($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

// ====== SleeperService ======
class SleeperService
{
    private static $salesMerge = ['薛佶姈' => '高弘治'];
    private static $customerSuffixes = [
        '企業股份有限公司', '開發有限公司', '股份有限公司', '有限公司',
        '企業', '建材', '國際', '磁磚', '磁藝', '工程', '設計部', '出貨',
        '保留收訂', '公司', '實業', '精品', '工作室', '開發', '室內裝修',
        '室內設計', '設計', '行銷', '商行', '建業', '綜合'
    ];
    private static $logisticsAddressKeywords = [
        '貨運', '物流', '運輸', '托運', '倉庫', '貨櫃', '轉運', '集運', '加工',
        '倉儲', '車隊', '站所', '物流中心', '轉運站'
    ];

    private $gs;

    public function __construct($gs)
    {
        $this->gs = $gs;
    }

    private function normalizeCustomerName($name)
    {
        return preg_replace('/[()（）【】\[\]「」『』,，.。\/／:：\s]+/u', '', trim((string)$name));
    }

    private function displayCustomerName($name)
    {
        $s = $this->normalizeCustomerName($name);
        if ($s === '') return '未知客戶';
        if (mb_strpos($s, '漢樺') !== false || mb_strpos($s, '波爾泰') !== false) return '漢樺';
        if (mb_strpos($s, '大永') !== false || mb_strpos($s, '新大永') !== false) return '大永';
        if (mb_strpos($s, '伊特') !== false || mb_strpos($s, '喬弈') !== false || mb_strpos($s, '喬翌') !== false) return '伊特';
        if (mb_strpos($s, '鏷城') !== false || mb_strpos($s, '璞城') !== false) return '鏷城';
        if (mb_strpos($s, '鼎康') !== false || mb_strpos($s, '鼎晨') !== false) return '鼎晨';
        if (mb_strpos($s, '今冠') !== false || mb_strpos($s, '金冠') !== false) return '金冠';
        if (mb_strpos($s, '東春') !== false || mb_strpos($s, '滿財') !== false) return '東春';
        if (mb_strpos($s, '德思特尼') !== false || mb_strpos($s, '德思') !== false) return '德思特尼';

        $parts = preg_split('/[-－—]/u', $s);
        $s = $parts[0] ?? $s;
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach (self::$customerSuffixes as $suffix) {
                $pos = mb_strpos($s, $suffix);
                if ($pos !== false && $pos > 0) {
                    $s = mb_substr($s, 0, $pos);
                    $changed = true;
                    break;
                }
            }
        }
        return mb_substr($s, 0, 4);
    }

    private function normalizeAddress($address)
    {
        return preg_replace('/[\s()（）【】\[\]「」『』,，.。\/／:：\-－—]+/u', '', trim((string)$address));
    }

    private function normalizeSizeLabel($size)
    {
        $size = strtoupper(trim((string)$size));
        $size = str_replace(['CM', '㎝', '＊', '*', 'Ｘ', '×', 'x'], ['', '', 'X', 'X', 'X', 'X', 'X'], $size);
        $size = preg_replace('/\s+/u', '', $size);
        $size = preg_replace('/(?<=\d)X(?=\d)/u', 'X', $size);
        if ($size === '') return '未標尺寸';
        return $size;
    }

    private function normalizeContractHealthLabel($health)
    {
        $health = trim((string)$health);
        if ($health === '') return ['bucket' => '未分類', 'note' => ''];
        if (mb_strpos($health, '嚴重') !== false) return ['bucket' => '嚴重', 'note' => ''];
        if (mb_strpos($health, '逾期') !== false) return ['bucket' => '逾期', 'note' => ''];
        if (mb_strpos($health, '待續') !== false) return ['bucket' => '待續', 'note' => ''];
        if (mb_strpos($health, '已續') !== false) return ['bucket' => '已續', 'note' => ''];
        if (mb_strpos($health, '正常') !== false) return ['bucket' => '正常', 'note' => ''];
        if (mb_strpos($health, '未續約') !== false) return ['bucket' => '其它未續約', 'note' => $health];
        return ['bucket' => '未分類', 'note' => $health];
    }

    private function isLogisticsAddress($address)
    {
        $address = trim((string)$address);
        if ($address === '') return false;
        foreach (self::$logisticsAddressKeywords as $keyword) {
            if (mb_strpos($address, $keyword) !== false) return true;
        }
        return false;
    }

    private function shortProjectName($customer, $projectRaw, $address)
    {
        $projectRaw = trim((string)$projectRaw);
        $address = trim((string)$address);
        if ($projectRaw !== '') return mb_substr($projectRaw, 0, 24, 'UTF-8');
        if ($address !== '') return $this->displayCustomerName($customer) . '｜' . mb_substr($address, 0, 18, 'UTF-8');
        return $this->displayCustomerName($customer) . '｜專案';
    }

    private function peakShavedAverage($values)
    {
        $vals = array_values(array_filter(array_map('floatval', (array)$values), function ($v) {
            return $v > 0;
        }));
        if (!count($vals)) return 0;
        sort($vals, SORT_NUMERIC);
        if (count($vals) >= 6) {
            array_shift($vals);
            if (count($vals) > 2) array_pop($vals);
            if (count($vals) > 2) array_pop($vals);
        }
        if (!count($vals)) return 0;
        return array_sum($vals) / count($vals);
    }

    private function matchesStrategyPeriod($rowYear, $rowMonth, $periodMeta)
    {
        return (int)$rowYear === (int)$periodMeta['year'] && in_array((int)$rowMonth, $periodMeta['months'], true);
    }

    private function truncateReport($n)
    {
        $n = (float)$n;
        return $n < 0 ? ceil($n) : floor($n);
    }

    private function safeRatio($num, $den)
    {
        $den = (float)$den;
        if ($den == 0.0) return 0;
        return (float)$num / $den;
    }

    private function calcPearson($pairs)
    {
        $n = count($pairs);
        if ($n < 2) return 0;
        $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0; $sumY2 = 0;
        foreach ($pairs as $pair) {
            $x = (float)($pair['x'] ?? 0);
            $y = (float)($pair['y'] ?? 0);
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
            $sumY2 += $y * $y;
        }
        $num = ($n * $sumXY) - ($sumX * $sumY);
        $den = sqrt((($n * $sumX2) - ($sumX * $sumX)) * (($n * $sumY2) - ($sumY * $sumY)));
        if ($den <= 0) return 0;
        return $num / $den;
    }

    private function classifyTaskLabel($text)
    {
        $text = trim((string)$text);
        if ($text === '') return '其他';
        $rules = [
            '送樣' => ['送樣'],
            '送貨' => ['送貨', '配送完工', '出貨'],
            '版面' => ['版面上架', '樣品結案', '版面巡視'],
            '追蹤' => ['案件追蹤', '業務更新', '聊天', '拜訪', '收款', '開會'],
            '回公司' => ['回公司', '公司()'],
            '退貨' => ['退貨']
        ];
        foreach ($rules as $label => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($text, $kw) !== false) return $label;
            }
        }
        return '其他';
    }

    private function parseWorkLogCustomers($customerText)
    {
        $text = trim((string)$customerText);
        if ($text === '') return [];

        $parts = preg_split('/\|\|/u', $text);
        $results = [];
        foreach ($parts as $part) {
            $part = preg_replace('/\[[^\]]*\]/u', '', trim((string)$part));
            $part = trim((string)$part);
            if ($part === '') continue;
            $part = preg_split('/[()（）]/u', $part)[0] ?? $part;
            $part = preg_split('/[-－—]/u', $part)[0] ?? $part;
            $part = trim((string)$part);
            if ($part === '') continue;
            if (preg_match('/^(公司|回公司|測試|內湖|台中|台南|高雄)$/u', $part)) continue;
            $norm = $this->displayCustomerName($part);
            if ($norm === '' || $norm === '未知客戶') continue;
            $results[$norm] = $norm;
        }
        return array_values($results);
    }

    public function scanProjectFlags()
    {
        $rows = $this->gs->readSheet(SALES_SHEET);
        if (count($rows) < 2) return ['success' => false, 'msg' => '經銷銷售報表無資料'];

        $headers = $rows[0];
        $idx = [
            'date' => $this->findHeader($headers, ['單據日期', '銷貨日期', '日期']),
            'customer' => $this->findHeader($headers, ['客戶名稱', '客戶']),
            'custCode' => $this->findHeader($headers, ['客戶編號', '客戶代碼', '代碼']),
            'code' => $this->findHeader($headers, ['產品編號', '編號', '品號']),
            'qty' => $this->findHeader($headers, ['數量', '銷貨數量', '片數']),
            'amount' => $this->findHeader($headers, ['金額', '銷貨金額', '銷額']),
            'address' => $this->findHeader($headers, ['送貨地址', '地址', '送貨地點']),
            'project' => $this->findHeader($headers, ['案名', '專案', '工地名稱', '工地']),
            'note' => $this->findHeader($headers, ['備註', '說明']),
            'suspect' => $this->findHeader($headers, ['疑似專案']),
            'projectName' => $this->findHeader($headers, ['專案名稱']),
            'projectType' => $this->findHeader($headers, ['專案類型']),
            'exclude' => $this->findHeader($headers, ['是否排除常態', '是否排除常態分析']),
            'reason' => $this->findHeader($headers, ['專案判定說明']),
            'confirm' => $this->findHeader($headers, ['人工確認']),
            'updatedAt' => $this->findHeader($headers, ['最後更新時間'])
        ];

        foreach (['date', 'customer', 'code', 'qty', 'amount', 'suspect', 'projectName', 'projectType', 'exclude', 'reason', 'confirm', 'updatedAt'] as $key) {
            if ($idx[$key] === -1) {
                return ['success' => false, 'msg' => '經銷銷售報表缺少欄位：' . $key];
            }
        }

        $metaMap = $this->getMetaMap();
        $groups = [];
        $customerMonthlyTotals = [];
        $rowMeta = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $d = $this->parseDate($this->getVal($row, $idx['date']));
            if (!$d) continue;

            $custName = trim($this->getVal($row, $idx['customer']));
            $custCode = $idx['custCode'] !== -1 ? trim($this->getVal($row, $idx['custCode'])) : '';
            $projectRaw = $idx['project'] !== -1 ? trim($this->getVal($row, $idx['project'])) : '';
            $note = $idx['note'] !== -1 ? trim($this->getVal($row, $idx['note'])) : '';
            $amount = $this->optFloat($this->getVal($row, $idx['amount']));
            if ($this->isSampleRow($custCode, $custName, $projectRaw . ' ' . $note, $amount)) continue;

            $qty = $this->optFloat($this->getVal($row, $idx['qty']));
            if ($qty <= 0 || $amount <= 0) continue;

            $sku = $this->cleanSku($this->getVal($row, $idx['code']));
            if ($sku === '') continue;

            $customer = $this->displayCustomerName($custName);
            $yearMonth = $d->format('Y-m');
            if (!isset($customerMonthlyTotals[$customer])) $customerMonthlyTotals[$customer] = [];
            if (!isset($customerMonthlyTotals[$customer][$yearMonth])) $customerMonthlyTotals[$customer][$yearMonth] = 0;
            $customerMonthlyTotals[$customer][$yearMonth] += $amount;

            $address = $idx['address'] !== -1 ? trim($this->getVal($row, $idx['address'])) : '';
            $isLogisticsAddress = $this->isLogisticsAddress($address);
            $addressKey = $this->normalizeAddress($address);
            $projectKey = $this->normalizeCustomerName($projectRaw);
            $entityKey = $projectKey !== '' ? $projectKey : ($isLogisticsAddress ? '' : $addressKey);
            if ($entityKey === '') continue;

            $perPing = isset($metaMap[$sku]) ? ($metaMap[$sku]['perPing'] ?: 36) : 36;
            $pings = abs($qty) / $perPing;
            $groupKey = $yearMonth . '|' . $customer . '|' . $entityKey;

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'customer' => $customer,
                    'yearMonth' => $yearMonth,
                    'address' => $address,
                    'isLogisticsAddress' => $isLogisticsAddress,
                    'projectRaw' => $projectRaw,
                    'rows' => [],
                    'amount' => 0,
                    'pings' => 0,
                    'skuMap' => []
                ];
            }
            $groups[$groupKey]['rows'][] = $i;
            $groups[$groupKey]['amount'] += $amount;
            $groups[$groupKey]['pings'] += $pings;
            $groups[$groupKey]['skuMap'][$sku] = true;

            $rowMeta[$i] = ['customer' => $customer, 'address' => $address, 'projectRaw' => $projectRaw];
        }

        $customerBaselines = [];
        foreach ($customerMonthlyTotals as $customer => $monthMap) {
            $customerBaselines[$customer] = $this->peakShavedAverage(array_values($monthMap));
        }

        $flaggedRows = [];
        $flaggedGroups = 0;
        foreach ($groups as $group) {
            $rowCount = count($group['rows']);
            $skuCount = count($group['skuMap']);
            $baseline = isset($customerBaselines[$group['customer']]) ? $customerBaselines[$group['customer']] : 0;
            $reasons = [];
            $score = 0;

            if (!$group['isLogisticsAddress'] && $group['address'] !== '' && $rowCount >= 2) {
                $reasons[] = '同地址集中出貨';
                $score += 2;
            }
            if ($group['projectRaw'] !== '') {
                $reasons[] = '含案名/工地欄位';
                $score += 1;
            }
            if ($group['isLogisticsAddress']) {
                $reasons[] = '貨運/物流/加工地址降權';
                $score -= 3;
            }
            if ($group['amount'] >= 1000000) {
                $reasons[] = '單月金額破 100 萬';
                $score += 3;
            } elseif ($group['amount'] >= 500000) {
                $reasons[] = '單月金額破 50 萬';
                $score += 2;
            }
            if ($group['pings'] >= 50) {
                $reasons[] = '單月坪數破 50 坪';
                $score += 3;
            } elseif ($group['pings'] >= 20) {
                $reasons[] = '單月坪數破 20 坪';
                $score += 2;
            }
            if ($skuCount >= 3 && $group['amount'] >= 300000) {
                $reasons[] = '同址多 SKU 集中';
                $score += 1;
            }
            if ($baseline > 0 && $group['amount'] >= max($baseline * 2.2, $baseline + 300000)) {
                $reasons[] = '高於客戶去峰值常態月銷';
                $score += 2;
            }

            if ($score < 3) continue;
            $flaggedGroups++;
            $suggestedType = (!$group['isLogisticsAddress'] && $group['address'] !== '' && $group['amount'] >= 800000 && $rowCount <= 4) ? '直送工地' : '現貨出庫';
            $suggestedExclude = $suggestedType === '直送工地' ? '是' : '否';
            $suggestedName = $this->shortProjectName($group['customer'], $group['projectRaw'], $group['address']);
            $cleanReasons = array_values(array_filter(array_unique($reasons), function ($reason) {
                return $reason !== '貨運/物流/加工地址降權';
            }));
            $reasonText = implode('、', $cleanReasons) . '｜' . $group['yearMonth'] . '｜' . (int)floor($group['amount'] / 10000) . '萬';

            foreach ($group['rows'] as $rowIndex) {
                $flaggedRows[$rowIndex] = [
                    'suspect' => '是',
                    'projectName' => $suggestedName,
                    'projectType' => $suggestedType,
                    'exclude' => $suggestedExclude,
                    'reason' => $reasonText
                ];
            }
        }

        $updatedAt = date('Y/m/d H:i:s');
        $startCol = $idx['suspect'];
        $writeRows = [];
        $chunkStartRow = 2;
        $written = 0;

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $existingProjectName = trim($this->getVal($row, $idx['projectName']));
            $existingProjectType = trim($this->getVal($row, $idx['projectType']));
            $existingExclude = trim($this->getVal($row, $idx['exclude']));
            $existingConfirm = trim($this->getVal($row, $idx['confirm']));
            $existingReason = trim($this->getVal($row, $idx['reason']));
            $hasManual = $existingConfirm !== '' || $existingProjectName !== '' || $existingProjectType !== '' || $existingExclude !== '';

            $flag = isset($flaggedRows[$i]) ? $flaggedRows[$i] : null;
            if ($flag) {
                $values = [
                    '是',
                    $existingProjectName !== '' ? $existingProjectName : $flag['projectName'],
                    $existingProjectType !== '' ? $existingProjectType : $flag['projectType'],
                    $existingExclude !== '' ? $existingExclude : $flag['exclude'],
                    $flag['reason'],
                    $existingConfirm !== '' ? $existingConfirm : '待確認',
                    $updatedAt
                ];
            } elseif ($hasManual) {
                $values = [
                    trim($this->getVal($row, $idx['suspect'])) ?: '否',
                    $existingProjectName,
                    $existingProjectType,
                    $existingExclude,
                    $existingReason,
                    $existingConfirm,
                    trim($this->getVal($row, $idx['updatedAt']))
                ];
            } else {
                $values = ['否', '', '', '', '', '', ''];
            }

            $writeRows[] = $values;
            if (count($writeRows) >= 500) {
                $this->gs->writeBlock(SALES_SHEET, $chunkStartRow, $startCol, $writeRows);
                $written += count($writeRows);
                $chunkStartRow += count($writeRows);
                $writeRows = [];
            }
        }
        if (count($writeRows)) {
            $this->gs->writeBlock(SALES_SHEET, $chunkStartRow, $startCol, $writeRows);
            $written += count($writeRows);
        }

        return [
            'success' => true,
            'scannedRows' => max(0, count($rows) - 1),
            'suspectedGroups' => $flaggedGroups,
            'suspectedRows' => count($flaggedRows),
            'writtenRows' => $written,
            'updatedAt' => $updatedAt
        ];
    }

    public function getSleeperConfig()
    {
        $data = $this->gs->readSheet(SLEEPER_SHEET);
        if (count($data) < 2) return ['success' => false, 'msg' => '睡美人工作表無資料'];

        $h = $data[0];
        $idx = [
            'grade' => $this->findHeader($h, ['等級','級別']),
            'sku'   => $this->findHeader($h, ['編號','產品編號','品號']),
            'cost'  => $this->findHeader($h, ['成本','單片成本','成本價'])
        ];
        if ($idx['grade'] === -1 || $idx['sku'] === -1) {
            return ['success' => false, 'msg' => '睡美人工作表缺少「等級」或「編號」欄位'];
        }

        $map = [];
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            $sku = $this->cleanSku($this->getVal($row, $idx['sku']));
            if (!$sku) continue;
            $map[$sku] = [
                'grade' => strtoupper(trim($this->getVal($row, $idx['grade']))),
                'cost'  => $this->optFloat($idx['cost'] !== -1 ? $this->getVal($row, $idx['cost']) : 0)
            ];
        }

        $priceData = $this->gs->readSheet(PRICE_SHEET);
        if (count($priceData) > 1) {
            $pH = $priceData[0];
            $pCode = $this->findHeader($pH, ['編號','產品編號']);
            $pSleeper = $this->findHeader($pH, ['睡美人']);
            $pCost = $this->findHeader($pH, ['成本','單片成本','成本價']);
            $pSeries = $this->findHeader($pH, ['中文系列','系列']);
            if ($pCode !== -1 && $pSleeper !== -1) {
                // Overlay: price sheet cost takes priority over sleeper sheet cost
                $priceCosts = [];
                for ($pi = 0; $pi < count($priceData); $pi++) {
                    if ($pi === 0) continue;
                    $rsku = $this->cleanSku($this->getVal($priceData[$pi], $pCode));
                    if (!$rsku) continue;
                    $priceCosts[$rsku] = $pCost !== -1 ? $this->optFloat($this->getVal($priceData[$pi], $pCost)) : 0;
                }
                foreach ($map as $sku => &$m) {
                    if (isset($priceCosts[$sku]) && $priceCosts[$sku] > 0) {
                        $m['cost'] = $priceCosts[$sku];
                    }
                }
                unset($m);
                // Add products marked 睡美人 in price sheet but not in sleeper sheet
                for ($i = 1; $i < count($priceData); $i++) {
                    $row = $priceData[$i];
                    $sku = $this->cleanSku($this->getVal($row, $pCode));
                    if (!$sku) continue;
                    $mark = trim($this->getVal($row, $pSleeper));
                    if ($mark !== '' && !isset($map[$sku])) {
                        $map[$sku] = [
                            'grade' => 'S',
                            'cost'  => $pCost !== -1 ? $this->optFloat($this->getVal($row, $pCost)) : 0
                        ];
                    }
                }
            }
        }

        return ['success' => true, 'data' => $map];
    }

    public function calcMultiplier($grade, $margin, $priceCostRatio, $isFullClearance)
    {
        $g = strtoupper(trim($grade));

        if ($g === 'XXX') {
            if ($priceCostRatio >= 0.7) return 3;
            if ($priceCostRatio >= 0.5) return 2;
            return 1;
        }
        if ($g === 'S') {
            if ($margin > 0.15) return 3;
            if ($priceCostRatio >= 0.85) return 2;
            if ($isFullClearance) return 2;
            return 1;
        }
        if ($g === 'A') {
            if ($margin > 0.15) return 2;
            if ($isFullClearance) return 2.5;
            if ($margin >= -0.05) return 1.5;
            return 1;
        }
        if ($g === 'B') {
            if ($margin > 0.30) return 2;
            return 1;
        }
        return 1;
    }

    public function getSeriesMap()
    {
        $data = $this->gs->readSheet(PRICE_SHEET);
        if (count($data) < 2) return [];

        $h = $data[0];
        $idxSku = $this->findHeader($h, ['編號','產品編號']);
        if ($idxSku === -1) return [];

        $idxSeriesCn = $this->findHeader($h, ['中文系列','系列中文','中文名稱']);
        $idxSeriesEn = $this->findHeader($h, ['系列','英文系列']);

        $map = [];
        for ($i = 1; $i < count($data); $i++) {
            $sku = $this->cleanSku($this->getVal($data[$i], $idxSku));
            if (!$sku) continue;
            $map[$sku] = trim($this->getVal($data[$i], $idxSeriesCn !== -1 ? $idxSeriesCn : $idxSeriesEn)) ?: '一般';
        }
        return $map;
    }

    public function getStockMap()
    {
        $data = $this->gs->readSheet(STOCK_SHEET);
        if (count($data) < 2) return [];

        $h = $data[0];
        $idxCode = $this->findHeader($h, ['編號','產品編號']);
        $idxStock = $this->findHeader($h, ['庫存坪數','坪數','庫存']);
        if ($idxCode === -1) return [];

        $map = [];
        for ($i = 1; $i < count($data); $i++) {
            $sku = $this->cleanSku($this->getVal($data[$i], $idxCode));
            if (!$sku) continue;
            $ping = $idxStock !== -1 ? $this->optFloat($this->getVal($data[$i], $idxStock)) : 0;
            $map[$sku] = isset($map[$sku]) ? $map[$sku] + $ping : $ping;
        }
        return $map;
    }

    public function getInventorySummary()
    {
        $sleeperCostMap = $this->getSleeperCostMap();
        $priceCostMap = $this->getPriceCostMap();
        $stockMap = $this->getStockMap();
        $metaMap = $this->getMetaMap();

        $totalCost = 0;
        $totalPing = 0;
        foreach ($stockMap as $sku => $stockPing) {
            $cost = $sleeperCostMap[$sku] ?? $priceCostMap[$sku] ?? null;
            if ($cost === null) continue;
            $meta = $metaMap[$sku] ?? ['perPing' => 36];
            $costPerPing = (float)$cost * ($meta['perPing'] ?: 36);
            $totalCost += $stockPing * $costPerPing;
            $totalPing += $stockPing;
        }
        return ['totalCost' => round($totalCost), 'totalPing' => round($totalPing, 1)];
    }

    public function getProductRestockAdvisor($tab, $forceRefresh = false)
    {
        $tab = in_array($tab, ['sleeper', 'normal', 'discontinued']) ? $tab : 'sleeper';
        if (!is_dir(AI_ADVISOR_CACHE_DIR)) {
            @mkdir(AI_ADVISOR_CACHE_DIR, 0775, true);
        }
        $cacheFile = AI_ADVISOR_CACHE_DIR . "/restock_{$tab}.json";

        if (!$forceRefresh && is_file($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached) {
                return ['success' => true, 'cached' => true, 'data' => $cached];
            }
        }

        if ($tab === 'sleeper') $res = $this->getSleeperProductOverview();
        elseif ($tab === 'normal') $res = $this->getNormalProductOverview();
        else $res = $this->getDiscontinuedProductOverview();
        if (!($res['success'] ?? false)) return $res;
        $products = $res['data'] ?? [];

        $this->recordProductHistory($tab, $products);

        if ($tab !== 'normal') {
            return ['success' => false, 'msg' => '睡美人/不續辦商品不進行補貨，故無補貨建議'];
        }

        if (GEMINI_API_KEY === '') {
            return ['success' => false, 'msg' => '尚未設定 GEMINI_API_KEY'];
        }

        $items = [];
        foreach ($products as $p) {
            if (($p['mosLevel'] ?? 0) >= 1 || ($p['stockPing'] ?? 0) <= ($p['monthlySpeedPings'] ?? 0) * 1.5) {
                $items[] = [
                    'sku' => $p['sku'],
                    'series' => $p['series'] ?? '',
                    'stockPing' => $p['stockPing'] ?? 0,
                    'monthlySpeedPings' => $p['monthlySpeedPings'] ?? 0,
                    'mos' => $p['mos'] ?? 0,
                    'daysSinceLastSale' => $p['daysSinceLastSale'],
                    'totalPings' => $p['totalPings'] ?? 0,
                    'inventoryCost' => $p['inventoryCost'] ?? 0
                ];
            }
        }
        usort($items, function ($a, $b) { return $b['inventoryCost'] <=> $a['inventoryCost']; });
        $items = array_slice($items, 0, 25);

        $advice = $items ? $this->callGeminiRestockAdvisor($items) : [];

        $payload = [
            'generatedAt' => date('Y-m-d H:i:s'),
            'tab' => $tab,
            'advice' => $advice
        ];
        file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE));
        return ['success' => true, 'cached' => false, 'data' => $payload];
    }

    private function callGeminiRestockAdvisor($items)
    {
        $itemSchema = [
            'type' => 'OBJECT',
            'properties' => [
                'sku' => ['type' => 'STRING'],
                'level' => ['type' => 'STRING', 'enum' => ['restock', 'watch', 'clear', 'ok']],
                'advice' => ['type' => 'STRING']
            ],
            'required' => ['sku', 'level', 'advice']
        ];
        $schema = ['type' => 'ARRAY', 'items' => $itemSchema];

        $prompt = "你是磁磚經銷商的庫存管理顧問。以下是部分產品的庫存與銷售數據（單位：坪），請針對每一個 SKU 給出補貨/促銷建議。\n"
            . "規則：\n"
            . "1. level：庫存偏低、近期銷速快、應盡快補貨 = restock；庫存與銷速雖正常但需留意（如剛開始滯銷）= watch；滯銷已久、庫存偏高、建議促銷去化 = clear；狀況正常不需特別處理 = ok。\n"
            . "2. advice 用一句話講清楚建議動作與簡單理由（20字內），可帶數字，例如「庫存僅1.2坪，月銷2坪，建議補貨3坪」。\n"
            . "3. 每個傳入的 sku 都要回覆一筆，照原順序。\n"
            . "4. 嚴格依照 JSON schema 輸出陣列，不要多餘文字。\n\n"
            . "資料如下：\n" . json_encode($items, JSON_UNESCAPED_UNICODE);

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;
        $body = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
                'temperature' => 0.3,
                'maxOutputTokens' => 8192
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE)
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new RuntimeException("Gemini 連線錯誤: {$curlErr}");
        }
        if ($httpCode >= 400) {
            throw new RuntimeException("Gemini API 錯誤 (HTTP {$httpCode}): {$resp}");
        }

        $result = json_decode($resp, true);
        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $finishReason = $result['candidates'][0]['finishReason'] ?? '';
        $text = trim($text);
        $text = preg_replace('/^```(json)?|```$/m', '', $text);
        $advice = json_decode($text, true);
        if (!is_array($advice)) {
            throw new RuntimeException("Gemini 回應格式錯誤 (finish={$finishReason}): " . substr($text, 0, 500));
        }
        return $advice;
    }

    public function getReservationMap()
    {
        $rows = $this->gs->readSheet('保留單');
        if (count($rows) < 2) return [];
        $h = $rows[0];
        $skuIdx = findHeader($h, ['編號']);
        $qtyIdx = findHeader($h, ['數量']);
        if ($skuIdx === null || $qtyIdx === null) return [];
        $map = [];
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $sku = trim($row[$skuIdx] ?? '');
            if ($sku === '') continue;
            $qty = (float)($row[$qtyIdx] ?? 0);
            if (!isset($map[$sku])) $map[$sku] = ['reservedQty' => 0, 'reservedCount' => 0];
            $map[$sku]['reservedQty'] += $qty;
            $map[$sku]['reservedCount'] += 1;
        }
        return $map;
    }

    public function recordProductHistory($tab, $products)
    {
        if (!is_dir(AI_ADVISOR_CACHE_DIR)) {
            @mkdir(AI_ADVISOR_CACHE_DIR, 0775, true);
        }
        $file = AI_ADVISOR_CACHE_DIR . "/product_history_{$tab}.json";
        $history = is_file($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
        $key = date('Y-m');
        $reservationMap = $this->getReservationMap();
        $snapshot = [];
        foreach ($products as $p) {
            $res = $reservationMap[$p['sku']] ?? ['reservedQty' => 0, 'reservedCount' => 0];
            $snapshot[$p['sku']] = [
                'totalPings' => $p['totalPings'] ?? 0,
                'stockPing' => $p['stockPing'] ?? 0,
                'mos' => $p['mos'] ?? 0,
                'monthlySpeedPings' => $p['monthlySpeedPings'] ?? 0,
                'daysSinceLastSale' => $p['daysSinceLastSale'],
                'reservedQty' => $res['reservedQty'],
                'reservedCount' => $res['reservedCount']
            ];
        }
        $history[$key] = ['date' => $key, 'products' => $snapshot];
        if (count($history) > 24) {
            $history = array_slice($history, -24, null, true);
        }
        file_put_contents($file, json_encode($history, JSON_UNESCAPED_UNICODE));
        return $history;
    }

    public function getProductHistory($tab, $sku)
    {
        $tab = in_array($tab, ['sleeper', 'normal', 'discontinued']) ? $tab : 'sleeper';
        $file = AI_ADVISOR_CACHE_DIR . "/product_history_{$tab}.json";
        $history = is_file($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
        $rows = [];
        foreach ($history as $key => $entry) {
            $rows[] = array_merge(['date' => $entry['date'] ?? $key], $entry['products'][$sku] ?? []);
        }
        return ['success' => true, 'data' => $rows];
    }

    public function getProductLifecycle($sku)
    {
        $sku = $this->cleanSku($sku);
        $profileMap = $this->getProductProfileMap();
        $profile = $profileMap[$sku] ?? [];
        $firstInDate = $profile['firstInDate'] ?? '';
        $latestInDate = $profile['latestInDate'] ?? '';

        $activeDisplays = $this->getActiveDisplaysMap();
        $displays = $activeDisplays[$sku] ?? [];
        $displayCount = count($displays);

        $daysSinceArrival = null;
        $ts = $firstInDate !== '' ? strtotime($firstInDate) : false;
        if ($ts !== false) {
            $daysSinceArrival = (int)floor((time() - $ts) / 86400);
        }

        $monthlyHistory = [];
        foreach (['sleeper', 'normal', 'discontinued'] as $tab) {
            $file = AI_ADVISOR_CACHE_DIR . "/product_history_{$tab}.json";
            if (!is_file($file)) continue;
            $history = json_decode(file_get_contents($file), true) ?: [];
            foreach ($history as $key => $entry) {
                if (isset($entry['products'][$sku])) {
                    $monthlyHistory[] = array_merge(['date' => $entry['date'] ?? $key], $entry['products'][$sku]);
                }
            }
        }
        usort($monthlyHistory, function ($a, $b) { return strcmp($a['date'], $b['date']); });

        return [
            'success' => true,
            'data' => [
                'sku' => $sku,
                'firstInDate' => $firstInDate,
                'latestInDate' => $latestInDate,
                'daysSinceArrival' => $daysSinceArrival,
                'displayCount' => $displayCount,
                'displays' => $displays,
                'monthlyHistory' => $monthlyHistory
            ]
        ];
    }

    public function getReportHistory()
    {
        $file = AI_ADVISOR_CACHE_DIR . '/report_history.json';
        $history = is_file($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
        return ['success' => true, 'data' => array_values($history)];
    }

    public function recordReportSnapshot($year, $month, $summary)
    {
        if (!is_dir(AI_ADVISOR_CACHE_DIR)) {
            @mkdir(AI_ADVISOR_CACHE_DIR, 0775, true);
        }
        $file = AI_ADVISOR_CACHE_DIR . '/report_history.json';
        $history = is_file($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
        $key = sprintf('%d-%02d', $year, $month);
        $history[$key] = [
            'year' => $year,
            'month' => $month,
            'recordedAt' => date('Y-m-d H:i:s'),
            'kpi' => $summary['kpi'] ?? null,
            'contractSummary' => $summary['contractSummary'] ?? null,
            'contractHealthCounts' => $summary['contractHealthCounts'] ?? null,
            'brandSales' => $summary['brandSales'] ?? null,
            'topSeries' => $summary['topSeries'] ?? null,
            'inventory' => $summary['inventoryHistory'][$key] ?? null
        ];
        uksort($history, 'strcmp');
        if (count($history) > 36) {
            $history = array_slice($history, -36, null, true);
        }
        file_put_contents($file, json_encode($history, JSON_UNESCAPED_UNICODE));
        return array_values($history);
    }

    public function recordInventorySnapshot($year, $month)
    {
        if (!is_dir(AI_ADVISOR_CACHE_DIR)) {
            @mkdir(AI_ADVISOR_CACHE_DIR, 0775, true);
        }
        $file = AI_ADVISOR_CACHE_DIR . '/inventory_history.json';
        $history = is_file($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
        $key = sprintf('%d-%02d', $year, $month);
        $summary = $this->getInventorySummary();
        $history[$key] = ['year' => $year, 'month' => $month, 'totalCost' => $summary['totalCost'], 'totalPing' => $summary['totalPing']];
        uksort($history, 'strcmp');
        if (count($history) > 24) {
            $history = array_slice($history, -24, null, true);
        }
        file_put_contents($file, json_encode($history, JSON_UNESCAPED_UNICODE));
        return array_values($history);
    }

    public function getMetaMap()
    {
        $data = $this->gs->readSheet(PRICE_SHEET);
        if (count($data) < 2) return [];

        $h = $data[0];
        $idxCode = $this->findHeader($h, ['編號','產品編號']);
        $idxSeries = $this->findHeader($h, ['中文系列','系列']);
        $idxPerPing = $this->findHeader($h, ['片/坪']);
        if ($idxCode === -1) return [];

        $map = [];
        for ($i = 1; $i < count($data); $i++) {
            $sku = $this->cleanSku($this->getVal($data[$i], $idxCode));
            if (!$sku) continue;
            $map[$sku] = [
                'series'  => $idxSeries !== -1 ? trim($this->getVal($data[$i], $idxSeries)) : '',
                'perPing' => $idxPerPing !== -1 ? ($this->optFloat($this->getVal($data[$i], $idxPerPing)) ?: 36) : 36
            ];
        }
        return $map;
    }

    public function getProductProfileMap()
    {
        $data = $this->gs->readSheet(PRICE_SHEET);
        if (count($data) < 2) return [];

        $h = $data[0];
        $idxCode = $this->findHeader($h, ['編號','產品編號']);
        $idxSeries = $this->findHeader($h, ['系列','英文系列']);
        $idxSeriesCn = $this->findHeader($h, ['中文系列','系列中文','中文名稱']);
        $idxPerPing = $this->findHeader($h, ['片/坪']);
        $idxBrand = $this->findHeader($h, ['廠牌','品牌']);
        $idxCountry = $this->findHeader($h, ['產地','國家']);
        $idxProduct = $this->findHeader($h, ['原廠品名','產品名稱','品名']);
        $idxSize = $this->findHeader($h, ['尺寸(cm)','尺寸']);
        $idxCategory = $this->findHeader($h, ['產品大類','大類']);
        $idxSleeper = $this->findHeader($h, ['睡美人']);
        $idxDisc = $this->findHeader($h, ['不續辦']);
        $idxImage = $this->findHeader($h, ['單片連結網址','單片圖','圖片網址','單片網址','圖片連結']);
        $idxFirstIn = $this->findHeader($h, ['首次進貨日']);
        $idxLatestIn = $this->findHeader($h, ['最新進貨日']);
        if ($idxCode === -1) return [];

        $map = [];
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            $sku = $this->cleanSku($this->getVal($row, $idxCode));
            if (!$sku) continue;
            $map[$sku] = [
                'series' => $idxSeries !== -1 ? trim($this->getVal($row, $idxSeries)) : '',
                'seriesCn' => $idxSeriesCn !== -1 ? trim($this->getVal($row, $idxSeriesCn)) : '',
                'perPing' => $idxPerPing !== -1 ? ($this->optFloat($this->getVal($row, $idxPerPing)) ?: 36) : 36,
                'brand' => $idxBrand !== -1 ? trim($this->getVal($row, $idxBrand)) : '',
                'country' => $idxCountry !== -1 ? trim($this->getVal($row, $idxCountry)) : '',
                'productName' => $idxProduct !== -1 ? trim($this->getVal($row, $idxProduct)) : '',
                'size' => $this->normalizeSizeLabel($idxSize !== -1 ? $this->getVal($row, $idxSize) : ''),
                'category' => $idxCategory !== -1 ? trim($this->getVal($row, $idxCategory)) : '',
                'imageUrl' => $idxImage !== -1 ? trim($this->getVal($row, $idxImage)) : '',
                'isSleeper' => $idxSleeper !== -1 && trim($this->getVal($row, $idxSleeper)) !== '',
                'isDiscontinued' => $idxDisc !== -1 && trim($this->getVal($row, $idxDisc)) !== '',
                'firstInDate' => $idxFirstIn !== -1 ? trim($this->getVal($row, $idxFirstIn)) : '',
                'latestInDate' => $idxLatestIn !== -1 ? trim($this->getVal($row, $idxLatestIn)) : ''
            ];
        }
        return $map;
    }

    private function getSleeperCostMap()
    {
        $data = $this->gs->readSheet(SLEEPER_SHEET);
        if (count($data) < 2) return [];

        $h = $data[0];
        $idxSku  = $this->findHeader($h, ['編號','產品編號','品號']);
        $idxCost = $this->findHeader($h, ['成本','單片成本','成本價']);
        if ($idxSku === -1 || $idxCost === -1) return [];

        $map = [];
        for ($i = 1; $i < count($data); $i++) {
            $sku = $this->cleanSku($this->getVal($data[$i], $idxSku));
            if (!$sku) continue;
            $map[$sku] = $this->optFloat($this->getVal($data[$i], $idxCost));
        }
        return $map;
    }

    private function getPriceCostMap()
    {
        $data = $this->gs->readSheet(PRICE_SHEET);
        if (count($data) < 2) return [];

        $h = $data[0];
        $idxCode = $this->findHeader($h, ['編號','產品編號']);
        $idxCost = $this->findHeader($h, ['成本','單片成本','成本價']);
        if ($idxCode === -1 || $idxCost === -1) return [];

        $map = [];
        for ($i = 1; $i < count($data); $i++) {
            $sku = $this->cleanSku($this->getVal($data[$i], $idxCode));
            if (!$sku) continue;
            $map[$sku] = $this->optFloat($this->getVal($data[$i], $idxCost));
        }
        return $map;
    }

    public function getSleeperSalesByMonth($year, $month)
    {
        $configRes = $this->getSleeperConfig();
        if (!$configRes['success']) return $configRes;
        $sleeperMap = $configRes['data'];
        $seriesMap = $this->getSeriesMap();

        $raw = $this->gs->readSheet(SALES_SHEET);
        if (count($raw) < 2) return ['success' => false, 'msg' => '經銷銷售報表無資料'];

        $h = $raw[0];
        $idx = [
            'date'     => $this->findHeader($h, ['日期','單據日期','銷貨日期']),
            'sales'    => $this->findHeader($h, ['負責業務','業務','業務員','負責人']),
            'cust'     => $this->findHeader($h, ['客戶名稱','客戶']),
            'custCode' => $this->findHeader($h, ['客戶編號','客戶代碼','代碼']),
            'code'     => $this->findHeader($h, ['產品編號','編號','品碼','序號']),
            'amt'      => $this->findHeader($h, ['金額','銷額','銷售金額','成交金額','小計','總計']),
            'qty'      => $this->findHeader($h, ['數量','片數']),
            'note'     => $this->findHeader($h, ['備註','說明'])
        ];
        if ($idx['date'] === -1 || $idx['code'] === -1) {
            return ['success' => false, 'msg' => '無法定位銷貨報表欄位'];
        }

        $results = [];
        for ($i = 1; $i < count($raw); $i++) {
            $row = $raw[$i];
            $d = $this->parseDate($this->getVal($row, $idx['date']));
            if (!$d) continue;
            if ($d->format('Y') != $year || $d->format('n') != $month + 1) continue;

            $custName = trim($this->getVal($row, $idx['cust']));
            $custCode = $idx['custCode'] !== -1 ? trim($this->getVal($row, $idx['custCode'])) : '';
            $note = trim($this->getVal($row, $idx['note']));
            $amt = $this->optFloat($this->getVal($row, $idx['amt']));
            if ($this->isSampleRow($custCode, $custName, $note, $amt)) continue;

            $code = $this->cleanSku($this->getVal($row, $idx['code']));
            if (!$code || !isset($sleeperMap[$code])) continue;

            $sleeper = $sleeperMap[$code];
            $qty = $this->optFloat($this->getVal($row, $idx['qty']));
            if ($qty == 0 || $amt == 0) continue;


            $unitPrice = $qty > 0 ? $amt / $qty : 0;
            $totalCost = $sleeper['cost'] * $qty;
            $margin = $amt > 0 ? ($amt - $totalCost) / $amt : 0;
            $priceCostRatio = $sleeper['cost'] > 0 ? $unitPrice / $sleeper['cost'] : 1;
            $multiplier = $this->calcMultiplier($sleeper['grade'], $margin, $priceCostRatio, false);

            $results[] = [
                'salesName'  => trim($this->getVal($row, $idx['sales'])),
                'month'      => sprintf('%d/%02d', $year, $month + 1),
                'cust'       => $custName,
                'sku'        => $code,
                'series'     => isset($seriesMap[$code]) ? $seriesMap[$code] : '一般',
                'qty'        => $qty,
                'unitPrice'  => round($unitPrice, 2),
                'amt'        => round($amt),
                'cost'       => $sleeper['cost'],
                'totalCost'  => $totalCost,
                'margin'     => round($margin * 10000) / 100,
                'grade'      => $sleeper['grade'],
                'multiplier' => $multiplier,
                'bonus'      => round($amt * $multiplier),
                'isFullClearance' => false,
                'note'       => ''
            ];
        }
        return ['success' => true, 'data' => $results];
    }

    public function syncTrialSheet($year, $month)
    {
        $res = $this->getSleeperSalesByMonth($year, $month);
        if (!$res['success']) return $res;
        $newData = $res['data'];

        $this->ensureTrialSheet();

        $existing = $this->gs->readSheet(TRIAL_SHEET);
        $existingIds = [];
        if (count($existing) > 1) {
            $idCol = array_search('識別碼', $existing[0]);
            if ($idCol !== false) {
                for ($i = 1; $i < count($existing); $i++) {
                    $id = trim($this->getVal($existing[$i], $idCol));
                    if ($id) $existingIds[$id] = true;
                }
            }
        }

        $rowsToAdd = [];
        $seqCount = [];
        foreach ($newData as $d) {
            $key = $d['salesName'] . '|' . $d['month'] . '|' . $d['cust'] . '|' . $d['sku'];
            $seqCount[$key] = isset($seqCount[$key]) ? $seqCount[$key] + 1 : 1;
            $rowId = $d['salesName'] . '|' . $d['month'] . '|' . $d['cust'] . '|' . $d['sku'] . '|' . $seqCount[$key];
            if (isset($existingIds[$rowId])) continue;

            $rowsToAdd[] = [
                $rowId,
                $d['salesName'],
                "'" . $d['month'],
                $d['cust'],
                $d['sku'],
                $d['series'],
                $d['qty'],
                $d['unitPrice'],
                $d['amt'],
                $d['cost'],
                $d['margin'] . '%',
                $d['grade'],
                $d['multiplier'],
                $d['bonus'],
                '',
                '',
                '系統'
            ];
        }

        if (count($rowsToAdd) > 0) {
            $this->gs->appendRows(TRIAL_SHEET, $rowsToAdd);
        }

        return ['success' => true, 'added' => count($rowsToAdd), 'total' => count($newData)];
    }

    public function recalcTrialSheet()
    {
        $data = $this->gs->readSheet(TRIAL_SHEET);
        if (count($data) < 2) return ['success' => true, 'count' => 0];

        $h = $data[0];
        $cols = [
            'qty'       => array_search('片數', $h),
            'price'     => array_search('單價', $h),
            'amt'       => array_search('金額', $h),
            'cost'      => array_search('成本', $h),
            'margin'    => array_search('毛利率', $h),
            'multiplier'=> array_search('倍數', $h),
            'bonus'     => array_search('獎金', $h),
            'clearance' => array_search('全出清', $h),
            'grade'     => array_search('等級', $h)
        ];

        if ($cols['margin'] === false || $cols['multiplier'] === false || $cols['bonus'] === false) {
            return ['success' => false, 'msg' => '試算表欄位不完整'];
        }

        $marginVals = []; $multVals = []; $bonusVals = [];
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            $qty = $this->optFloat($this->getVal($row, $cols['qty']));
            $unitPrice = $this->optFloat($this->getVal($row, $cols['price']));
            $amt = $this->optFloat($this->getVal($row, $cols['amt']));
            if ($amt == 0) $amt = $qty * $unitPrice;
            $cost = $this->optFloat($this->getVal($row, $cols['cost']));
            $totalCost = $cost * $qty;
            $margin = $amt > 0 ? round(($amt - $totalCost) / $amt * 10000) / 100 : 0;
            $grade = strtoupper(trim($this->getVal($row, $cols['grade'])));
            $priceCostRatio = $cost > 0 ? $unitPrice / $cost : 1;
            $isFullClearance = (trim($this->getVal($row, $cols['clearance'])) === '是');
            $autoMult = $this->calcMultiplier($grade, $margin / 100, $priceCostRatio, $isFullClearance);

            $marginVals[] = [$margin . '%'];
            $multVals[] = [$autoMult];
            $bonusVals[] = [round($amt * $autoMult)];
        }

        $count = count($marginVals);
        if ($count > 0) {
            $this->gs->writeRows(TRIAL_SHEET, 2, $this->padToColumn($marginVals, $cols['margin'] + 1));
            $this->gs->writeRows(TRIAL_SHEET, 2, $this->padToColumn($multVals, $cols['multiplier'] + 1));
            $this->gs->writeRows(TRIAL_SHEET, 2, $this->padToColumn($bonusVals, $cols['bonus'] + 1));
        }

        return ['success' => true, 'count' => $count];
    }

    public function updateTrialRow($rowIdx, $qty, $unitPrice, $multiplier, $clearance, $note)
    {
        $amt = round($qty * $unitPrice);
        $data = $this->gs->readSheet(TRIAL_SHEET);
        if ($rowIdx < 2 || $rowIdx > count($data)) {
            return ['success' => false, 'msg' => '行號無效'];
        }

        $row = $data[$rowIdx - 1];
        $h = $data[0];
        $cols = [
            'qty'       => array_search('片數', $h),
            'price'     => array_search('單價', $h),
            'amt'       => array_search('金額', $h),
            'cost'      => array_search('成本', $h),
            'margin'    => array_search('毛利率', $h),
            'multiplier'=> array_search('倍數', $h),
            'bonus'     => array_search('獎金', $h),
            'clearance' => array_search('全出清', $h),
            'grade'     => array_search('等級', $h),
            'note'      => array_search('備註', $h)
        ];

        $cost = $this->optFloat($this->getVal($row, $cols['cost']));
        $totalCost = $cost * $qty;
        $grade = strtoupper(trim($this->getVal($row, $cols['grade'])));
        $priceCostRatio = $cost > 0 ? $unitPrice / $cost : 1;
        $isFullClearance = ($clearance === '是');
        $margin = $amt > 0 ? round(($amt - $totalCost) / $amt * 10000) / 100 : 0;
        $autoMult = $multiplier ?: $this->calcMultiplier($grade, $margin / 100, $priceCostRatio, $isFullClearance);
        $bonus = round($amt * $autoMult);

        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['qty'], $qty);
        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['price'], $unitPrice);
        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['amt'], $amt);
        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['margin'], $margin . '%');
        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['multiplier'], $autoMult);
        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['bonus'], $bonus);
        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['clearance'], $clearance ?: '');
        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['note'], $note);

        return ['success' => true, 'rowIdx' => $rowIdx];
    }

    public function readTrialSheet($year, $month, $salesName = '')
    {
        $data = $this->gs->readSheet(TRIAL_SHEET);
        if (count($data) < 2) return ['success' => true, 'data' => []];

        $h = $data[0];
        $monthCol = array_search('月份', $h);
        if ($monthCol === false) return ['success' => false, 'msg' => '找不到月份欄'];

        $results = [];
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            $r = [];
            foreach ($h as $j => $col) {
                $r[$col] = trim($this->getVal($row, $j));
            }
            $r['_rowIdx'] = $i + 1;

            $monthStr = ltrim($r['月份'], "'");

            if ($year !== null && $month !== null) {
                $expected = sprintf('%d/%02d', $year, $month + 1);
                if ($monthStr !== $expected) continue;
            } elseif ($year !== null) {
                if (strpos($monthStr, (string)$year) !== 0) continue;
            }

            if ($salesName && $r['業務'] !== $salesName) continue;

            $amt = $this->optFloat($r['金額']);
            $cost = $this->optFloat($r['成本']);
            $qty = $this->optFloat($r['片數']);
            $totalCost = $cost * $qty;
            $margin = $amt > 0 ? round(($amt - $totalCost) / $amt * 10000) / 100 : 0;
            $mult = $this->optFloat($r['倍數']);
            if ($mult == 0) $mult = 1;

            $r['_amt'] = $amt;
            $r['_cost'] = $cost;
            $r['_qty'] = $qty;
            $r['_totalCost'] = $totalCost;
            $r['_margin'] = $margin;
            $r['_multiplier'] = $mult;
            $r['_bonus'] = round($amt * $mult);

            $results[] = $r;
        }

        return ['success' => true, 'data' => $results];
    }

    public function getBonusSummary($year, $month)
    {
        $res = $this->readTrialSheet($year, $month);
        if (!$res['success']) return $res;

        $rows = $res['data'];
        $groups = [];

        foreach ($rows as $r) {
            $name = isset(self::$salesMerge[$r['業務']]) ? self::$salesMerge[$r['業務']] : ($r['業務'] ?: '未指定');
            if (!isset($groups[$name])) {
                $groups[$name] = [
                    'salesName' => $name, 'count' => 0,
                    'totalAmt' => 0, 'totalCost' => 0,
                    'totalBonus' => 0, 'details' => []
                ];
            }
            $groups[$name]['count']++;
            $groups[$name]['totalAmt'] += $r['_amt'];
            $groups[$name]['totalCost'] += $r['_totalCost'];
            $groups[$name]['totalBonus'] += $r['_bonus'];
            $groups[$name]['details'][] = $r;
        }

        $people = [];
        foreach ($groups as $g) {
            $margin = $g['totalAmt'] > 0
                ? round(($g['totalAmt'] - $g['totalCost']) / $g['totalAmt'] * 10000) / 100
                : 0;
            $people[] = [
                'salesName' => $g['salesName'],
                'count'     => $g['count'],
                'totalAmt'  => round($g['totalAmt']),
                'totalCost' => round($g['totalCost']),
                'margin'    => $margin,
                'totalBonus'=> $g['totalBonus'],
                'details'   => $g['details']
            ];
        }
        usort($people, function($a, $b) { return $b['totalBonus'] - $a['totalBonus']; });

        $grand = [
            'count'      => array_sum(array_map(function($p) { return $p['count']; }, $people)),
            'totalAmt'   => array_sum(array_map(function($p) { return $p['totalAmt']; }, $people)),
            'totalCost'  => array_sum(array_map(function($p) { return $p['totalCost']; }, $people)),
            'totalBonus' => array_sum(array_map(function($p) { return $p['totalBonus']; }, $people))
        ];
        $grand['margin'] = $grand['totalAmt'] > 0
            ? round(($grand['totalAmt'] - $grand['totalCost']) / $grand['totalAmt'] * 10000) / 100
            : 0;

        return ['success' => true, 'data' => ['people' => $people, 'grand' => $grand]];
    }

    public function getYearSummary($year)
    {
        $data = $this->gs->readSheet(TRIAL_SHEET);
        $currentMonth = (int)date('n');
        $yearStr = (string)$year;

        $months = [];
        $grandTotal = 0;
        $peopleTotal = [];
        $custData = [];
        $prodTotals = [];
        $totalAmt = 0;

        if (count($data) > 1) {
            $h = $data[0];
            $monthIdx = array_search('月份', $h);
            $salesIdx = array_search('業務', $h);
            $custIdx  = array_search('客戶', $h);
            $skuIdx   = array_search('編號', $h);
            $seriesIdx= array_search('系列', $h);
            $amtIdx   = array_search('金額', $h);
            $multIdx  = array_search('倍數', $h);

            $monthMap = [];
            for ($i = 1; $i < count($data); $i++) {
                $r = $data[$i];
                $monthVal = ltrim(trim($this->getVal($r, $monthIdx)), "'");
                if (strpos($monthVal, $yearStr) !== 0) continue;

                $mNum = (int)substr($monthVal, 5, 2);
                $salesRaw = trim($this->getVal($r, $salesIdx));
                $sales = isset(self::$salesMerge[$salesRaw]) ? self::$salesMerge[$salesRaw] : $salesRaw;
                $cust  = trim($this->getVal($r, $custIdx));
                $sku   = trim($this->getVal($r, $skuIdx));
                $series= trim($this->getVal($r, $seriesIdx));
                $amt   = $this->optFloat($this->getVal($r, $amtIdx));
                $mult  = $this->optFloat($this->getVal($r, $multIdx)) ?: 1;
                $bonus = round($amt * $mult);

                if (!isset($monthMap[$mNum])) {
                    $monthMap[$mNum] = ['total' => 0, 'people' => []];
                }
                $monthMap[$mNum]['total'] += $bonus;
                if ($sales) {
                    if (!isset($monthMap[$mNum]['people'][$sales])) $monthMap[$mNum]['people'][$sales] = 0;
                    $monthMap[$mNum]['people'][$sales] += $bonus;
                    if (!isset($peopleTotal[$sales])) $peopleTotal[$sales] = 0;
                    $peopleTotal[$sales] += $bonus;
                }
                $grandTotal += $bonus;

                if ($cust && $amt) {
                    if (!isset($custData[$cust])) $custData[$cust] = ['total' => 0, 'series' => []];
                    $custData[$cust]['total'] += abs($amt);
                    if (!isset($custData[$cust]['series'][$series])) $custData[$cust]['series'][$series] = 0;
                    $custData[$cust]['series'][$series] += abs($amt);
                    $totalAmt += abs($amt);
                }
                if ($sku && $amt) {
                    $key = $sku . '|' . $series;
                    if (!isset($prodTotals[$key])) $prodTotals[$key] = ['sku' => $sku, 'series' => $series, 'amt' => 0];
                    $prodTotals[$key]['amt'] += abs($amt);
                }
            }

            for ($m = 1; $m <= $currentMonth; $m++) {
                $md = isset($monthMap[$m]) ? $monthMap[$m] : ['total' => 0, 'people' => []];
                $months[] = ['month' => $m, 'total' => $md['total'], 'people' => $md['people']];
            }
        } else {
            for ($m = 1; $m <= $currentMonth; $m++) {
                $months[] = ['month' => $m, 'total' => 0, 'people' => []];
            }
        }

        $maxMonthTotal = 0;
        foreach ($months as $m) {
            if ($m['total'] > $maxMonthTotal) $maxMonthTotal = $m['total'];
        }

        $top10cust = [];
        uasort($custData, function($a, $b) { return $b['total'] - $a['total']; });
        $ci = 0;
        foreach ($custData as $name => $data) {
            if ($ci++ >= 10) break;
            arsort($data['series']);
            $topSeries = key($data['series']);
            $top10cust[] = [
                'name'     => mb_substr($name, 0, 2, 'UTF-8'),
                'fullName' => $name,
                'series'   => $topSeries ?: '',
                'totalWan' => round($data['total'] / 10000, 1),
                'pct'      => $totalAmt > 0 ? round($data['total'] / $totalAmt * 100, 1) : 0
            ];
        }

        $top10prod = [];
        usort($prodTotals, function($a, $b) { return $b['amt'] - $a['amt']; });
        $top10prod = array_slice($prodTotals, 0, 10);

        $discontStats = ['discCount' => 0, 'missingSleeper' => []];
        $pData = $this->gs->readSheet(PRICE_SHEET);
        if (count($pData) > 1) {
            $pH = $pData[0];
            $pCode  = $this->findHeader($pH, ['編號','產品編號']);
            $pDisc  = $this->findHeader($pH, ['不續辦']);
            $pSlp   = $this->findHeader($pH, ['睡美人']);
            $pSer   = $this->findHeader($pH, ['中文系列','系列']);

            $discCount = 0;
            $sleeperFromPrice = [];
            for ($i = 1; $i < count($pData); $i++) {
                $row = $pData[$i];
                $sku = $this->cleanSku($this->getVal($row, $pCode));
                if (!$sku) continue;
                if ($pDisc !== -1 && trim($this->getVal($row, $pDisc)) !== '') $discCount++;
                if ($pSlp !== -1 && trim($this->getVal($row, $pSlp)) !== '') {
                    $sleeperFromPrice[$sku] = $pSer !== -1 ? trim($this->getVal($row, $pSer)) : '';
                }
            }
            $discontStats['discCount'] = $discCount;

            $sleeperConfig = $this->getSleeperConfig();
            $existing = $sleeperConfig['success'] ? $sleeperConfig['data'] : [];
            foreach ($sleeperFromPrice as $sku => $series) {
                if (!isset($existing[$sku])) {
                    $discontStats['missingSleeper'][] = ['sku' => $sku, 'series' => $series];
                }
            }
        }

        return [
            'success' => true,
            'data' => [
                'year' => $year,
                'currentMonth' => $currentMonth,
                'months' => $months,
                'grandTotal' => $grandTotal,
                'peopleTotal' => $peopleTotal,
                'target' => 3000000,
                'maxMonthTotal' => $maxMonthTotal,
                'topCustomers' => $top10cust,
                'topProducts' => $top10prod,
                'discontStats' => $discontStats
            ]
        ];
    }

    private function getStrategyPeriodMeta($mode, $year, $period)
    {
        $mode = in_array($mode, ['month', 'quarter', 'half', 'year'], true) ? $mode : 'month';
        $period = (int)$period;
        $year = (int)$year;

        if ($mode === 'month') {
            $period = max(1, min(12, $period));
            return [
                'mode' => 'month',
                'year' => $year,
                'period' => $period,
                'months' => [$period],
                'label' => $year . ' / ' . $period,
                'primaryLabel' => 'MOM'
            ];
        }
        if ($mode === 'quarter') {
            $period = max(1, min(4, $period));
            $start = ($period - 1) * 3 + 1;
            return [
                'mode' => 'quarter',
                'year' => $year,
                'period' => $period,
                'months' => [$start, $start + 1, $start + 2],
                'label' => $year . ' Q' . $period,
                'primaryLabel' => 'QOQ'
            ];
        }
        if ($mode === 'half') {
            $period = $period === 2 ? 2 : 1;
            return [
                'mode' => 'half',
                'year' => $year,
                'period' => $period,
                'months' => $period === 1 ? [1, 2, 3, 4, 5, 6] : [7, 8, 9, 10, 11, 12],
                'label' => $year . ' H' . $period,
                'primaryLabel' => 'HOH'
            ];
        }

        return [
            'mode' => 'year',
            'year' => $year,
            'period' => 1,
            'months' => range(1, 12),
            'label' => (string)$year,
            'primaryLabel' => 'YOY'
        ];
    }

    private function getPreviousStrategyPeriodMeta($meta)
    {
        $year = (int)$meta['year'];
        $period = (int)$meta['period'];
        $mode = $meta['mode'];
        if ($mode === 'month') {
            return $period === 1
                ? $this->getStrategyPeriodMeta('month', $year - 1, 12)
                : $this->getStrategyPeriodMeta('month', $year, $period - 1);
        }
        if ($mode === 'quarter') {
            return $period === 1
                ? $this->getStrategyPeriodMeta('quarter', $year - 1, 4)
                : $this->getStrategyPeriodMeta('quarter', $year, $period - 1);
        }
        if ($mode === 'half') {
            return $period === 1
                ? $this->getStrategyPeriodMeta('half', $year - 1, 2)
                : $this->getStrategyPeriodMeta('half', $year, 1);
        }
        return $this->getStrategyPeriodMeta('year', $year - 1, 1);
    }

    private function getYoyStrategyPeriodMeta($meta)
    {
        return $this->getStrategyPeriodMeta($meta['mode'], (int)$meta['year'] - 1, (int)$meta['period']);
    }

    private function buildStrategySummaryFromBuckets($salesTotals, $customerTotals, $total, $pings, $txCount)
    {
        $salesList = array_values($salesTotals);
        usort($salesList, function ($a, $b) { return $b['amount'] <=> $a['amount']; });
        $customerList = array_values($customerTotals);
        usort($customerList, function ($a, $b) { return $b['amount'] <=> $a['amount']; });

        $top3SalesAmt = 0;
        for ($i = 0; $i < min(3, count($salesList)); $i++) $top3SalesAmt += $salesList[$i]['amount'];
        $topCustomerAmt = count($customerList) ? $customerList[0]['amount'] : 0;

        return [
            'total' => round($total),
            'pings' => round($pings, 2),
            'txCount' => (int)$txCount,
            'avgTicket' => $txCount > 0 ? round($total / $txCount) : 0,
            'salesCount' => count($salesTotals),
            'top3SalesPct' => $total > 0 ? round($top3SalesAmt / $total * 100, 1) : 0,
            'topCustomerPct' => $total > 0 ? round($topCustomerAmt / $total * 100, 1) : 0
        ];
    }

    private function buildStrategyCompare($current, $base, $label)
    {
        $fields = ['total', 'pings', 'txCount', 'avgTicket'];
        $out = ['label' => $label];
        foreach ($fields as $field) {
            $curr = (float)($current[$field] ?? 0);
            $prev = (float)($base[$field] ?? 0);
            $delta = $curr - $prev;
            $out[$field . 'Delta'] = round($delta, $field === 'pings' ? 2 : 0);
            $out[$field . 'Pct'] = $prev != 0 ? round($delta / $prev * 100, 1) : ($curr != 0 ? 100.0 : 0.0);
        }
        return $out;
    }

    private function buildFieldActivityCompare($current, $base, $label)
    {
        $fields = ['totalVisits', 'visitedCustomers', 'totalKm', 'fuelAmount', 'salesPerVisit', 'salesPerKm', 'visitSalesCorrelation'];
        $out = ['label' => $label];
        foreach ($fields as $field) {
            $curr = (float)($current[$field] ?? 0);
            $prev = (float)($base[$field] ?? 0);
            $delta = $curr - $prev;
            $precision = $field === 'visitSalesCorrelation' ? 4 : 0;
            $out[$field . 'Delta'] = round($delta, $precision);
            $out[$field . 'Pct'] = $prev != 0 ? round($delta / $prev * 100, 1) : ($curr != 0 ? 100.0 : 0.0);
        }
        return $out;
    }

    private function buildDeltaLeaders($currentMap, $prevMap, $type)
    {
        $keys = array_unique(array_merge(array_keys($currentMap), array_keys($prevMap)));
        $rows = [];
        foreach ($keys as $key) {
            $curr = isset($currentMap[$key]) ? (float)$currentMap[$key]['amount'] : 0;
            $prev = isset($prevMap[$key]) ? (float)$prevMap[$key]['amount'] : 0;
            $delta = $curr - $prev;
            if (abs($delta) < 0.0001) continue;

            if ($type === 'product') {
                $label = isset($currentMap[$key])
                    ? ($currentMap[$key]['sku'] ?: $key)
                    : (isset($prevMap[$key]) ? ($prevMap[$key]['sku'] ?: $key) : $key);
            } else {
                $label = isset($currentMap[$key])
                    ? $currentMap[$key]['name']
                    : (isset($prevMap[$key]) ? $prevMap[$key]['name'] : $key);
            }

            $rows[] = [
                'key' => $key,
                'name' => $label,
                'delta' => round($delta),
                'current' => round($curr),
                'previous' => round($prev)
            ];
        }
        usort($rows, function ($a, $b) {
            return abs($b['delta']) <=> abs($a['delta']);
        });
        return array_slice($rows, 0, 8);
    }

    public function getStrategyReport($year, $period = null, $mode = 'month')
    {
        if ($period === null || $period === 0) $period = (int)date('n');
        $meta = $this->getStrategyPeriodMeta($mode, (int)$year, (int)$period);
        $prevMeta = $this->getPreviousStrategyPeriodMeta($meta);
        $yoyMeta = $this->getYoyStrategyPeriodMeta($meta);
        $productProfiles = $this->getProductProfileMap();

        $raw = $this->gs->readSheet(CACHE_SHEET);
        if (count($raw) < 2) {
            return ['success' => false, 'msg' => '找不到產品年度銷售快取，請先同步銷售快取。'];
        }

        $h = $raw[0];
        $idx = [
            'year' => $this->findHeader($h, ['年度']),
            'month' => $this->findHeader($h, ['月份']),
            'code' => $this->findHeader($h, ['產品編號']),
            'customer' => $this->findHeader($h, ['客戶名稱', '客戶']),
            'project' => $this->findHeader($h, ['專案名稱', '案名', '專案', '工地']),
            'sales' => $this->findHeader($h, ['負責業務', '業務']),
            'pings' => $this->findHeader($h, ['銷售坪數']),
            'amount' => $this->findHeader($h, ['銷售金額']),
            'count' => $this->findHeader($h, ['交易筆數']),
            'productName' => $this->findHeader($h, ['產品名稱', '品名'])
        ];
        if ($idx['year'] === -1 || $idx['month'] === -1 || $idx['code'] === -1 || $idx['amount'] === -1) {
            return ['success' => false, 'msg' => '快取欄位不完整，請重新同步銷售快取。'];
        }

        $metaMap = $this->getMetaMap();
        $monthTrend = [];
        for ($m = 1; $m <= 12; $m++) $monthTrend[$m] = 0;
        $trailingTrendMap = [];
        $selectedMonth = max($meta['months']);
        for ($m = 1; $m <= $selectedMonth; $m++) {
            $trailingTrendMap[$m] = [
                'month' => $m,
                'label' => $m . '月',
                'current' => 0,
                'previous' => 0
            ];
        }

        $buckets = [
            'current' => ['sales' => [], 'customers' => [], 'projects' => [], 'products' => [], 'series' => [], 'total' => 0, 'pings' => 0, 'count' => 0, 'sleeperTotal' => 0, 'projectTotal' => 0],
            'previous' => ['sales' => [], 'customers' => [], 'projects' => [], 'products' => [], 'series' => [], 'total' => 0, 'pings' => 0, 'count' => 0, 'sleeperTotal' => 0, 'projectTotal' => 0],
            'yoy' => ['sales' => [], 'customers' => [], 'projects' => [], 'products' => [], 'series' => [], 'total' => 0, 'pings' => 0, 'count' => 0, 'sleeperTotal' => 0, 'projectTotal' => 0]
        ];

        for ($i = 1; $i < count($raw); $i++) {
            $row = $raw[$i];
            $rowYear = (int)$this->getVal($row, $idx['year']);
            $rowMonth = (int)$this->getVal($row, $idx['month']);

            $amount = $this->optFloat($this->getVal($row, $idx['amount']));
            if ($rowYear === (int)$meta['year']) $monthTrend[$rowMonth] += $amount;
            if (isset($trailingTrendMap[$rowMonth])) {
                if ($rowYear === (int)$meta['year']) {
                    $trailingTrendMap[$rowMonth]['current'] += $amount;
                } elseif ($rowYear === ((int)$meta['year'] - 1)) {
                    $trailingTrendMap[$rowMonth]['previous'] += $amount;
                }
            }

            $bucketName = null;
            if ($this->matchesStrategyPeriod($rowYear, $rowMonth, $meta)) $bucketName = 'current';
            elseif ($this->matchesStrategyPeriod($rowYear, $rowMonth, $prevMeta)) $bucketName = 'previous';
            elseif ($this->matchesStrategyPeriod($rowYear, $rowMonth, $yoyMeta)) $bucketName = 'yoy';
            if ($bucketName === null) continue;

            $sku = $this->cleanSku($this->getVal($row, $idx['code']));
            $profile = isset($productProfiles[$sku]) ? $productProfiles[$sku] : null;
            $customer = $this->displayCustomerName($this->getVal($row, $idx['customer']));
            $project = trim($idx['project'] !== -1 ? $this->getVal($row, $idx['project']) : '');
            $hasProject = $project !== '';
            if (!$hasProject) $project = '非專案';
            $sales = $this->normalizeSalesRep($idx['sales'] !== -1 ? $this->getVal($row, $idx['sales']) : '');
            if (isset(self::$salesMerge[$sales])) $sales = self::$salesMerge[$sales];
            $pings = $idx['pings'] !== -1 ? $this->optFloat($this->getVal($row, $idx['pings'])) : 0;
            $txCount = $idx['count'] !== -1 ? (int)$this->getVal($row, $idx['count']) : 0;
            $productName = trim($this->getVal($row, $idx['productName']));
            $series = $profile ? trim($profile['seriesCn'] ?: $profile['series']) : (isset($metaMap[$sku]) ? trim($metaMap[$sku]['series']) : '');
            if ($series === '') $series = '未分類';

            if (!isset($buckets[$bucketName]['sales'][$sales])) $buckets[$bucketName]['sales'][$sales] = ['name' => $sales, 'amount' => 0, 'pings' => 0, 'count' => 0];
            $buckets[$bucketName]['sales'][$sales]['amount'] += $amount;
            $buckets[$bucketName]['sales'][$sales]['pings'] += $pings;
            $buckets[$bucketName]['sales'][$sales]['count'] += $txCount;

            if (!isset($buckets[$bucketName]['customers'][$customer])) $buckets[$bucketName]['customers'][$customer] = ['name' => $customer, 'amount' => 0, 'pings' => 0, 'count' => 0];
            $buckets[$bucketName]['customers'][$customer]['amount'] += $amount;
            $buckets[$bucketName]['customers'][$customer]['pings'] += $pings;
            $buckets[$bucketName]['customers'][$customer]['count'] += $txCount;

            if ($hasProject) {
                if (!isset($buckets[$bucketName]['projects'][$project])) $buckets[$bucketName]['projects'][$project] = ['name' => $project, 'amount' => 0, 'pings' => 0, 'count' => 0];
                $buckets[$bucketName]['projects'][$project]['amount'] += $amount;
                $buckets[$bucketName]['projects'][$project]['pings'] += $pings;
                $buckets[$bucketName]['projects'][$project]['count'] += $txCount;
            }

            $productKey = $sku . '|' . $series;
            if (!isset($buckets[$bucketName]['products'][$productKey])) {
                $buckets[$bucketName]['products'][$productKey] = ['sku' => $sku, 'series' => $series, 'productName' => $productName, 'amount' => 0, 'pings' => 0, 'count' => 0];
            }
            $buckets[$bucketName]['products'][$productKey]['amount'] += $amount;
            $buckets[$bucketName]['products'][$productKey]['pings'] += $pings;
            $buckets[$bucketName]['products'][$productKey]['count'] += $txCount;

            if (!isset($buckets[$bucketName]['series'][$series])) $buckets[$bucketName]['series'][$series] = ['name' => $series, 'amount' => 0, 'pings' => 0, 'count' => 0];
            $buckets[$bucketName]['series'][$series]['amount'] += $amount;
            $buckets[$bucketName]['series'][$series]['pings'] += $pings;
            $buckets[$bucketName]['series'][$series]['count'] += $txCount;

            $buckets[$bucketName]['total'] += $amount;
            $buckets[$bucketName]['pings'] += $pings;
            $buckets[$bucketName]['count'] += $txCount;
            if ($hasProject) {
                if (!isset($buckets[$bucketName]['projectTotal'])) $buckets[$bucketName]['projectTotal'] = 0;
                $buckets[$bucketName]['projectTotal'] += $amount;
            }
            if ($profile && (!empty($profile['isSleeper']) || !empty($profile['isDiscontinued']))) {
                $buckets[$bucketName]['sleeperTotal'] += $amount;
            }
        }

        $sortDesc = function (&$arr) {
            uasort($arr, function ($a, $b) { return $b['amount'] <=> $a['amount']; });
        };
        foreach (['current', 'previous', 'yoy'] as $bucketName) {
            $sortDesc($buckets[$bucketName]['sales']);
            $sortDesc($buckets[$bucketName]['customers']);
            $sortDesc($buckets[$bucketName]['projects']);
            $sortDesc($buckets[$bucketName]['products']);
            $sortDesc($buckets[$bucketName]['series']);
        }

        $currentSummary = $this->buildStrategySummaryFromBuckets(
            $buckets['current']['sales'],
            $buckets['current']['customers'],
            $buckets['current']['total'],
            $buckets['current']['pings'],
            $buckets['current']['count']
        );
        $prevSummary = $this->buildStrategySummaryFromBuckets(
            $buckets['previous']['sales'],
            $buckets['previous']['customers'],
            $buckets['previous']['total'],
            $buckets['previous']['pings'],
            $buckets['previous']['count']
        );
        $yoySummary = $this->buildStrategySummaryFromBuckets(
            $buckets['yoy']['sales'],
            $buckets['yoy']['customers'],
            $buckets['yoy']['total'],
            $buckets['yoy']['pings'],
            $buckets['yoy']['count']
        );
        $currentSummary['sleeperSales'] = round($buckets['current']['sleeperTotal']);
        $currentSummary['sleeperPct'] = $currentSummary['total'] > 0 ? round($buckets['current']['sleeperTotal'] / $currentSummary['total'] * 100, 1) : 0;
        $currentSummary['projectSales'] = round($buckets['current']['projectTotal']);
        $currentSummary['projectPct'] = $currentSummary['total'] > 0 ? round($buckets['current']['projectTotal'] / $currentSummary['total'] * 100, 1) : 0;
        $prevSummary['sleeperSales'] = round($buckets['previous']['sleeperTotal']);
        $prevSummary['sleeperPct'] = $prevSummary['total'] > 0 ? round($buckets['previous']['sleeperTotal'] / $prevSummary['total'] * 100, 1) : 0;
        $prevSummary['projectSales'] = round($buckets['previous']['projectTotal']);
        $prevSummary['projectPct'] = $prevSummary['total'] > 0 ? round($buckets['previous']['projectTotal'] / $prevSummary['total'] * 100, 1) : 0;
        $yoySummary['sleeperSales'] = round($buckets['yoy']['sleeperTotal']);
        $yoySummary['sleeperPct'] = $yoySummary['total'] > 0 ? round($buckets['yoy']['sleeperTotal'] / $yoySummary['total'] * 100, 1) : 0;
        $yoySummary['projectSales'] = round($buckets['yoy']['projectTotal']);
        $yoySummary['projectPct'] = $yoySummary['total'] > 0 ? round($buckets['yoy']['projectTotal'] / $yoySummary['total'] * 100, 1) : 0;

        $fieldCurrent = $this->buildFieldActivityReport($meta, $buckets['current']);
        $fieldPrevious = $this->buildFieldActivityReport($prevMeta, $buckets['previous']);
        $fieldYoy = $this->buildFieldActivityReport($yoyMeta, $buckets['yoy']);

        $topSales = array_values(array_slice($buckets['current']['sales'], 0, 8));
        $topCustomers = array_values(array_slice($buckets['current']['customers'], 0, 10));
        $topProjects = array_values(array_slice($buckets['current']['projects'], 0, 10));
        $topProducts = array_values(array_slice($buckets['current']['products'], 0, 10));
        $topSeries = array_values(array_slice($buckets['current']['series'], 0, 6));

        $topProduct = count($topProducts) ? $topProducts[0] : null;
        $growthSales = $this->buildDeltaLeaders($buckets['current']['sales'], $buckets['previous']['sales'], 'sales');
        $growthCustomers = $this->buildDeltaLeaders($buckets['current']['customers'], $buckets['previous']['customers'], 'customer');
        $growthProjects = $this->buildDeltaLeaders($buckets['current']['projects'], $buckets['previous']['projects'], 'project');
        $growthProducts = $this->buildDeltaLeaders($buckets['current']['products'], $buckets['previous']['products'], 'product');

        return [
            'success' => true,
            'data' => [
                'mode' => $meta['mode'],
                'year' => (int)$meta['year'],
                'period' => (int)$meta['period'],
                'months' => $meta['months'],
                'label' => $meta['label'],
                'previousLabel' => $prevMeta['label'],
                'yoyLabel' => $yoyMeta['label'],
                'summary' => $currentSummary,
                'bases' => [
                    'previous' => $prevSummary,
                    'yoy' => $yoySummary
                ],
                'comparisons' => [
                    'primary' => $this->buildStrategyCompare($currentSummary, $prevSummary, $meta['primaryLabel']),
                    'yoy' => $this->buildStrategyCompare($currentSummary, $yoySummary, 'YOY')
                ],
                'topSales' => $topSales,
                'topCustomers' => $topCustomers,
                'topProjects' => $topProjects,
                'topProducts' => $topProducts,
                'topSeries' => $topSeries,
                'growthSales' => $growthSales,
                'growthCustomers' => $growthCustomers,
                'growthProjects' => $growthProjects,
                'growthProducts' => $growthProducts,
                'monthTrend' => $monthTrend,
                'trailingTrend' => array_values($trailingTrendMap),
                'fieldActivity' => $fieldCurrent,
                'fieldBases' => [
                    'previous' => $fieldPrevious['summary'] ?? [],
                    'yoy' => $fieldYoy['summary'] ?? []
                ],
                'fieldComparisons' => [
                    'primary' => $this->buildFieldActivityCompare($fieldCurrent['summary'] ?? [], $fieldPrevious['summary'] ?? [], $meta['primaryLabel']),
                    'yoy' => $this->buildFieldActivityCompare($fieldCurrent['summary'] ?? [], $fieldYoy['summary'] ?? [], 'YOY')
                ],
                'insights' => [
                    'leader' => count($topSales) ? $topSales[0]['name'] . ' 目前領先，' . (int)floor($topSales[0]['amount'] / 10000) . ' 萬。' : '本期尚無業務資料。',
                    'concentration' => '前 3 業務占比 ' . (int)floor($currentSummary['top3SalesPct']) . '%，最大客戶占比 ' . (int)floor($currentSummary['topCustomerPct']) . '%。',
                    'customer' => count($topCustomers) ? '最大客戶 ' . mb_substr($topCustomers[0]['name'], 0, 8, 'UTF-8') . '，貢獻 ' . (int)floor($topCustomers[0]['amount'] / 10000) . ' 萬。' : '本期尚無客戶資料。',
                    'product' => $topProduct ? '熱銷產品 ' . $topProduct['sku'] . '，' . (int)floor($topProduct['amount'] / 10000) . ' 萬。' : '本期尚無產品資料。'
                ]
            ]
        ];
    }

    private function buildFieldActivityReport($meta, $currentBucket)
    {
        $gsLayout = new GoogleSheetsClient(SS_ID_LAYOUT);
        $workRows = $gsLayout->readSheet('智能_工作日誌');
        $driveRows = $gsLayout->readSheet('智能_行駛日誌');

        $salesByCustomer = [];
        foreach (($currentBucket['customers'] ?? []) as $name => $row) {
            $salesByCustomer[$name] = $row['amount'] ?? 0;
        }
        $salesByRep = [];
        foreach (($currentBucket['sales'] ?? []) as $name => $row) {
            $salesByRep[$name] = $row['amount'] ?? 0;
        }

        $visitByCustomer = [];
        $visitByRep = [];
        $taskTypeCounts = [];
        $customerLastVisit = [];
        $allVisitPairs = [];

        if (count($workRows) >= 2) {
            $h = $workRows[0];
            $idxDate = $this->findHeader($h, ['日期']);
            $idxSales = $this->findHeader($h, ['業務姓名', '業務']);
            $idxCustomer = $this->findHeader($h, ['客戶名稱', '客戶']);
            $idxTask = $this->findHeader($h, ['任務摘要', '摘要']);
            for ($i = 1; $i < count($workRows); $i++) {
                $row = $workRows[$i];
                $d = $this->parseDate($this->getVal($row, $idxDate));
                if (!$d) continue;
                if (!$this->matchesStrategyPeriod((int)$d->format('Y'), (int)$d->format('n'), $meta)) continue;

                $sales = $this->normalizeSalesRep($this->getVal($row, $idxSales));
                if (isset(self::$salesMerge[$sales])) $sales = self::$salesMerge[$sales];
                if ($sales === '') $sales = '未指定';

                $taskLabel = $this->classifyTaskLabel($this->getVal($row, $idxTask));
                if (!isset($taskTypeCounts[$taskLabel])) $taskTypeCounts[$taskLabel] = ['name' => $taskLabel, 'amount' => 0];
                $taskTypeCounts[$taskLabel]['amount'] += 1;

                if (!isset($visitByRep[$sales])) {
                    $visitByRep[$sales] = [
                        'name' => $sales, 'visits' => 0, 'customerSet' => [], 'daySet' => [],
                        'salesAmount' => isset($salesByRep[$sales]) ? $salesByRep[$sales] : 0,
                        'km' => 0, 'fuelAmount' => 0, 'fuelLiters' => 0
                    ];
                }
                $visitByRep[$sales]['daySet'][$d->format('Y-m-d')] = true;

                $customers = $this->parseWorkLogCustomers($this->getVal($row, $idxCustomer));
                foreach ($customers as $customer) {
                    if (!isset($visitByCustomer[$customer])) {
                        $visitByCustomer[$customer] = [
                            'name' => $customer,
                            'visits' => 0,
                            'salesAmount' => isset($salesByCustomer[$customer]) ? $salesByCustomer[$customer] : 0,
                            'repSet' => [],
                            'lastVisit' => '',
                            'daySet' => []
                        ];
                    }
                    $visitByCustomer[$customer]['visits'] += 1;
                    $visitByCustomer[$customer]['repSet'][$sales] = true;
                    $visitByCustomer[$customer]['daySet'][$d->format('Y-m-d')] = true;
                    $visitByCustomer[$customer]['lastVisit'] = max($visitByCustomer[$customer]['lastVisit'], $d->format('Y-m-d'));
                    $visitByRep[$sales]['visits'] += 1;
                    $visitByRep[$sales]['customerSet'][$customer] = true;
                    $customerLastVisit[$customer] = max($customerLastVisit[$customer] ?? '', $d->format('Y-m-d'));
                    $allVisitPairs[$customer] = ['x' => $visitByCustomer[$customer]['visits'], 'y' => $visitByCustomer[$customer]['salesAmount']];
                }
            }
        }

        if (count($driveRows) >= 2) {
            $h = $driveRows[0];
            $idxDate = $this->findHeader($h, ['日期']);
            $idxSales = $this->findHeader($h, ['業務姓名', '業務']);
            $idxKm = $this->findHeader($h, ['行駛距離', '距離']);
            $idxFuelLiters = $this->findHeader($h, ['加油公升']);
            $idxFuelAmount = $this->findHeader($h, ['加油金額', '油資']);
            for ($i = 1; $i < count($driveRows); $i++) {
                $row = $driveRows[$i];
                $d = $this->parseDate($this->getVal($row, $idxDate));
                if (!$d) continue;
                if (!$this->matchesStrategyPeriod((int)$d->format('Y'), (int)$d->format('n'), $meta)) continue;

                $sales = $this->normalizeSalesRep($this->getVal($row, $idxSales));
                if (isset(self::$salesMerge[$sales])) $sales = self::$salesMerge[$sales];
                if ($sales === '') $sales = '未指定';
                if (!isset($visitByRep[$sales])) {
                    $visitByRep[$sales] = [
                        'name' => $sales, 'visits' => 0, 'customerSet' => [], 'daySet' => [],
                        'salesAmount' => isset($salesByRep[$sales]) ? $salesByRep[$sales] : 0,
                        'km' => 0, 'fuelAmount' => 0, 'fuelLiters' => 0
                    ];
                }
                $visitByRep[$sales]['km'] += $this->optFloat($this->getVal($row, $idxKm));
                $visitByRep[$sales]['fuelLiters'] += $this->optFloat($this->getVal($row, $idxFuelLiters));
                $visitByRep[$sales]['fuelAmount'] += $this->optFloat($this->getVal($row, $idxFuelAmount));
                $visitByRep[$sales]['daySet'][$d->format('Y-m-d')] = true;
            }
        }

        foreach ($visitByCustomer as &$item) {
            $item['visitDays'] = count($item['daySet']);
            $item['repCount'] = count($item['repSet']);
            $item['salesPerVisit'] = $this->safeRatio($item['salesAmount'], max(1, $item['visits']));
            unset($item['daySet'], $item['repSet']);
        }
        unset($item);

        foreach ($visitByRep as &$item) {
            $item['customerCount'] = count($item['customerSet']);
            $item['activeDays'] = count($item['daySet']);
            $item['salesPerVisit'] = $this->safeRatio($item['salesAmount'], max(1, $item['visits']));
            $item['salesPerKm'] = $this->safeRatio($item['salesAmount'], max(1, $item['km']));
            $item['costPerKm'] = $this->safeRatio($item['fuelAmount'], max(1, $item['km']));
            $item['kmPerLiter'] = $this->safeRatio($item['km'], max(1, $item['fuelLiters']));
            unset($item['customerSet'], $item['daySet']);
        }
        unset($item);

        foreach ($salesByCustomer as $customer => $amount) {
            if (!isset($visitByCustomer[$customer])) {
                $visitByCustomer[$customer] = [
                    'name' => $customer,
                    'visits' => 0,
                    'salesAmount' => $amount,
                    'lastVisit' => $customerLastVisit[$customer] ?? '',
                    'visitDays' => 0,
                    'repCount' => 0,
                    'salesPerVisit' => $amount
                ];
                $allVisitPairs[$customer] = ['x' => 0, 'y' => $amount];
            }
        }

        $customerRows = array_values($visitByCustomer);
        usort($customerRows, function ($a, $b) {
            return ($b['visits'] <=> $a['visits']) ?: ($b['salesAmount'] <=> $a['salesAmount']);
        });
        $underVisited = array_values(array_filter($visitByCustomer, function ($row) {
            return ($row['salesAmount'] ?? 0) > 0 && ($row['visits'] ?? 0) <= 1;
        }));
        usort($underVisited, function ($a, $b) {
            return ($b['salesAmount'] <=> $a['salesAmount']) ?: ($a['visits'] <=> $b['visits']);
        });
        $highVisitLowSales = array_values(array_filter($visitByCustomer, function ($row) {
            return ($row['visits'] ?? 0) >= 2;
        }));
        usort($highVisitLowSales, function ($a, $b) {
            return ($b['visits'] <=> $a['visits']) ?: ($a['salesAmount'] <=> $b['salesAmount']);
        });

        $repRows = array_values($visitByRep);
        foreach ($repRows as &$row) {
            if (!isset($row['salesAmount']) || !$row['salesAmount']) $row['salesAmount'] = $salesByRep[$row['name']] ?? 0;
        }
        unset($row);
        usort($repRows, function ($a, $b) {
            return ($b['salesAmount'] <=> $a['salesAmount']) ?: ($b['visits'] <=> $a['visits']);
        });

        $taskRows = array_values($taskTypeCounts);
        usort($taskRows, function ($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });

        $totalVisits = 0;
        $totalKm = 0;
        $totalFuelAmount = 0;
        $totalFuelLiters = 0;
        foreach ($repRows as $row) {
            $totalVisits += $row['visits'] ?? 0;
            $totalKm += $row['km'] ?? 0;
            $totalFuelAmount += $row['fuelAmount'] ?? 0;
            $totalFuelLiters += $row['fuelLiters'] ?? 0;
        }

        return [
            'summary' => [
                'totalVisits' => $totalVisits,
                'visitedCustomers' => count(array_filter($customerRows, function ($row) { return ($row['visits'] ?? 0) > 0; })),
                'totalKm' => $totalKm,
                'fuelAmount' => $totalFuelAmount,
                'fuelLiters' => $totalFuelLiters,
                'salesPerKm' => $this->safeRatio($currentBucket['total'] ?? 0, max(1, $totalKm)),
                'salesPerVisit' => $this->safeRatio($currentBucket['total'] ?? 0, max(1, $totalVisits)),
                'visitSalesCorrelation' => $this->calcPearson(array_values($allVisitPairs))
            ],
            'topVisitedCustomers' => array_slice($customerRows, 0, 10),
            'underVisitedCustomers' => array_slice($underVisited, 0, 10),
            'highVisitLowSalesCustomers' => array_slice($highVisitLowSales, 0, 10),
            'repEfficiency' => array_slice($repRows, 0, 10),
            'taskMix' => array_slice($taskRows, 0, 8),
            'allCustomers' => $customerRows,
            'allReps' => $repRows
        ];
    }

    private function getContractMeetingSummary($year, $month)
    {
        $rows = $this->gs->readSheet('合約');
        if (count($rows) < 2) {
            return [
                'healthCounts' => [],
                'summary' => ['active' => 0, 'expiringSoon' => 0, 'overdue' => 0, 'balance' => 0, 'signedMonthlyTarget' => 0],
                'topRisk' => [],
                'notes' => [],
                'signedCustomers' => [],
                'detailGroups' => [
                    'normal' => [],
                    'overdueSevere' => [],
                    'pendingRenewal' => [],
                    'renewed' => [],
                    'otherOpen' => []
                ]
            ];
        }

        $h = $rows[0];
        $idxHealth = $this->findHeader($h, ['健康度']);
        $idxCustomer = $this->findHeader($h, ['客戶']);
        $idxContractAmt = $this->findHeader($h, ['合約內容']);
        $idxLastDue = $this->findHeader($h, ['最後一張票期']);
        $idxBalance = -1;
        foreach ($h as $i => $col) {
            if (mb_strpos((string)$col, '餘額') !== false) $idxBalance = $i;
        }
        $idxSales = $this->findHeader($h, ['業務']);

        $healthCounts = [
            '正常' => ['name' => '正常', 'count' => 0],
            '逾期' => ['name' => '逾期', 'count' => 0],
            '嚴重' => ['name' => '嚴重', 'count' => 0],
            '待續' => ['name' => '待續', 'count' => 0],
            '已續' => ['name' => '已續', 'count' => 0],
            '其它未續約' => ['name' => '其它未續約', 'count' => 0],
            '未分類' => ['name' => '未分類', 'count' => 0]
        ];
        $topRisk = [];
        $notes = [];
        $detailGroups = [
            'normal' => [],
            'overdueSevere' => [],
            'pendingRenewal' => [],
            'renewed' => [],
            'otherOpen' => []
        ];
        $active = 0;
        $expiringSoon = 0;
        $overdue = 0;
        $balance = 0;
        $signedMonthlyTarget = 0;
        $signedCustomers = [];
        $targetMonth = new DateTime(sprintf('%04d-%02d-01', $year, $month));
        $today = new DateTime('today');

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $health = trim($this->getVal($row, $idxHealth));
            $customer = $this->displayCustomerName($this->getVal($row, $idxCustomer));
            $bal = $idxBalance !== -1 ? $this->optFloat($this->getVal($row, $idxBalance)) : 0;
            $sales = trim($this->getVal($row, $idxSales));
            if ($customer === '未知客戶') continue;

            $healthMeta = $this->normalizeContractHealthLabel($health);
            $bucket = $healthMeta['bucket'];
            if (!isset($healthCounts[$bucket])) $healthCounts[$bucket] = ['name' => $bucket, 'count' => 0];
            $healthCounts[$bucket]['count'] += 1;
            $active += 1;
            $balance += $bal;
            $contractAmt = $idxContractAmt !== -1 ? $this->optFloat($this->getVal($row, $idxContractAmt)) : 0;
            if (in_array($bucket, ['正常', '逾期', '嚴重', '待續', '已續'], true)) {
                $signedMonthlyTarget += $contractAmt;
                $signedCustomers[$customer] = true;
            }
            if (in_array($bucket, ['逾期', '嚴重'], true)) $overdue += 1;

            $due = $this->parseDate($this->getVal($row, $idxLastDue));
            if ($due) {
                $diffDays = (int)$targetMonth->diff($due)->format('%r%a');
                if ($diffDays >= 0 && $diffDays <= 45) $expiringSoon += 1;
            }

            $elapsedText = '未填票期';
            if ($due) {
                $todayDiff = (int)$today->diff($due)->format('%r%a');
                if ($todayDiff < 0) $elapsedText = '逾期 ' . abs($todayDiff) . ' 天';
                elseif ($todayDiff > 0) $elapsedText = $todayDiff . ' 天後到期';
                else $elapsedText = '今天到期';
            }

            if ($healthMeta['note'] !== '') {
                $notes[$healthMeta['note']] = isset($notes[$healthMeta['note']]) ? $notes[$healthMeta['note']] + 1 : 1;
            }

            $detailRow = [
                'customer' => $customer,
                'health' => $bucket,
                'balance' => $bal,
                'sales' => $sales,
                'lastDue' => $due ? $due->format('Y/m/d') : '',
                'elapsed' => $elapsedText,
                'note' => $healthMeta['note']
            ];

            if (in_array($bucket, ['逾期', '嚴重'], true)) $detailGroups['overdueSevere'][] = $detailRow;
            elseif ($bucket === '待續') $detailGroups['pendingRenewal'][] = $detailRow;
            elseif ($bucket === '已續') $detailGroups['renewed'][] = $detailRow;
            elseif ($bucket === '正常') $detailGroups['normal'][] = $detailRow;
            elseif ($bucket === '其它未續約') $detailGroups['otherOpen'][] = $detailRow;

            if (in_array($bucket, ['逾期', '嚴重', '待續'], true) || $bal > 300000) {
                $topRisk[] = [
                    'customer' => $customer,
                    'health' => $bucket,
                    'balance' => $bal,
                    'sales' => $sales,
                    'lastDue' => $due ? $due->format('Y/m/d') : ''
                ];
            }
        }

        usort($topRisk, function ($a, $b) {
            return ($b['balance'] <=> $a['balance']);
        });
        foreach ($detailGroups as &$group) {
            usort($group, function ($a, $b) {
                return ($b['balance'] <=> $a['balance']);
            });
        }
        unset($group);
        $noteRows = [];
        foreach ($notes as $text => $count) {
            $noteRows[] = ['name' => $text, 'count' => $count];
        }
        usort($noteRows, function ($a, $b) {
            return ($b['count'] <=> $a['count']);
        });

        return [
            'healthCounts' => array_values($healthCounts),
            'summary' => [
                'active' => $active,
                'expiringSoon' => $expiringSoon,
                'overdue' => $overdue,
                'balance' => $balance,
                'signedMonthlyTarget' => $signedMonthlyTarget
            ],
            'topRisk' => array_slice($topRisk, 0, 10),
            'notes' => array_slice($noteRows, 0, 12),
            'signedCustomers' => array_values(array_keys($signedCustomers)),
            'detailGroups' => [
                'normal' => array_slice($detailGroups['normal'], 0, 30),
                'overdueSevere' => array_slice($detailGroups['overdueSevere'], 0, 30),
                'pendingRenewal' => array_slice($detailGroups['pendingRenewal'], 0, 30),
                'renewed' => array_slice($detailGroups['renewed'], 0, 30),
                'otherOpen' => array_slice($detailGroups['otherOpen'], 0, 30)
            ]
        ];
    }

    public function getMeetingReport($year, $month)
    {
        $year = (int)$year;
        $month = (int)$month;
        if ($month < 1 || $month > 12) $month = (int)date('n');

        $strategyRes = $this->getStrategyReport($year, $month, 'month');
        if (!$strategyRes['success']) return $strategyRes;
        $strategy = $strategyRes['data'];

        $profiles = $this->getProductProfileMap();
        $cacheRows = $this->gs->readSheet(CACHE_SHEET);
        if (count($cacheRows) < 2) {
            return ['success' => false, 'msg' => '找不到產品年度銷售快取，請先同步銷售快取。'];
        }

        $h = $cacheRows[0];
        $idx = [
            'year' => $this->findHeader($h, ['年度']),
            'month' => $this->findHeader($h, ['月份']),
            'sku' => $this->findHeader($h, ['產品編號']),
            'customer' => $this->findHeader($h, ['客戶名稱']),
            'project' => $this->findHeader($h, ['專案名稱']),
            'sales' => $this->findHeader($h, ['負責業務', '業務']),
            'amount' => $this->findHeader($h, ['銷售金額']),
            'pings' => $this->findHeader($h, ['銷售坪數']),
            'count' => $this->findHeader($h, ['交易筆數'])
        ];

        $monthTotals = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthTotals[$m] = ['current' => 0, 'previous' => 0];
        }
        $threeYearMonthTotals = [];
        for ($y = $year - 2; $y <= $year; $y++) {
            $threeYearMonthTotals[$y] = array_fill(1, 12, 0);
        }
        $meetingCurrentTotal = 0;
        $meetingYoyTotal = 0;
        $meetingCurrentPings = 0;
        $meetingCurrentTxCount = 0;
        $meetingSleeperTotal = 0;
        $meetingProjectTotal = 0;
        $currentCustomerAmounts = [];
        $countryRanks = [];
        $customerDetails = [];
        $salesCustomerBreakdown = [];
        $countryBrandRanks = ['義大利' => [], '西班牙' => []];
        $seriesRanks = [];
        $categoryRanks = [];
        $sizeRanks = [];
        $topProducts = [];

        for ($i = 1; $i < count($cacheRows); $i++) {
            $row = $cacheRows[$i];
            $rowYear = (int)$this->getVal($row, $idx['year']);
            $rowMonth = (int)$this->getVal($row, $idx['month']);
            $sku = $this->cleanSku($this->getVal($row, $idx['sku']));
            $customer = $idx['customer'] !== -1 ? $this->displayCustomerName($this->getVal($row, $idx['customer'])) : '未知客戶';
            $projectName = $idx['project'] !== -1 ? trim($this->getVal($row, $idx['project'])) : '';
            $sales = $idx['sales'] !== -1 ? $this->normalizeSalesRep($this->getVal($row, $idx['sales'])) : '未指定';
            if (isset(self::$salesMerge[$sales])) $sales = self::$salesMerge[$sales];
            if ($sales === '') $sales = '未指定';
            $amount = $this->optFloat($this->getVal($row, $idx['amount']));
            $pings = $this->optFloat($this->getVal($row, $idx['pings']));
            $txCount = (int)$this->getVal($row, $idx['count']);
            $profile = isset($profiles[$sku]) ? $profiles[$sku] : null;

            if ($rowYear === $year) $monthTotals[$rowMonth]['current'] += $amount;
            if ($rowYear === ($year - 1)) $monthTotals[$rowMonth]['previous'] += $amount;
            if (isset($threeYearMonthTotals[$rowYear])) $threeYearMonthTotals[$rowYear][$rowMonth] += $amount;
            if ($rowYear === ($year - 1) && $rowMonth === $month) {
                $meetingYoyTotal += $amount;
            }

            if ($rowYear !== $year || $rowMonth !== $month) continue;

            $meetingCurrentTotal += $amount;
            $meetingCurrentPings += $pings;
            $meetingCurrentTxCount += $txCount;
            if ($customer !== '未知客戶') {
                if (!isset($currentCustomerAmounts[$customer])) $currentCustomerAmounts[$customer] = 0;
                $currentCustomerAmounts[$customer] += $amount;
                if (!isset($customerDetails[$customer])) {
                    $customerDetails[$customer] = ['name' => $customer, 'items' => []];
                }
                $seriesCn = trim(($profile['seriesCn'] ?? '') ?: ($profile['series'] ?? '')) ?: '未分類系列';
                $detailKey = $seriesCn . '|' . $sku;
                if (!isset($customerDetails[$customer]['items'][$detailKey])) {
                    $customerDetails[$customer]['items'][$detailKey] = [
                        'seriesCn' => $seriesCn,
                        'sku' => $sku,
                        'name' => trim(($profile['productName'] ?? '') . ' ' . ($profile['size'] ?? '')),
                        'count' => 0,
                        'amount' => 0,
                        'pings' => 0
                    ];
                }
                $customerDetails[$customer]['items'][$detailKey]['count'] += $txCount;
                $customerDetails[$customer]['items'][$detailKey]['amount'] += $amount;
                $customerDetails[$customer]['items'][$detailKey]['pings'] += $pings;
            }
            if (!isset($salesCustomerBreakdown[$sales])) {
                $salesCustomerBreakdown[$sales] = ['name' => $sales, 'customers' => []];
            }
            if ($customer !== '未知客戶') {
                if (!isset($salesCustomerBreakdown[$sales]['customers'][$customer])) {
                    $salesCustomerBreakdown[$sales]['customers'][$customer] = ['name' => $customer, 'amount' => 0];
                }
                $salesCustomerBreakdown[$sales]['customers'][$customer]['amount'] += $amount;
            }
            if ($profile && (!empty($profile['isSleeper']) || !empty($profile['isDiscontinued']))) {
                $meetingSleeperTotal += $amount;
            }
            if ($projectName !== '') {
                $meetingProjectTotal += $amount;
            }
            $country = trim($profile['country'] ?? '') ?: '未分類';
            if (!isset($countryRanks[$country])) {
                $countryRanks[$country] = ['name' => $country, 'amount' => 0, 'pings' => 0, 'count' => 0];
            }
            $countryRanks[$country]['amount'] += $amount;
            $countryRanks[$country]['pings'] += $pings;
            $countryRanks[$country]['count'] += $txCount;
            if (isset($countryBrandRanks[$country])) {
                $brand = trim($profile['brand'] ?? '') ?: '未分類廠牌';
                $seriesName = trim(($profile['seriesCn'] ?? '') ?: ($profile['series'] ?? '')) ?: '未分類系列';
                if (!isset($countryBrandRanks[$country][$brand])) {
                    $countryBrandRanks[$country][$brand] = [
                        'name' => $brand,
                        'amount' => 0,
                        'pings' => 0,
                        'seriesSet' => [],
                        'seriesRows' => []
                    ];
                }
                $countryBrandRanks[$country][$brand]['amount'] += $amount;
                $countryBrandRanks[$country][$brand]['pings'] += $pings;
                $countryBrandRanks[$country][$brand]['seriesSet'][$seriesName] = true;
                if (!isset($countryBrandRanks[$country][$brand]['seriesRows'][$seriesName])) {
                    $countryBrandRanks[$country][$brand]['seriesRows'][$seriesName] = ['name' => $seriesName, 'amount' => 0, 'pings' => 0];
                }
                $countryBrandRanks[$country][$brand]['seriesRows'][$seriesName]['amount'] += $amount;
                $countryBrandRanks[$country][$brand]['seriesRows'][$seriesName]['pings'] += $pings;
            }

            $seriesKey = ($profile['brand'] ?? '') . '|' . ($profile['series'] ?? '') . '|' . ($profile['seriesCn'] ?? '');
            if (!isset($seriesRanks[$seriesKey])) {
                $seriesRanks[$seriesKey] = [
                    'brand' => $profile['brand'] ?? '',
                    'series' => $profile['series'] ?? '',
                    'seriesCn' => $profile['seriesCn'] ?? '',
                    'totalPings' => 0,
                    'totalAmount' => 0,
                    'items' => []
                ];
            }
            $seriesRanks[$seriesKey]['totalPings'] += $pings;
            $seriesRanks[$seriesKey]['totalAmount'] += $amount;
            if (!isset($seriesRanks[$seriesKey]['items'][$sku])) {
                $seriesRanks[$seriesKey]['items'][$sku] = [
                    'sku' => $sku,
                    'name' => trim(($profile['productName'] ?? '') . ' ' . ($profile['size'] ?? '')),
                    'pings' => 0,
                    'amount' => 0,
                    'imageUrl' => $profile['imageUrl'] ?? '',
                    'customers' => []
                ];
            }
            $seriesRanks[$seriesKey]['items'][$sku]['pings'] += $pings;
            $seriesRanks[$seriesKey]['items'][$sku]['amount'] += $amount;
            if ($customer !== '未知客戶') {
                if (!isset($seriesRanks[$seriesKey]['items'][$sku]['customers'][$customer])) {
                    $seriesRanks[$seriesKey]['items'][$sku]['customers'][$customer] = [
                        'name' => $customer,
                        'amount' => 0,
                        'pings' => 0
                    ];
                }
                $seriesRanks[$seriesKey]['items'][$sku]['customers'][$customer]['amount'] += $amount;
                $seriesRanks[$seriesKey]['items'][$sku]['customers'][$customer]['pings'] += $pings;
            }

            $category = trim($profile['category'] ?? '') ?: '未分類';
            if (!isset($categoryRanks[$category])) {
                $categoryRanks[$category] = ['name' => $category, 'amount' => 0, 'pings' => 0, 'count' => 0];
            }
            $categoryRanks[$category]['amount'] += $amount;
            $categoryRanks[$category]['pings'] += $pings;
            $categoryRanks[$category]['count'] += $txCount;

            $size = trim($profile['size'] ?? '') ?: '未標尺寸';
            if (!isset($sizeRanks[$size])) {
                $sizeRanks[$size] = ['name' => $size, 'amount' => 0, 'pings' => 0, 'count' => 0];
            }
            $sizeRanks[$size]['amount'] += $amount;
            $sizeRanks[$size]['pings'] += $pings;
            $sizeRanks[$size]['count'] += $txCount;

            if (!isset($topProducts[$sku])) {
                $topProducts[$sku] = [
                    'sku' => $sku,
                    'name' => trim($profile['productName'] ?? ''),
                    'series' => trim($profile['seriesCn'] ?: ($profile['series'] ?? '')),
                    'category' => $category,
                    'size' => $size,
                    'imageUrl' => $profile['imageUrl'] ?? '',
                    'amount' => 0,
                    'pings' => 0,
                    'count' => 0
                ];
            }
            $topProducts[$sku]['amount'] += $amount;
            $topProducts[$sku]['pings'] += $pings;
            $topProducts[$sku]['count'] += $txCount;
        }

        foreach ($seriesRanks as &$rank) {
            $rank['items'] = array_values($rank['items']);
            foreach ($rank['items'] as &$skuRow) {
                $skuRow['customers'] = array_values($skuRow['customers']);
                usort($skuRow['customers'], function ($a, $b) {
                    return $b['amount'] <=> $a['amount'];
                });
                foreach ($skuRow['customers'] as &$custRow) {
                    $custRow['sharePct'] = $skuRow['amount'] > 0 ? ($custRow['amount'] / $skuRow['amount'] * 100) : 0;
                }
                unset($custRow);
            }
            unset($skuRow);
            usort($rank['items'], function ($a, $b) {
                return $b['pings'] <=> $a['pings'];
            });
            $rank['sharePct'] = ($strategy['summary']['total'] ?? 0) > 0 ? ($rank['totalAmount'] / $strategy['summary']['total'] * 100) : 0;
        }
        unset($rank);
        usort($seriesRanks, function ($a, $b) {
            return $b['totalPings'] <=> $a['totalPings'];
        });
        $seriesRanks = array_slice(array_values($seriesRanks), 0, 12);
        usort($categoryRanks, function ($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        usort($sizeRanks, function ($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        foreach ($categoryRanks as &$row) {
            $row['sharePct'] = ($strategy['summary']['total'] ?? 0) > 0 ? ($row['amount'] / $strategy['summary']['total'] * 100) : 0;
        }
        unset($row);
        foreach ($sizeRanks as &$row) {
            $row['sharePct'] = ($strategy['summary']['total'] ?? 0) > 0 ? ($row['amount'] / $strategy['summary']['total'] * 100) : 0;
        }
        unset($row);
        foreach ($countryRanks as &$row) {
            $row['sharePct'] = ($strategy['summary']['total'] ?? 0) > 0 ? ($row['amount'] / $strategy['summary']['total'] * 100) : 0;
        }
        unset($row);
        usort($countryRanks, function ($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        foreach ($customerDetails as &$detail) {
            $detail['items'] = array_values($detail['items']);
            usort($detail['items'], function ($a, $b) {
                if ($a['seriesCn'] === $b['seriesCn']) return $b['amount'] <=> $a['amount'];
                return strcmp($a['seriesCn'], $b['seriesCn']);
            });
            $customerTotal = array_sum(array_map(function ($item) { return $item['amount']; }, $detail['items']));
            foreach ($detail['items'] as &$item) {
                $item['sharePct'] = $customerTotal > 0 ? ($item['amount'] / $customerTotal * 100) : 0;
            }
            unset($item);
        }
        unset($detail);
        foreach ($salesCustomerBreakdown as &$rep) {
            $rep['customers'] = array_values($rep['customers']);
            usort($rep['customers'], function ($a, $b) {
                return $b['amount'] <=> $a['amount'];
            });
            $repTotal = array_sum(array_map(function ($row) { return $row['amount']; }, $rep['customers']));
            $cum = 0;
            $cover = 0;
            foreach ($rep['customers'] as &$custRow) {
                $custRow['sharePct'] = $repTotal > 0 ? ($custRow['amount'] / $repTotal * 100) : 0;
                if ($cum < 80) {
                    $cum += $custRow['sharePct'];
                    $cover++;
                    $custRow['isCore80'] = true;
                } else {
                    $custRow['isCore80'] = false;
                }
            }
            unset($custRow);
            $rep['top80CustomerCount'] = $cover;
        }
        unset($rep);
        foreach ($countryBrandRanks as $country => &$brands) {
            $countryTotal = 0;
            foreach ($brands as $tmpRow) $countryTotal += $tmpRow['amount'];
            foreach ($brands as &$brandRow) {
                $brandRow['seriesCount'] = count($brandRow['seriesSet']);
                $brandRow['sharePct'] = $countryTotal > 0 ? ($brandRow['amount'] / $countryTotal * 100) : 0;
                $brandRow['seriesRows'] = array_values($brandRow['seriesRows']);
                usort($brandRow['seriesRows'], function ($a, $b) {
                    return $b['amount'] <=> $a['amount'];
                });
                $brandRow['seriesRows'] = array_values(array_filter($brandRow['seriesRows'], function ($row) {
                    return (float)($row['amount'] ?? 0) > 0;
                }));
                foreach ($brandRow['seriesRows'] as &$seriesRow) {
                    $seriesRow['sharePct'] = $brandRow['amount'] > 0 ? ($seriesRow['amount'] / $brandRow['amount'] * 100) : 0;
                }
                unset($seriesRow);
                unset($brandRow['seriesSet']);
            }
            unset($brandRow);
            usort($brands, function ($a, $b) {
                return $b['amount'] <=> $a['amount'];
            });
            $brands = array_slice(array_values($brands), 0, 8);
        }
        unset($brands);
        usort($topProducts, function ($a, $b) {
            return ($b['amount'] <=> $a['amount']) ?: ($b['pings'] <=> $a['pings']);
        });
        foreach ($topProducts as &$row) {
            $row['sharePct'] = ($strategy['summary']['total'] ?? 0) > 0 ? ($row['amount'] / $strategy['summary']['total'] * 100) : 0;
        }
        unset($row);

        $meetingSales = array_values(array_filter($strategy['topSales'] ?? [], function ($row) {
            return trim((string)($row['name'] ?? '')) !== '高弘治';
        }));

        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $cur = $monthTotals[$m]['current'];
            $prev = $monthTotals[$m]['previous'];
            $rows[] = [
                'month' => $m,
                'current' => $cur,
                'previous' => $prev,
                'yoyPct' => $prev > 0 ? (($cur - $prev) / $prev * 100) : 0
            ];
        }

        $contractSummary = $this->getContractMeetingSummary($year, $month);
        $summary = $strategy['summary'];
        $fieldCurrent = $strategy['fieldActivity'] ?? ['summary' => []];
        $visitMap = [];
        foreach (($fieldCurrent['allCustomers'] ?? []) as $visitRow) {
            $visitMap[$visitRow['name']] = $visitRow;
        }
        $meetingSalesYoyPct = $meetingYoyTotal > 0 ? (($meetingCurrentTotal - $meetingYoyTotal) / $meetingYoyTotal * 100) : 0;
        $meetingSleeperPct = $meetingCurrentTotal > 0 ? ($meetingSleeperTotal / $meetingCurrentTotal * 100) : 0;
        $meetingProjectPct = $meetingCurrentTotal > 0 ? ($meetingProjectTotal / $meetingCurrentTotal * 100) : 0;
        $catchUpPct = $meetingYoyTotal > 0 ? ($meetingCurrentTotal / $meetingYoyTotal * 100) : 0;
        $signedCustomerLookup = [];
        foreach (($contractSummary['signedCustomers'] ?? []) as $cust) {
            $signedCustomerLookup[$cust] = true;
        }
        $signedStoreSales = 0;
        $signedCustomerSalesRows = [];
        foreach ($currentCustomerAmounts as $cust => $amount) {
            if (isset($signedCustomerLookup[$cust])) {
                $signedStoreSales += $amount;
                $signedCustomerSalesRows[] = ['name' => $cust, 'amount' => $amount];
            }
        }
        usort($signedCustomerSalesRows, function ($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        $signedTarget = $contractSummary['summary']['signedMonthlyTarget'] ?? 0;
        $signedHealthPct = $signedTarget > 0 ? ($signedStoreSales / $signedTarget * 100) : 0;

        $shipmentBuckets = [
            ['name' => '5萬內', 'min' => 0, 'max' => 50000, 'count' => 0, 'amount' => 0],
            ['name' => '5~10萬', 'min' => 50000, 'max' => 100000, 'count' => 0, 'amount' => 0],
            ['name' => '10~20萬', 'min' => 100000, 'max' => 200000, 'count' => 0, 'amount' => 0],
            ['name' => '20萬以上', 'min' => 200000, 'max' => null, 'count' => 0, 'amount' => 0],
        ];
        $shipCustomerTotal = count($currentCustomerAmounts);
        foreach ($currentCustomerAmounts as $amount) {
            foreach ($shipmentBuckets as &$bucket) {
                $hit = $bucket['max'] === null
                    ? ($amount >= $bucket['min'])
                    : ($amount >= $bucket['min'] && $amount < $bucket['max']);
                if ($hit) {
                    $bucket['count'] += 1;
                    $bucket['amount'] += $amount;
                    break;
                }
            }
            unset($bucket);
        }
        foreach ($shipmentBuckets as &$bucket) {
            $bucket['customerSharePct'] = $shipCustomerTotal > 0 ? ($bucket['count'] / $shipCustomerTotal * 100) : 0;
            $bucket['salesSharePct'] = $meetingCurrentTotal > 0 ? ($bucket['amount'] / $meetingCurrentTotal * 100) : 0;
        }
        unset($bucket);
        foreach ($shipmentBuckets as &$bucket) {
            $bucket['customers'] = [];
        }
        foreach ($currentCustomerAmounts as $customerName => $amount) {
            foreach ($shipmentBuckets as &$bucket) {
                $hit = $bucket['max'] === null
                    ? ($amount >= $bucket['min'])
                    : ($amount >= $bucket['min'] && $amount < $bucket['max']);
                if ($hit) {
                    $visitRow = $visitMap[$customerName] ?? ['visits' => 0, 'visitDays' => 0];
                    $bucket['customers'][] = [
                        'name' => $customerName,
                        'shortName' => mb_substr($customerName, 0, 2, 'UTF-8'),
                        'amount' => $amount,
                        'visits' => $visitRow['visits'] ?? 0,
                        'visitDays' => $visitRow['visitDays'] ?? 0
                    ];
                    break;
                }
            }
            unset($bucket);
        }
        foreach ($shipmentBuckets as &$bucket) {
            usort($bucket['customers'], function ($a, $b) {
                return $b['amount'] <=> $a['amount'];
            });
            $visitTotal = array_sum(array_map(function ($row) { return $row['visits']; }, $bucket['customers']));
            $bucket['visitTotal'] = $visitTotal;
            $bucket['avgVisitsPerCustomer'] = $bucket['count'] > 0
                ? round($visitTotal / $bucket['count'], 1)
                : 0;
            $bucket['salesPerVisit'] = $visitTotal > 0
                ? round($bucket['amount'] / $visitTotal)
                : 0;
        }
        unset($bucket);

        $brandSales = [
            'italy' => ['name' => '義大利', 'amount' => 0],
            'spain' => ['name' => '西班牙', 'amount' => 0],
        ];
        foreach ($countryRanks as $row) {
            $countryName = trim((string)$row['name']);
            if ($countryName === '義大利') $brandSales['italy']['amount'] += $row['amount'];
            if ($countryName === '西班牙') $brandSales['spain']['amount'] += $row['amount'];
        }
        foreach ($brandSales as &$row) {
            $row['sharePct'] = $meetingCurrentTotal > 0 ? ($row['amount'] / $meetingCurrentTotal * 100) : 0;
        }
        unset($row);

        $threeYearCompare = [];
        $threeYearCumulative = [];
        foreach ($threeYearMonthTotals as $cmpYear => $months) {
            $cum = 0;
            $monthly = [];
            $cumulative = [];
            for ($m = 1; $m <= 12; $m++) {
                $val = (float)$months[$m];
                $cum += $val;
                $monthly[] = ['month' => $m, 'amount' => $val];
                if ($m <= $month) {
                    $cumulative[] = ['month' => $m, 'amount' => $cum];
                }
            }
            $threeYearCompare[] = ['year' => $cmpYear, 'months' => $monthly];
            $threeYearCumulative[] = ['year' => $cmpYear, 'months' => $cumulative, 'total' => $cum];
        }

        $meetingSales = array_map(function ($row) use ($salesCustomerBreakdown) {
            $name = $row['name'] ?? '';
            $row['customers'] = $salesCustomerBreakdown[$name]['customers'] ?? [];
            $row['top80CustomerCount'] = $salesCustomerBreakdown[$name]['top80CustomerCount'] ?? 0;
            return $row;
        }, $meetingSales);
        $topCustomersDetailed = array_map(function ($row) use ($customerDetails) {
            $name = $row['name'] ?? '';
            $row['items'] = $customerDetails[$name]['items'] ?? [];
            return $row;
        }, array_slice($strategy['topCustomers'] ?? [], 0, 10));

        return [
            'success' => true,
            'data' => [
                'year' => $year,
                'month' => $month,
                'label' => sprintf('%d.%d', $year, $month),
                'summary' => [
                    'sales' => round($meetingCurrentTotal),
                    'salesYoyBase' => round($meetingYoyTotal),
                    'salesYoyPct' => round($meetingSalesYoyPct, 1),
                    'catchUpPct' => round($catchUpPct, 1),
                    'pings' => round($meetingCurrentPings, 2),
                    'txCount' => (int)$meetingCurrentTxCount,
                    'avgTicket' => $meetingCurrentTxCount > 0 ? round($meetingCurrentTotal / $meetingCurrentTxCount) : 0,
                    'top3Pct' => $summary['top3SalesPct'] ?? 0,
                    'topCustomerPct' => $summary['topCustomerPct'] ?? 0,
                    'sleeperSales' => round($meetingSleeperTotal),
                    'sleeperPct' => round($meetingSleeperPct, 1),
                    'projectSales' => round($meetingProjectTotal),
                    'projectPct' => round($meetingProjectPct, 1),
                    'signedMonthlyTarget' => round($signedTarget),
                    'signedStoreSales' => round($signedStoreSales),
                    'signedHealthPct' => round($signedHealthPct, 1),
                    'shipCustomerCount' => $shipCustomerTotal
                ],
                'monthCompare' => $rows,
                'threeYearCompare' => $threeYearCompare,
                'threeYearCumulative' => $threeYearCumulative,
                'brandSales' => array_values($brandSales),
                'shipmentBuckets' => $shipmentBuckets,
                'signedCustomerSalesRows' => $signedCustomerSalesRows,
                'topCustomers' => $topCustomersDetailed,
                'topProjects' => array_slice($strategy['topProjects'] ?? [], 0, 10),
                'topSales' => array_slice($meetingSales, 0, 10),
                'seriesRanking' => $seriesRanks,
                'countryRanking' => array_values($countryRanks),
                'countryBrandRanking' => $countryBrandRanks,
                'categoryRanking' => array_values($categoryRanks),
                'sizeRanking' => array_values($sizeRanks),
                'topProductsDetailed' => array_slice(array_values($topProducts), 0, 12),
                'contracts' => $contractSummary,
                'fieldActivity' => $fieldCurrent,
                'insights' => $strategy['insights'] ?? []
            ]
        ];
    }

    public function getAiAdvisor($year, $month, $forceRefresh = false)
    {
        $year = (int)$year;
        $month = (int)$month;
        if (!is_dir(AI_ADVISOR_CACHE_DIR)) {
            @mkdir(AI_ADVISOR_CACHE_DIR, 0775, true);
        }
        $cacheFile = AI_ADVISOR_CACHE_DIR . "/advisor_{$year}_{$month}.json";

        if (!$forceRefresh && is_file($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached) {
                return ['success' => true, 'cached' => true, 'data' => $cached];
            }
        }

        if (GEMINI_API_KEY === '') {
            return ['success' => false, 'msg' => '尚未設定 GEMINI_API_KEY'];
        }

        $report = $this->getMeetingReport($year, $month);
        if (!($report['success'] ?? false)) {
            return ['success' => false, 'msg' => '無法取得月報資料: ' . ($report['msg'] ?? '')];
        }

        $inventoryHistory = $this->recordInventorySnapshot($year, $month);
        $summary = $this->buildAdvisorSummary($report['data']);
        $summary['inventoryHistory'] = $inventoryHistory;
        $this->recordReportSnapshot($year, $month, array_merge($summary, [
            'inventoryHistory' => array_combine(
                array_map(function ($r) { return sprintf('%d-%02d', $r['year'], $r['month']); }, $inventoryHistory),
                $inventoryHistory
            )
        ]));
        $sections = $this->callGeminiAdvisor($summary);

        $payload = [
            'generatedAt' => date('Y-m-d H:i:s'),
            'year' => $year,
            'month' => $month,
            'sections' => $sections
        ];
        file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE));
        return ['success' => true, 'cached' => false, 'data' => $payload];
    }

    private function buildAdvisorSummary($d)
    {
        $s = $d['summary'] ?? [];
        $monthCompare = array_filter($d['monthCompare'] ?? [], function ($r) use ($d) {
            return ($r['month'] ?? 0) <= ($d['month'] ?? 12);
        });
        $threeYear = $d['threeYearCompare'] ?? [];
        $brandSales = array_slice($d['brandSales'] ?? [], 0, 6);
        $countryBrand = [];
        foreach (($d['countryBrandRanking'] ?? []) as $country => $brands) {
            $countryBrand[$country] = array_map(function ($b) {
                return ['name' => $b['name'] ?? '', 'amount' => round($b['amount'] ?? 0), 'sharePct' => round($b['sharePct'] ?? 0, 1)];
            }, array_slice($brands, 0, 5));
        }
        $buckets = array_map(function ($row) {
            return [
                'name' => $row['name'] ?? '',
                'count' => $row['count'] ?? 0,
                'amount' => round($row['amount'] ?? 0),
                'customerSharePct' => round($row['customerSharePct'] ?? 0, 1),
                'salesSharePct' => round($row['salesSharePct'] ?? 0, 1),
                'avgVisitsPerCustomer' => round($row['avgVisitsPerCustomer'] ?? 0, 1),
                'salesPerVisit' => round($row['salesPerVisit'] ?? 0)
            ];
        }, $d['shipmentBuckets'] ?? []);
        $contracts = $d['contracts'] ?? [];
        $contractSummary = $contracts['summary'] ?? [];
        $healthCounts = $contracts['healthCounts'] ?? [];
        $topCustomers = array_map(function ($row) {
            return ['name' => $row['name'] ?? '', 'amount' => round($row['amount'] ?? 0), 'pings' => $row['pings'] ?? 0];
        }, array_slice($d['topCustomers'] ?? [], 0, 10));
        $topSales = array_map(function ($row) {
            return [
                'name' => $row['name'] ?? '',
                'amount' => round($row['amount'] ?? 0),
                'count' => $row['count'] ?? 0,
                'top80CustomerCount' => $row['top80CustomerCount'] ?? 0
            ];
        }, array_slice($d['topSales'] ?? [], 0, 10));
        $field = $d['fieldActivity'] ?? [];
        $fieldSummary = $field['summary'] ?? [];

        return [
            'year' => $d['year'],
            'month' => $d['month'],
            'kpi' => $s,
            'monthlyCompare' => array_values($monthCompare),
            'threeYearCompare' => array_map(function ($row) use ($d) {
                $months = array_filter($row['months'] ?? [], function ($m) use ($d) {
                    return ($m['month'] ?? 0) <= ($d['month'] ?? 12);
                });
                return [
                    'year' => $row['year'],
                    'total' => round(array_sum(array_map(function ($m) { return $m['amount'] ?? 0; }, $months)))
                ];
            }, $threeYear),
            'brandSales' => array_map(function ($r) {
                return ['name' => $r['name'] ?? '', 'amount' => round($r['amount'] ?? 0), 'sharePct' => round($r['sharePct'] ?? 0, 1)];
            }, $brandSales),
            'countryBrandRanking' => $countryBrand,
            'shipmentBuckets' => $buckets,
            'contractSummary' => [
                'active' => $contractSummary['active'] ?? 0,
                'expiringSoon' => $contractSummary['expiringSoon'] ?? 0,
                'balance' => round($contractSummary['balance'] ?? 0)
            ],
            'contractHealthCounts' => $healthCounts,
            'topCustomers' => $topCustomers,
            'topSales' => $topSales,
            'fieldSummary' => [
                'totalVisits' => $fieldSummary['totalVisits'] ?? 0,
                'visitedCustomers' => $fieldSummary['visitedCustomers'] ?? 0,
                'totalKm' => $fieldSummary['totalKm'] ?? 0,
                'salesPerVisit' => round($fieldSummary['salesPerVisit'] ?? 0)
            ],
            'topSeries' => array_map(function ($r) {
                return [
                    'name' => $r['seriesCn'] ?? $r['series'] ?? '未分類系列',
                    'brand' => $r['brand'] ?? '',
                    'amount' => round($r['totalAmount'] ?? 0),
                    'pings' => $r['totalPings'] ?? 0,
                    'sharePct' => round($r['sharePct'] ?? 0, 1)
                ];
            }, array_slice($d['seriesRanking'] ?? [], 0, 8)),
            'categoryRanking' => array_map(function ($r) {
                return ['name' => $r['name'] ?? '', 'amount' => round($r['amount'] ?? 0), 'sharePct' => round($r['sharePct'] ?? 0, 1)];
            }, $d['categoryRanking'] ?? []),
            'sizeRanking' => array_map(function ($r) {
                return ['name' => $r['name'] ?? '', 'amount' => round($r['amount'] ?? 0), 'sharePct' => round($r['sharePct'] ?? 0, 1)];
            }, $d['sizeRanking'] ?? [])
        ];
    }

    private function callGeminiAdvisor($summary)
    {
        $sectionSchema = [
            'type' => 'OBJECT',
            'properties' => [
                'level' => ['type' => 'STRING', 'enum' => ['good', 'warn', 'danger', 'info']],
                'title' => ['type' => 'STRING'],
                'points' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']]
            ],
            'required' => ['level', 'title', 'points']
        ];
        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'kpi' => $sectionSchema,
                'monthlyCompare' => $sectionSchema,
                'threeYear' => $sectionSchema,
                'brandCountry' => $sectionSchema,
                'health' => $sectionSchema,
                'contract' => $sectionSchema,
                'topCustomers' => $sectionSchema,
                'topSales' => $sectionSchema,
                'field' => $sectionSchema,
                'inventory' => $sectionSchema,
                'series' => $sectionSchema,
                'category' => $sectionSchema
            ],
            'required' => ['kpi', 'monthlyCompare', 'threeYear', 'brandCountry', 'health', 'contract', 'topCustomers', 'topSales', 'field', 'inventory', 'series', 'category']
        ];

        $context = "公司背景與口徑說明（分析時務必納入考量，不要把這些當成問題提出）：\n"
            . "1. 我們是 REFIN 總代理，主要進口歐洲（義大利、西班牙）磁磚，所以品牌與產地本來就會集中在少數幾個品牌/國家，這是正常且預期的結構，不是風險。\n"
            . "2. 「簽約店健康度」是指簽約店家本月實際銷售 / 簽約總額，因為簽約是先收合約票儲值，中南部很多客戶不簽約但仍會出貨，所以同行水準是「超過75%就算不錯」。signedHealthPct 在 70~85% 區間屬於正常偏好的範圍，不要當成警訊；只有明顯低於 60% 才需要留意。\n\n";

        $prompt = "你是一位資深的零售/經銷產業商業顧問，正在幫磁磚經銷商「高雅瓷」看月報數據。\n"
            . $context
            . "請用白話、口語化但專業的語氣，針對下面 JSON 數據的每個區塊，給出簡短的經營解讀與建議。\n"
            . "規則：\n"
            . "1. level 代表整體評價：good=表現良好、info=中性觀察、warn=需留意、danger=有明顯風險。\n"
            . "2. title 是一句結論標題（15字內），points 是 2~4 條具體分析，每條用一句話講清楚「數字代表什麼」+「該怎麼做」。\n"
            . "3. 重點數字可以用 **粗體** 標出（例如 **421萬**）。\n"
            . "4. 不要寫客套話或免責聲明，直接給結論。\n"
            . "5. inventory 區塊請根據 inventoryHistory（每月存貨總成本與坪數）分析存貨金額的月度變化趨勢，是否有異常增減。\n"
            . "6. series 區塊請針對 topSeries（熱銷系列）給出該主推或觀察哪些系列的建議；category 區塊請針對 categoryRanking / sizeRanking 分析產品大類與尺寸結構是否健康。\n"
            . "7. health 區塊請用 80/20 法則分析 shipmentBuckets：例如「20萬以上」家數佔比 vs 業績佔比的落差，代表業績集中在少數大客戶，要分析這代表的風險與機會，並建議該往哪個級距的客戶加強拜訪或開發。\n"
            . "8. 嚴格依照給定的 JSON schema 輸出，不要多餘文字。\n\n"
            . "資料如下：\n" . json_encode($summary, JSON_UNESCAPED_UNICODE);

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;
        $body = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
                'temperature' => 0.4
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE)
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new RuntimeException("Gemini API 錯誤 (HTTP {$httpCode}): {$resp}");
        }

        $result = json_decode($resp, true);
        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $sections = json_decode($text, true);
        if (!is_array($sections)) {
            throw new RuntimeException("Gemini 回應格式錯誤: {$text}");
        }
        return $sections;
    }

    public function getCustomerDetail($customer, $year = null)
    {
        $seriesMap = $this->getSeriesMap();
        $raw = $this->gs->readSheet(SALES_SHEET);
        if (count($raw) < 2) return ['success' => true, 'data' => []];

        $h = $raw[0];
        $idxDate  = $this->findHeader($h, ['日期','單據日期','銷貨日期']);
        $idxSales = $this->findHeader($h, ['負責業務','業務','業務員','負責人']);
        $idxCust  = $this->findHeader($h, ['客戶名稱','客戶']);
        $idxCode  = $this->findHeader($h, ['產品編號','編號','品碼','序號']);
        $idxQty   = $this->findHeader($h, ['數量','片數']);
        $idxAmt   = $this->findHeader($h, ['金額','銷額','銷售金額','成交金額','小計','總計']);
        $idxNote  = $this->findHeader($h, ['備註','說明','案名','專案','工地']);
        if ($idxCust === -1 || $idxDate === -1 || $idxCode === -1) return ['success' => true, 'data' => []];

        $results = [];
        for ($i = 1; $i < count($raw); $i++) {
            $row = $raw[$i];
            $custName = trim($this->getVal($row, $idxCust));
            if (strpos($custName, $customer) !== 0) continue;

            $d = $this->parseDate($this->getVal($row, $idxDate));
            if (!$d) continue;
            if ($year !== null && $d->format('Y') != $year) continue;

            $note = trim($this->getVal($row, $idxNote));
            if (strpos($note, '樣品') !== false || strpos($note, '扣帶') !== false) continue;

            $sku = $this->cleanSku($this->getVal($row, $idxCode));
            $qty = $this->optFloat($this->getVal($row, $idxQty));
            $amt = $qty > 0 ? $this->optFloat($this->getVal($row, $idxAmt)) : 0;
            if (!$sku || $qty == 0) continue;

            $results[] = [
                'date'   => $d->format('Y/m/d'),
                'month'  => $d->format('Y/m'),
                'sales'  => $idxSales !== -1 ? trim($this->getVal($row, $idxSales)) : '',
                'cust'   => $custName,
                'sku'    => $sku,
                'series' => isset($seriesMap[$sku]) ? $seriesMap[$sku] : '',
                'qty'    => $qty,
                'amt'    => round($amt),
                'note'   => $note
            ];
        }

        usort($results, function($a, $b) { return strcmp($a['date'], $b['date']); });

        return ['success' => true, 'data' => $results];
    }

    public function getSleeperProductOverview()
    {
        $configRes = $this->getSleeperConfig();
        if (!$configRes['success']) return $configRes;
        $sleeperMap = $configRes['data'];

        $displaysMap = $this->getActiveDisplaysMap();
        $stockMap = $this->getStockMap();
        $profileMap = $this->getProductProfileMap();
        $metaMap = $this->getMetaMap();

        $salesStats = $this->loadSalesStats($metaMap);
        $now = new DateTime();
        $products = [];

        foreach ($sleeperMap as $sku => $slp) {
            $profile = isset($profileMap[$sku]) ? $profileMap[$sku] : [];
            if (!empty($profile['isDiscontinued'])) continue;
            $meta = isset($metaMap[$sku]) ? $metaMap[$sku] : ['series' => '', 'perPing' => 36];
            $series = trim($profile['seriesCn'] ?? '') ?: trim($profile['series'] ?? '') ?: trim($meta['series'] ?? '');
            $stockPing = isset($stockMap[$sku]) ? $stockMap[$sku] : 0;
            $costPerPing = $slp['cost'] * ($meta['perPing'] ?: 36);
            $inventoryCost = round($stockPing * $costPerPing);
            $stats = isset($salesStats[$sku]) ? $salesStats[$sku] : null;

            $daysSinceLastSale = null;
            $lastSaleStr = '從未銷售';
            if ($stats && $stats['lastDate']) {
                $daysSinceLastSale = (int)$now->diff($stats['lastDate'])->days;
                $lastSaleStr = $this->formatRocDate($stats['lastDate'], false);
            }

            $totalPings = $stats ? round($stats['totalPings'] * 10) / 10 : 0;
            $customers = $this->buildProductCustomerRows($stats);
            $totalAmount = $stats ? round($stats['totalAmount']) : 0;
            $totalQty = $stats ? round($stats['totalQty']) : 0;
            $avgMarginPct = ($stats && $stats['totalAmount'] > 0)
                ? round((($stats['totalAmount'] - ($stats['totalQty'] * $slp['cost'])) / $stats['totalAmount']) * 100, 1)
                : 0;

            // Stagnancy diagnostics & actions
            $pings6M = $stats ? $stats['pings6M'] : 0;
            $monthlySpeedPings = $pings6M / 6;
            $mos = $monthlySpeedPings > 0 ? round(($stockPing / $monthlySpeedPings) * 10) / 10 : ($stockPing > 0 ? 888 : 0);

            $action = '正常去化';
            $actionColor = '#10b981'; // green
            $stagnantReason = '正常';
            $mosLevel = 0; // 0: benign, 1: observation, 2: promotion

            if ($daysSinceLastSale === null) {
                $action = '🔥 折扣促銷';
                $actionColor = '#ef4444'; // red
                $stagnantReason = '💀 從未銷售';
                $mosLevel = 2;
            } elseif ($daysSinceLastSale > 180) {
                $action = '🔥 折扣促銷';
                $actionColor = '#ef4444'; // red
                $stagnantReason = '💀 6M無交易';
                $mosLevel = 2;
            } elseif ($daysSinceLastSale > 90) {
                $action = '📝 觀察/加強推廣';
                $actionColor = '#f59e0b'; // yellow
                $stagnantReason = '🔇 3M無交易';
                $mosLevel = 1;
            } elseif ($mos > 12) {
                $action = '🔥 折扣促銷';
                $actionColor = '#ef4444'; // red
                $stagnantReason = '⚠️ 銷速過慢 (MOS > 12M)';
                $mosLevel = 2;
            } elseif ($mos > 6) {
                $action = '📝 觀察/加強推廣';
                $actionColor = '#f59e0b'; // yellow
                $stagnantReason = '🔸 銷速偏慢 (MOS > 6M)';
                $mosLevel = 1;
            }

            $activeDisplays = isset($displaysMap[$sku]) ? $displaysMap[$sku] : [];

            $products[] = [
                'sku' => $sku,
                'series' => $series,
                'productName' => $profile['productName'] ?? '',
                'size' => $profile['size'] ?? '',
                'grade' => $slp['grade'],
                'perPing' => $meta['perPing'],
                'stockPing' => round($stockPing * 10) / 10,
                'inventoryCost' => $inventoryCost,
                'totalPings' => $totalPings,
                'totalAmount' => $totalAmount,
                'totalQty' => $totalQty,
                'saleCount' => $stats ? $stats['count'] : 0,
                'daysSinceLastSale' => $daysSinceLastSale,
                'lastSaleStr' => $lastSaleStr,
                'avgMarginPct' => $avgMarginPct,
                'customers' => $customers,
                'imageUrl' => $profile['imageUrl'] ?? '',
                'mos' => $mos,
                'monthlySpeedPings' => round($monthlySpeedPings * 10) / 10,
                'action' => $action,
                'actionColor' => $actionColor,
                'stagnantReason' => $stagnantReason,
                'mosLevel' => $mosLevel,
                'displayCount' => count($activeDisplays),
                'displays' => $activeDisplays
            ];
        }

        usort($products, function($a, $b) { return $b['totalPings'] <=> $a['totalPings']; });

        return ['success' => true, 'data' => $products];
    }

    public function getDiscontinuedProductOverview()
    {
        $pData = $this->gs->readSheet(PRICE_SHEET);
        if (count($pData) < 2) return ['success' => true, 'data' => []];

        $pH = $pData[0];
        $pCode  = $this->findHeader($pH, ['編號','產品編號']);
        $pDisc  = $this->findHeader($pH, ['不續辦']);
        $pSer   = $this->findHeader($pH, ['中文系列','系列']);
        $pCost  = $this->findHeader($pH, ['成本','單片成本','成本價']);
        $pPerPing = $this->findHeader($pH, ['片/坪']);
        $pImg   = $this->findHeader($pH, ['單片連結網址','單片圖','圖片網址','單片網址','圖片連結']);
        if ($pCode === -1 || $pDisc === -1) return ['success' => true, 'data' => []];

        $disconMap = [];
        for ($i = 1; $i < count($pData); $i++) {
            $row = $pData[$i];
            $sku = $this->cleanSku($this->getVal($row, $pCode));
            if (!$sku) continue;
            if (trim($this->getVal($row, $pDisc)) === '') continue;
            $seriesVal = $pSer !== -1 ? trim($this->getVal($row, $pSer)) : '';
            $disconMap[$sku] = [
                'series'   => $seriesVal,
                'cost'     => $pCost !== -1 ? $this->optFloat($this->getVal($row, $pCost)) : 0,
                'perPing'  => $pPerPing !== -1 ? ($this->optFloat($this->getVal($row, $pPerPing)) ?: 36) : 36,
                'imageUrl' => $pImg !== -1 ? trim($this->getVal($row, $pImg)) : ''
            ];
        }

        $sleeperCosts = $this->getSleeperCostMap();
        foreach ($disconMap as $sku => &$info) {
            if ($info['cost'] <= 0 && isset($sleeperCosts[$sku]) && $sleeperCosts[$sku] > 0) {
                $info['cost'] = $sleeperCosts[$sku];
            }
        }
        unset($info);

        $displaysMap = $this->getActiveDisplaysMap();
        $stockMap = $this->getStockMap();
        $metaMap = $this->getMetaMap();
        $profileMap = $this->getProductProfileMap();
        $salesStats = $this->loadSalesStats($metaMap);

        $now = new DateTime();
        $products = [];
        foreach ($disconMap as $sku => $info) {
            $stockPing = isset($stockMap[$sku]) ? $stockMap[$sku] : 0;
            $costPerPing = $info['cost'] * ($info['perPing'] ?: 36);
            $inventoryCost = round($stockPing * $costPerPing);
            $stats = isset($salesStats[$sku]) ? $salesStats[$sku] : null;

            $daysSinceLastSale = null;
            $lastSaleStr = '從未銷售';
            if ($stats && $stats['lastDate']) {
                $daysSinceLastSale = (int)$now->diff($stats['lastDate'])->days;
                $lastSaleStr = $this->formatRocDate($stats['lastDate'], false);
            }

            $totalPings = $stats ? round($stats['totalPings'] * 10) / 10 : 0;
            $customers = $this->buildProductCustomerRows($stats);
            $totalAmount = $stats ? round($stats['totalAmount']) : 0;
            $totalQty = $stats ? round($stats['totalQty']) : 0;
            $avgMarginPct = ($stats && $stats['totalAmount'] > 0)
                ? round((($stats['totalAmount'] - ($stats['totalQty'] * $info['cost'])) / $stats['totalAmount']) * 100, 1)
                : 0;

            // Stagnancy diagnostics & actions
            $pings6M = $stats ? $stats['pings6M'] : 0;
            $monthlySpeedPings = $pings6M / 6;
            $mos = $monthlySpeedPings > 0 ? round(($stockPing / $monthlySpeedPings) * 10) / 10 : ($stockPing > 0 ? 888 : 0);

            $action = '正常去化';
            $actionColor = '#10b981'; // green
            $stagnantReason = '正常';
            $mosLevel = 0;

            if ($daysSinceLastSale === null) {
                $action = '🔥 折扣促銷';
                $actionColor = '#ef4444';
                $stagnantReason = '💀 從未銷售';
                $mosLevel = 2;
            } elseif ($daysSinceLastSale > 180) {
                $action = '🔥 折扣促銷';
                $actionColor = '#ef4444';
                $stagnantReason = '💀 6M無交易';
                $mosLevel = 2;
            } elseif ($daysSinceLastSale > 90) {
                $action = '📝 觀察/加強推廣';
                $actionColor = '#f59e0b';
                $stagnantReason = '🔇 3M無交易';
                $mosLevel = 1;
            } elseif ($mos > 12) {
                $action = '🔥 折扣促銷';
                $actionColor = '#ef4444';
                $stagnantReason = '⚠️ 銷速過慢 (MOS > 12M)';
                $mosLevel = 2;
            } elseif ($mos > 6) {
                $action = '📝 觀察/加強推廣';
                $actionColor = '#f59e0b';
                $stagnantReason = '🔸 銷速偏慢 (MOS > 6M)';
                $mosLevel = 1;
            }

            $activeDisplays = isset($displaysMap[$sku]) ? $displaysMap[$sku] : [];

            $products[] = [
                'sku' => $sku,
                'series' => $info['series'],
                'grade' => '',
                'costPerPiece' => $info['cost'],
                'perPing' => $info['perPing'],
                'stockPing' => round($stockPing * 10) / 10,
                'inventoryCost' => $inventoryCost,
                'totalPings' => $totalPings,
                'totalAmount' => $totalAmount,
                'totalQty' => $totalQty,
                'saleCount' => $stats ? $stats['count'] : 0,
                'daysSinceLastSale' => $daysSinceLastSale,
                'lastSaleStr' => $lastSaleStr,
                'avgMarginPct' => $avgMarginPct,
                'customers' => $customers,
                'imageUrl' => $info['imageUrl'] ?: ($profileMap[$sku]['imageUrl'] ?? ''),
                'productName' => $profileMap[$sku]['productName'] ?? '',
                'mos' => $mos,
                'monthlySpeedPings' => round($monthlySpeedPings * 10) / 10,
                'action' => $action,
                'actionColor' => $actionColor,
                'stagnantReason' => $stagnantReason,
                'mosLevel' => $mosLevel,
                'displayCount' => count($activeDisplays),
                'displays' => $activeDisplays
            ];
        }

        usort($products, function($a, $b) { return $b['totalPings'] <=> $a['totalPings']; });

        return ['success' => true, 'data' => $products];
    }

    public function getNormalProductOverview()
    {
        $pData = $this->gs->readSheet(PRICE_SHEET);
        if (count($pData) < 2) return ['success' => true, 'data' => []];

        $pH = $pData[0];
        $pCode    = $this->findHeader($pH, ['編號','產品編號']);
        $pSleeper = $this->findHeader($pH, ['睡美人']);
        $pDisc    = $this->findHeader($pH, ['不續辦']);
        $pSer     = $this->findHeader($pH, ['中文系列','系列']);
        $pCost    = $this->findHeader($pH, ['成本','單片成本','成本價']);
        $pPerPing = $this->findHeader($pH, ['片/坪']);
        if ($pCode === -1) return ['success' => true, 'data' => []];

        $normMap = [];
        for ($i = 1; $i < count($pData); $i++) {
            $row = $pData[$i];
            $sku = $this->cleanSku($this->getVal($row, $pCode));
            if (!$sku) continue;
            $isSleeper = ($pSleeper !== -1 && trim($this->getVal($row, $pSleeper)) !== '');
            $isDisc    = ($pDisc !== -1 && trim($this->getVal($row, $pDisc)) !== '');
            if ($isSleeper || $isDisc) continue;
            $normMap[$sku] = [
                'series'  => $pSer !== -1 ? trim($this->getVal($row, $pSer)) : '',
                'cost'    => $pCost !== -1 ? $this->optFloat($this->getVal($row, $pCost)) : 0,
                'perPing' => $pPerPing !== -1 ? ($this->optFloat($this->getVal($row, $pPerPing)) ?: 36) : 36
            ];
        }

        $sleeperCosts = $this->getSleeperCostMap();
        foreach ($normMap as $sku => &$info) {
            if ($info['cost'] <= 0 && isset($sleeperCosts[$sku]) && $sleeperCosts[$sku] > 0) {
                $info['cost'] = $sleeperCosts[$sku];
            }
        }
        unset($info);

        $displaysMap = $this->getActiveDisplaysMap();
        $stockMap = $this->getStockMap();
        $metaMap = $this->getMetaMap();
        $profileMap = $this->getProductProfileMap();
        $salesStats = $this->loadSalesStats($metaMap);

        $now = new DateTime();
        $products = [];
        foreach ($normMap as $sku => $info) {
            $stockPing = isset($stockMap[$sku]) ? $stockMap[$sku] : 0;
            $costPerPing = $info['cost'] * ($info['perPing'] ?: 36);
            $inventoryCost = round($stockPing * $costPerPing);
            $stats = isset($salesStats[$sku]) ? $salesStats[$sku] : null;

            $daysSinceLastSale = null;
            $lastSaleStr = '從未銷售';
            if ($stats && $stats['lastDate']) {
                $daysSinceLastSale = (int)$now->diff($stats['lastDate'])->days;
                $lastSaleStr = $this->formatRocDate($stats['lastDate'], false);
            }

            $totalPings = $stats ? round($stats['totalPings'] * 10) / 10 : 0;
            $customers = $this->buildProductCustomerRows($stats);
            $totalAmount = $stats ? round($stats['totalAmount']) : 0;
            $totalQty = $stats ? round($stats['totalQty']) : 0;
            $avgMarginPct = ($stats && $stats['totalAmount'] > 0)
                ? round((($stats['totalAmount'] - ($stats['totalQty'] * $info['cost'])) / $stats['totalAmount']) * 100, 1)
                : 0;

            // Stagnancy diagnostics & actions
            $pings6M = $stats ? $stats['pings6M'] : 0;
            $monthlySpeedPings = $pings6M / 6;
            $mos = $monthlySpeedPings > 0 ? round(($stockPing / $monthlySpeedPings) * 10) / 10 : ($stockPing > 0 ? 888 : 0);

            $action = '正常去化';
            $actionColor = '#10b981'; // green
            $stagnantReason = '正常';
            $mosLevel = 0;

            if ($daysSinceLastSale === null) {
                $action = '🔥 折扣促銷';
                $actionColor = '#ef4444';
                $stagnantReason = '💀 從未銷售';
                $mosLevel = 2;
            } elseif ($daysSinceLastSale > 180) {
                $action = '🔥 折扣促銷';
                $actionColor = '#ef4444';
                $stagnantReason = '💀 6M無交易';
                $mosLevel = 2;
            } elseif ($daysSinceLastSale > 90) {
                $action = '📝 觀察/加強推廣';
                $actionColor = '#f59e0b';
                $stagnantReason = '🔇 3M無交易';
                $mosLevel = 1;
            } elseif ($mos > 12) {
                $action = '🔥 折扣促銷';
                $actionColor = '#ef4444';
                $stagnantReason = '⚠️ 銷速過慢 (MOS > 12M)';
                $mosLevel = 2;
            } elseif ($mos > 6) {
                $action = '📝 觀察/加強推廣';
                $actionColor = '#f59e0b';
                $stagnantReason = '🔸 銷速偏慢 (MOS > 6M)';
                $mosLevel = 1;
            }

            $activeDisplays = isset($displaysMap[$sku]) ? $displaysMap[$sku] : [];

            $products[] = [
                'sku' => $sku,
                'series' => $info['series'],
                'grade' => '',
                'costPerPiece' => $info['cost'],
                'perPing' => $info['perPing'],
                'stockPing' => round($stockPing * 10) / 10,
                'inventoryCost' => $inventoryCost,
                'totalPings' => $totalPings,
                'totalAmount' => $totalAmount,
                'totalQty' => $totalQty,
                'saleCount' => $stats ? $stats['count'] : 0,
                'daysSinceLastSale' => $daysSinceLastSale,
                'lastSaleStr' => $lastSaleStr,
                'avgMarginPct' => $avgMarginPct,
                'customers' => $customers,
                'imageUrl' => $profileMap[$sku]['imageUrl'] ?? '',
                'productName' => $profileMap[$sku]['productName'] ?? '',
                'mos' => $mos,
                'monthlySpeedPings' => round($monthlySpeedPings * 10) / 10,
                'action' => $action,
                'actionColor' => $actionColor,
                'stagnantReason' => $stagnantReason,
                'mosLevel' => $mosLevel,
                'displayCount' => count($activeDisplays),
                'displays' => $activeDisplays
            ];
        }

        usort($products, function($a, $b) { return $b['totalPings'] <=> $a['totalPings']; });

        return ['success' => true, 'data' => $products];
    }



    private function ensureTrialSheet()
    {
        $data = $this->gs->readSheet(TRIAL_SHEET);
        if (count($data) === 0) {
            $this->gs->writeRows(TRIAL_SHEET, 1, [['識別碼','業務','月份','客戶','編號','系列','片數','單價','金額','成本','毛利率','等級','倍數','獎金','全出清','備註','資料來源']]);
        }
    }

    private function cleanSku($v)
    {
        return strtoupper(preg_replace('/[\s\-]/', '', trim($v)));
    }

    private function normalizeSalesRep($v)
    {
        $name = trim((string)$v);
        if ($name === '') return '未分配';
        if ($name === '陳育瑋') return '陳勁多';
        return $name;
    }

    private function extractProjectFromNote($note)
    {
        $note = trim((string)$note);
        if ($note === '') return '';
        if (mb_strpos($note, '專案') === false) return '';
        return $note;
    }

    private function optFloat($v)
    {
        if ($v instanceof DateTime) return 0;
        $s = preg_replace('/[^0-9.\-]/', '', (string)$v);
        $n = (float)$s;
        return is_finite($n) ? $n : 0;
    }

    private function parseDate($v)
    {
        if (!$v) return null;
        if ($v instanceof DateTime) return $v;
        if (is_numeric($v) && $v > 40000) {
            $dt = new DateTime();
            $dt->setDate(1899, 12, 30);
            $dt->modify('+' . (int)$v . ' days');
            return $dt;
        }
        $str = trim((string)$v);

        if (preg_match('/^0*(\d{2,4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/', $str, $m)) {
            $y = (int)$m[1];
            if ($y < 1000) $y += 1911;
            return new DateTime(sprintf('%04d-%02d-%02d', $y, (int)$m[2], (int)$m[3]));
        }
        if (preg_match('/^0*(\d{2,4})[\/\-\.](\d{1,2})$/', $str, $m)) {
            $y = (int)$m[1];
            if ($y < 1000) $y += 1911;
            return new DateTime(sprintf('%04d-%02d-01', $y, (int)$m[2]));
        }
        return null;
    }

    private function formatRocDate($dt, $withDay = true)
    {
        if (!($dt instanceof DateTime)) return '';
        $rocYear = (int)$dt->format('Y') - 1911;
        if ($rocYear <= 0) return $withDay ? $dt->format('Y/m/d') : $dt->format('Y/m');
        return $withDay
            ? sprintf('%03d/%02d/%02d', $rocYear, (int)$dt->format('m'), (int)$dt->format('d'))
            : sprintf('%03d/%02d', $rocYear, (int)$dt->format('m'));
    }

    private function findHeader($headers, $candidates)
    {
        if (!$headers || count($headers) === 0) return -1;
        $clean = [];
        foreach ($headers as $h) {
            $clean[] = preg_replace('/[\s\x{FEFF}"()（）]/u', '', trim($h));
        }
        foreach ($candidates as $cand) {
            $target = preg_replace('/[\s"()（）]/u', '', $cand);
            $idx = array_search($target, $clean);
            if ($idx !== false) return $idx;
        }
        foreach ($candidates as $cand) {
            $target = preg_replace('/[\s"()（）]/u', '', $cand);
            foreach ($clean as $i => $c) {
                if (strpos($c, $target) !== false) return $i;
            }
        }
        return -1;
    }

    private function getVal($row, $idx)
    {
        return isset($row[$idx]) ? $row[$idx] : '';
    }

    private function buildProductCustomerRows($stats)
    {
        if (!$stats || empty($stats['customerStats'])) return [];
        $rows = array_values($stats['customerStats']);
        usort($rows, function ($a, $b) { return $b['amount'] <=> $a['amount']; });
        $totalAmount = max(1, (float)($stats['totalAmount'] ?? 0));
        $out = [];
        foreach (array_slice($rows, 0, 5) as $row) {
            $out[] = [
                'name' => $row['name'],
                'qty' => round($row['qty']),
                'amount' => round($row['amount']),
                'sharePct' => round(($row['amount'] / $totalAmount) * 100, 1)
            ];
        }
        return $out;
    }

    private function padToColumn($data, $colIndex)
    {
        $result = [];
        foreach ($data as $row) {
            $padded = array_fill(0, $colIndex, '');
            $padded[$colIndex - 1] = $row[0];
            $result[] = $padded;
        }
        return $result;
    }

    public function isSampleRow($custCode, $custName, $note, $amt)
    {
        $cCode = strtoupper(trim($custCode));
        if (substr($cCode, -2) === '-S' || substr($cCode, -3) === '-S1') return true;
        
        $name = trim($custName);
        $nt = trim($note);
        
        if (preg_match('/樣品|陳列|贈|SAMPLE|送樣|扣帶/ui', $name . ' ' . $nt)) {
            return true;
        }
        if ($amt == 0) return true;
        return false;
    }

    public function getActiveDisplaysMap()
    {
        $map = [];
        try {
            $gsLayout = new GoogleSheetsClient(SS_ID_LAYOUT);
            $data = $gsLayout->readSheet(LAYOUT_SHEET);
            if (count($data) < 3) return $map;

            $h = $data[1]; // headers on row index 1
            $idxCust = $this->findHeader($h, ["客戶名稱", "客戶"]);
            $idxSku = $this->findHeader($h, ["編號", "產品編號"]);
            $idxImg = $this->findHeader($h, ["版面連結", "連結", "圖片", "照片"]);
            $idxDate = $this->findHeader($h, ["上架日期", "日期"]);
            $idxOffDate = $this->findHeader($h, ["下架日期"]);

            if ($idxCust === -1 || $idxSku === -1) return $map;

            for ($i = 2; $i < count($data); $i++) {
                $row = $data[$i];
                $offDateVal = trim($this->getVal($row, $idxOffDate));
                if ($offDateVal !== '') continue; // Skip if already off board

                $sku = $this->cleanSku($this->getVal($row, $idxSku));
                if (!$sku) continue;

                $cust = trim($this->getVal($row, $idxCust));
                $dateVal = $idxDate !== -1 ? trim($this->getVal($row, $idxDate)) : '';
                $rawImg = $idxImg !== -1 ? trim($this->getVal($row, $idxImg)) : '';

                $photoUrl = $rawImg;
                $driveId = $this->extractIdFromUrl($rawImg);
                if ($driveId) {
                    $photoUrl = "https://lh3.googleusercontent.com/d/{$driveId}=w1000";
                }

                if (!isset($map[$sku])) {
                    $map[$sku] = [];
                }
                $map[$sku][] = [
                    'cust' => $cust,
                    'date' => $dateVal,
                    'photoUrl' => $photoUrl
                ];
            }
        } catch (Exception $e) {
            error_log("getActiveDisplaysMap error: " . $e->getMessage());
        }
        return $map;
    }

    private function extractIdFromUrl($url)
    {
        if (!$url) return '';
        if (preg_match('/[-\w]{25,}(?!.*[-\w]{25,})/', $url, $matches)) {
            return $matches[0];
        }
        return '';
    }

    public function getSalesYearCacheRows()
    {
        $data = $this->gs->readSheet(CACHE_SHEET);
        if (count($data) < 2) return [];
        return array_slice($data, 1); // skip header row
    }

    public function rebuildSalesYearCache($years = null)
    {
        $lockFp = null;
        try {
            $lockFp = fopen(sys_get_temp_dir() . '/sleeping_beauty_sales_year_cache.lock', 'c');
            if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
                if (is_resource($lockFp)) fclose($lockFp);
                return ['success' => false, 'error' => '年度銷售快取正在由睡美人戰情室重建中，請稍後再試。'];
            }

            foreach (['sleeper', 'normal', 'discontinued'] as $tab) {
                $f = AI_ADVISOR_CACHE_DIR . "/restock_{$tab}.json";
                if (is_file($f)) @unlink($f);
            }

            $nowYear = (int)date('Y');
            if ($years === null) {
                $targetYears = [$nowYear, $nowYear - 1, $nowYear - 2];
            } else {
                $targetYears = array_unique(array_map('intval', (array)$years));
            }
            sort($targetYears);

            $salesRows = $this->gs->readSheet(SALES_SHEET);
            if (count($salesRows) < 2) {
                return ['success' => false, 'error' => '找不到經銷銷售報表資料。'];
            }

            $headers = $salesRows[0];
            $idx = [
                'date' => $this->findHeader($headers, ['單據日期', '銷貨日期', '日期']),
                'code' => $this->findHeader($headers, ['產品編號', '編號', '品號']),
                'customer' => $this->findHeader($headers, ['客戶名稱', '客戶']),
                'sales' => $this->findHeader($headers, ['負責業務', '業務', '業務員']),
                'custCode' => $this->findHeader($headers, ['客戶編號', '客戶代碼', '代碼']),
                'qty' => $this->findHeader($headers, ['數量', '銷貨數量', '片數']),
                'amount' => $this->findHeader($headers, ['金額', '銷貨金額']),
                'type' => $this->findHeader($headers, ['類別', '性質', '單據類別']),
                'productName' => $this->findHeader($headers, ['產品名稱', '品名']),
                'project' => $this->findHeader($headers, ['案名', '專案', '工地名稱', '工地']),
                'note' => $this->findHeader($headers, ['產品備註', '備註', '說明'])
            ];

            if ($idx['date'] === -1 || $idx['code'] === -1 || $idx['qty'] === -1 || $idx['amount'] === -1) {
                return ['success' => false, 'error' => '經銷銷售報表缺少必要欄位：日期、產品編號、數量或金額。'];
            }

            $metaMap = $this->getMetaMap();
            $aggregate = [];
            $scanned = 0;
            $included = 0;
            $updatedAt = date('Y/m/d H:i:s');

            for ($i = 1; $i < count($salesRows); $i++) {
                $row = $salesRows[$i];
                $scanned++;

                $d = $this->parseDate($this->getVal($row, $idx['date']));
                if (!$d) continue;

                $year = (int)$d->format('Y');
                if (!in_array($year, $targetYears, true)) continue;

                $code = $this->cleanSku($this->getVal($row, $idx['code']));
                if (!$code) continue;

                $qty = $this->optFloat($this->getVal($row, $idx['qty']));
                $amount = $this->optFloat($this->getVal($row, $idx['amount']));
                if ($qty == 0 && $amount == 0) continue;

                $custName = trim($this->getVal($row, $idx['customer']));
                $salesName = $this->normalizeSalesRep($idx['sales'] !== -1 ? $this->getVal($row, $idx['sales']) : '');
                $custCode = $idx['custCode'] !== -1 ? trim($this->getVal($row, $idx['custCode'])) : '';
                $productName = $idx['productName'] !== -1 ? trim($this->getVal($row, $idx['productName'])) : '';
                $note = $idx['note'] !== -1 ? trim($this->getVal($row, $idx['note'])) : '';
                $projectName = $this->extractProjectFromNote($note);

                if ($this->isSampleRow($custCode, $custName, $productName . ' ' . $note, $amount)) continue;

                $typeText = $idx['type'] !== -1 ? trim($this->getVal($row, $idx['type'])) : '';
                $isReturn = (strpos($typeText, '退') !== false) || ($qty < 0);
                $month = (int)$d->format('n');
                
                $key = "{$year}|{$month}|{$code}|{$custName}|{$projectName}|{$salesName}";
                $perPing = isset($metaMap[$code]) ? ($metaMap[$code]['perPing'] ?: 36) : 36;

                if (!isset($aggregate[$key])) {
                    $aggregate[$key] = [
                        'year' => $year,
                        'month' => $month,
                        'code' => $code,
                        'customer' => $custName,
                        'project' => $projectName,
                        'sales' => $salesName,
                        'qty' => 0,
                        'pings' => 0,
                        'amount' => 0,
                        'count' => 0,
                        'returnQty' => 0,
                        'totalQty' => 0,
                        'lastTxDate' => null,
                        'productName' => $productName
                    ];
                }

                $item = &$aggregate[$key];
                if ($productName && !$item['productName']) $item['productName'] = $productName;

                if ($isReturn) {
                    $absQty = abs($qty);
                    $item['returnQty'] += $absQty;
                    $item['qty'] -= $absQty;
                    $item['pings'] -= $absQty / $perPing;
                    if ($amount != 0) {
                        $item['amount'] += ($amount < 0 ? $amount : -abs($amount));
                    }
                    $item['totalQty'] = $item['qty'];
                    continue;
                }

                $absQty = abs($qty);
                $item['qty'] += $absQty;
                $item['pings'] += $absQty / $perPing;
                $item['amount'] += $amount;
                $item['count'] += 1;
                $item['totalQty'] = $item['qty'];
                if (!$item['lastTxDate'] || $d > $item['lastTxDate']) {
                    $item['lastTxDate'] = $d;
                }
                $included++;
            }

            $oldRows = [];
            try {
                $existing = $this->gs->readSheet(CACHE_SHEET);
                if (count($existing) > 1) {
                    for ($i = 1; $i < count($existing); $i++) {
                        $row = $existing[$i];
                        $yrVal = (int)$this->getVal($row, 0);
                        if ($yrVal && !in_array($yrVal, $targetYears, true)) {
                            $oldRows[] = $row;
                        }
                    }
                }
            } catch (Exception $ex) {
                // Ignore if sheet doesn't exist
            }

            $newRows = [];
            ksort($aggregate);
            foreach ($aggregate as $key => $item) {
                $newRows[] = [
                    $item['year'],
                    $item['month'],
                    $item['code'],
                    $item['customer'],
                    $item['project'],
                    $item['sales'],
                    round($item['qty'], 2),
                    round($item['pings'], 2),
                    round($item['amount']),
                    $item['count'],
                    round($item['returnQty'], 2),
                    round($item['totalQty'], 2),
                    $item['lastTxDate'] ? $item['lastTxDate']->format('Y/m/d') : '',
                    $item['productName'],
                    $updatedAt,
                    'v4-net-returns'
                ];
            }

            $mergedRows = array_merge($oldRows, $newRows);

            $this->gs->clearSheet(CACHE_SHEET);
            
            $headers = ['年度', '月份', '產品編號', '客戶名稱', '專案名稱', '負責業務', '銷售片數', '銷售坪數', '銷售金額', '交易筆數', '退貨片數', '總片數', '最後交易日', '產品名稱', '更新時間', '快取版本'];
            $allRows = array_merge([$headers], $mergedRows);

            $chunkSize = 1000;
            for ($i = 0; $i < count($allRows); $i += $chunkSize) {
                $chunk = array_slice($allRows, $i, $chunkSize);
                $this->gs->writeRows(CACHE_SHEET, $i + 1, $chunk);
            }
            $this->gs->formatSalesYearCacheSheet(CACHE_SHEET);

            return [
                'success' => true,
                'years' => $targetYears,
                'scannedRows' => $scanned,
                'includedRows' => $included,
                'cacheRows' => count($newRows),
                'totalRowsAfterMerge' => count($mergedRows),
                'updatedAt' => $updatedAt
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        } finally {
            if (is_resource($lockFp)) {
                flock($lockFp, LOCK_UN);
                fclose($lockFp);
            }
        }
    }

    private function loadSalesStats($metaMap)
    {
        $salesStats = [];
        $cacheLoaded = false;
        $now = new DateTime();
        $limit6M = clone $now;
        $limit6M->modify('-180 days');

        try {
            $raw = $this->gs->readSheet(CACHE_SHEET);
            if ($raw && count($raw) > 1) {
                $h = $raw[0];
                $idxCode = $this->findHeader($h, ['產品編號']);
                $idxCust = $this->findHeader($h, ['客戶名稱', '客戶']);
                $idxQty = $this->findHeader($h, ['銷售片數']);
                $idxPings = $this->findHeader($h, ['銷售坪數']);
                $idxAmt = $this->findHeader($h, ['銷售金額']);
                $idxCount = $this->findHeader($h, ['交易筆數']);
                $idxLastDate = $this->findHeader($h, ['最後交易日']);
                if ($idxCode !== -1 && $idxCust !== -1 && $idxPings !== -1 && $idxAmt !== -1 && $idxLastDate !== -1) {
                    for ($i = 1; $i < count($raw); $i++) {
                        $cRow = $raw[$i];
                        $sku = $this->cleanSku($this->getVal($cRow, $idxCode));
                        if (!$sku) continue;

                        $pings = $this->optFloat($this->getVal($cRow, $idxPings));
                        $qty = $idxQty !== -1 ? $this->optFloat($this->getVal($cRow, $idxQty)) : 0;
                        $amount = $idxAmt !== -1 ? $this->optFloat($this->getVal($cRow, $idxAmt)) : 0;
                        $txCount = $idxCount !== -1 ? (int)$this->getVal($cRow, $idxCount) : 0;
                        $d = $this->parseDate($this->getVal($cRow, $idxLastDate));
                        $cust = $this->displayCustomerName($this->getVal($cRow, $idxCust));
                        $perPing = isset($metaMap[$sku]) ? ($metaMap[$sku]['perPing'] ?: 36) : 36;

                        // Older cache versions may have written qty into the pings column.
                        if ($qty > 0 && ($pings <= 0 || $pings > ($qty * 1.2))) {
                            $pings = $qty / $perPing;
                        }
                        // Ignore obviously broken future dates so they don't poison last-sale output.
                        if ($d && (int)$d->format('Y') > ((int)date('Y') + 1)) {
                            $d = null;
                        }

                        if (!isset($salesStats[$sku])) {
                            $salesStats[$sku] = [
                                'lastDate' => null,
                                'totalPings' => 0,
                                'pings6M' => 0,
                                'buyerMap' => [],
                                'count' => 0,
                                'totalAmount' => 0,
                                'totalQty' => 0,
                                'customerStats' => []
                            ];
                        }
                        $s = &$salesStats[$sku];
                        $s['count'] += $txCount;
                        $s['totalPings'] += $pings;
                        $s['totalQty'] += $qty;
                        $s['totalAmount'] += $amount;
                        if ($d) {
                            if (!$s['lastDate'] || $d > $s['lastDate']) $s['lastDate'] = $d;
                            if ($d >= $limit6M) $s['pings6M'] += $pings;
                        }
                        if (!isset($s['buyerMap'][$cust])) $s['buyerMap'][$cust] = 0;
                        $s['buyerMap'][$cust] += $pings;
                        if (!isset($s['customerStats'][$cust])) {
                            $s['customerStats'][$cust] = ['name' => $cust, 'qty' => 0, 'amount' => 0, 'pings' => 0];
                        }
                        $s['customerStats'][$cust]['qty'] += $qty;
                        $s['customerStats'][$cust]['amount'] += $amount;
                        $s['customerStats'][$cust]['pings'] += $pings;
                    }
                    $cacheLoaded = true;
                }
            }
        } catch (Exception $e) {
            // suppress
        }

        if (!$cacheLoaded) {
            $raw = $this->gs->readSheet(SALES_SHEET);
            $h = isset($raw[0]) ? $raw[0] : [];
            $idxCode = $this->findHeader($h, ['產品編號','編號']);
            $idxCustCode = $this->findHeader($h, ['客戶編號','客戶代碼','代碼']);
            $idxDate = $this->findHeader($h, ['日期','單據日期']);
            $idxQty  = $this->findHeader($h, ['數量','片數']);
            $idxCust = $this->findHeader($h, ['客戶名稱','客戶']);
            $idxNote = $this->findHeader($h, ['備註']);
            $idxAmt  = $this->findHeader($h, ['金額','銷額','銷售金額']);

            if ($idxCode !== -1 && $idxDate !== -1) {
                for ($i = 1; $i < count($raw); $i++) {
                    $row = $raw[$i];
                    $sku = $this->cleanSku($this->getVal($row, $idxCode));
                    if (!$sku) continue;

                    $custName = trim($this->getVal($row, $idxCust));
                    $custCode = $idxCustCode !== -1 ? trim($this->getVal($row, $idxCustCode)) : '';
                    $note = trim($this->getVal($row, $idxNote));
                    $amt = $this->optFloat($this->getVal($row, $idxAmt));

                    if ($this->isSampleRow($custCode, $custName, $note, $amt)) continue;

                    $qty = $this->optFloat($this->getVal($row, $idxQty));
                    if ($qty <= 0) continue;

                    $d = $this->parseDate($this->getVal($row, $idxDate));
                    if (!$d) continue;

                    $perPing = isset($metaMap[$sku]) ? ($metaMap[$sku]['perPing'] ?: 36) : 36;
                    $pings = $qty / $perPing;
                    $customerName = $this->displayCustomerName($custName);

                    if (!isset($salesStats[$sku])) {
                        $salesStats[$sku] = [
                            'lastDate' => null,
                            'totalPings' => 0,
                            'pings6M' => 0,
                            'buyerMap' => [],
                            'count' => 0,
                            'totalAmount' => 0,
                            'totalQty' => 0,
                            'customerStats' => []
                        ];
                    }
                    $s = &$salesStats[$sku];
                    $s['count']++;
                    if (!$s['lastDate'] || $d > $s['lastDate']) $s['lastDate'] = $d;
                    if ($d >= $limit6M) $s['pings6M'] += $pings;
                    $s['totalPings'] += $pings;
                    $s['totalQty'] += $qty;
                    $s['totalAmount'] += $amt;
                    if (!isset($s['buyerMap'][$customerName])) $s['buyerMap'][$customerName] = 0;
                    $s['buyerMap'][$customerName] += $pings;
                    if (!isset($s['customerStats'][$customerName])) {
                        $s['customerStats'][$customerName] = ['name' => $customerName, 'qty' => 0, 'amount' => 0, 'pings' => 0];
                    }
                    $s['customerStats'][$customerName]['qty'] += $qty;
                    $s['customerStats'][$customerName]['amount'] += $amt;
                    $s['customerStats'][$customerName]['pings'] += $pings;
                }
            }
        }
        return $salesStats;
    }
}


// ====== ROUTER ======
try {
    $gs = new GoogleSheetsClient();
    $svc = new SleeperService($gs);

    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($action) {
        case 'migrate-notes':
            $oldId = '1-CuM1-4dQfFFMYeozEQSA5WM4VETyC96FBfMWkUx9RM';
            $oldGs = new GoogleSheetsClient($oldId);
            $tab = $_GET['tab'] ?? '';
            $repNames = ['謝博皓', '陳勁多', '潘右森'];
            if (!in_array($tab, $repNames)) {
                echo json_encode(['success' => false, 'msg' => '請指定 tab=謝博皓/陳勁多/潘右森']);
                break;
            }
            $dryrun = !empty($_GET['dryrun']);

            $rows = $oldGs->readSheet($tab);
            $out = [];
            for ($i = 0; $i < count($rows); $i++) {
                $cell0 = trim($rows[$i][0] ?? '');
                if (!preg_match('/^(\d{4})\s*第\s*\d+\s*周/u', $cell0, $m)) continue;
                $year = (int)$m[1];

                $dateRow = $rows[$i + 1] ?? [];
                $weekDate = null;
                $d0 = trim($dateRow[0] ?? '');
                if (preg_match('/^(\d{1,2})\/(\d{1,2})$/', $d0, $dm)) {
                    $weekDate = sprintf('%04d-%02d-%02d', $year, (int)$dm[1], (int)$dm[2]);
                }

                // find "客戶名稱" header row within this block
                for ($r = $i + 3; $r < count($rows); $r++) {
                    $first = trim($rows[$r][0] ?? '');
                    if (preg_match('/^(\d{4})\s*第\s*\d+\s*周/u', $first)) break;
                    if ($first === '客戶名稱') {
                        for ($n = $r + 1; $n < count($rows); $n++) {
                            $nfirst = trim($rows[$n][0] ?? '');
                            if ($nfirst === '' || preg_match('/^(\d{4})\s*第\s*\d+\s*周/u', $nfirst)) break;
                            $note = trim($rows[$n][1] ?? '');
                            if ($note === '') continue;
                            $out[] = [$weekDate, $tab, $nfirst, $note, 'MIGRATED_' . substr(md5($tab . $weekDate . $nfirst . $note . $n), 0, 12)];
                        }
                        break;
                    }
                }
            }

            if ($dryrun) {
                echo json_encode(['success' => true, 'count' => count($out), 'sample' => array_slice($out, 0, 20)]);
                break;
            }

            $gsLayout = new GoogleSheetsClient(SS_ID_LAYOUT);
            if (!empty($_GET['create'])) {
                try { $gsLayout->addSheetPublic('智能_客戶備註歷史'); } catch (Exception $e) {}
                $gsLayout->appendRows('智能_客戶備註歷史', [['日期', '業務姓名', '客戶名稱', '備註', '記錄ID']]);
            }
            foreach (array_chunk($out, 500) as $chunk) {
                $gsLayout->appendRows('智能_客戶備註歷史', $chunk);
            }
            echo json_encode(['success' => true, 'count' => count($out)]);
            break;


        case 'debug-old-sheet':
            $oldId = '1-CuM1-4dQfFFMYeozEQSA5WM4VETyC96FBfMWkUx9RM';
            $oldGs = new GoogleSheetsClient($oldId);
            $sheet = $_GET['sheet'] ?? '謝博皓';
            $data = $oldGs->readSheet($sheet);
            echo json_encode(['success' => true, 'rowCount' => count($data), 'rows' => array_slice($data, 0, 80)]);
            break;




        case 'config':
            $res = $svc->getSleeperConfig();
            echo json_encode($res);
            break;

        case 'sales':
            $year  = (int)($_GET['year'] ?? 2026);
            $month = (int)($_GET['month'] ?? 5);
            $res = $svc->getSleeperSalesByMonth($year, $month);
            echo json_encode($res);
            break;

        case 'sync':
            $year  = (int)($_POST['year'] ?? $_GET['year'] ?? 2026);
            $month = (int)($_POST['month'] ?? $_GET['month'] ?? 5);
            $res = $svc->syncTrialSheet($year, $month);
            echo json_encode($res);
            break;

        case 'recalc':
            $res = $svc->recalcTrialSheet();
            echo json_encode($res);
            break;

        case 'summary':
            $year  = isset($_GET['year'])  ? (int)$_GET['year']  : null;
            $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
            $res = $svc->getBonusSummary($year, $month);
            echo json_encode($res);
            break;

        case 'year-summary':
            try {
                $year  = (int)($_GET['year'] ?? date('Y'));
                $res = $svc->getYearSummary($year);
                echo json_encode($res);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => 'year-summary 錯誤: ' . $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            }
            break;

        case 'strategy-report':
            try {
                $year = (int)($_GET['year'] ?? date('Y'));
                $mode = trim($_GET['mode'] ?? 'month');
                $period = isset($_GET['period']) ? (int)$_GET['period'] : (int)($_GET['month'] ?? date('n'));
                $res = $svc->getStrategyReport($year, $period, $mode);
                echo json_encode($res);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => 'strategy-report 錯誤: ' . $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            }
            break;

        case 'meeting-report':
            try {
                $year = (int)($_GET['year'] ?? date('Y'));
                $month = (int)($_GET['month'] ?? date('n'));
                $res = $svc->getMeetingReport($year, $month);
                echo json_encode($res);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => 'meeting-report 錯誤: ' . $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            }
            break;

        case 'report-history':
            try {
                $res = $svc->getReportHistory();
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => 'report-history 錯誤: ' . $e->getMessage()]);
            }
            break;

        case 'ai-advisor':
            try {
                $year = (int)($_GET['year'] ?? date('Y'));
                $month = (int)($_GET['month'] ?? date('n'));
                $refresh = ($_GET['refresh'] ?? '') === '1';
                $res = $svc->getAiAdvisor($year, $month, $refresh);
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => 'ai-advisor 錯誤: ' . $e->getMessage()]);
            }
            break;

        case 'customer-detail':
            $customer = $_GET['customer'] ?? '';
            $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
            $res = $svc->getCustomerDetail($customer, $year);
            echo json_encode($res);
            break;

        case 'products':
            $res = $svc->getSleeperProductOverview();
            echo json_encode($res);
            break;

        case 'discontinued-products':
            $res = $svc->getDiscontinuedProductOverview();
            echo json_encode($res);
            break;

        case 'normal-products':
            $res = $svc->getNormalProductOverview();
            echo json_encode($res);
            break;

        case 'product-lifecycle':
            $sku = $_GET['sku'] ?? '';
            $res = $svc->getProductLifecycle($sku);
            echo json_encode($res);
            break;

        case 'product-advisor':
            $tab = $_GET['tab'] ?? 'sleeper';
            $refresh = !empty($_GET['refresh']);
            $res = $svc->getProductRestockAdvisor($tab, $refresh);
            echo json_encode($res);
            break;

        case 'product-history':
            $tab = $_GET['tab'] ?? 'sleeper';
            $sku = $_GET['sku'] ?? '';
            $res = $svc->getProductHistory($tab, $sku);
            echo json_encode($res);
            break;

        case 'rebuild-cache':
            $years = $_POST['years'] ?? $_GET['years'] ?? null;
            if ($years && is_string($years)) {
                $years = array_map('intval', preg_split('/[,，\s]+/', $years));
            }
            $res = $svc->rebuildSalesYearCache($years);
            echo json_encode($res);
            break;

        case 'active-displays':
            $res = ['success' => true, 'data' => $svc->getActiveDisplaysMap()];
            echo json_encode($res);
            break;

        case 'trial-sheet':
            $year  = isset($_GET['year'])  ? (int)$_GET['year']  : null;
            $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
            $sales = $_GET['sales'] ?? '';
            $res = $svc->readTrialSheet($year, $month, $sales);
            echo json_encode($res);
            break;


        case 'update-row':
            $rowIdx    = (int)($_POST['rowIdx'] ?? 0);
            $qty       = (float)($_POST['qty'] ?? 0);
            $unitPrice = (float)($_POST['unitPrice'] ?? 0);
            $multiplier = (float)($_POST['multiplier'] ?? 0);
            $clearance = $_POST['clearance'] ?? '';
            $note      = $_POST['note'] ?? '';
            $res = $svc->updateTrialRow($rowIdx, $qty, $unitPrice, $multiplier, $clearance, $note);
            echo json_encode($res);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'msg' => '未知 action: ' . $action]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
