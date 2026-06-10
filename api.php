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

    public function __construct($ssId = null)
    {
        $this->ssId = $ssId ?: SS_ID_MAIN;
    }

    public function readSheet($sheetName)
    {
        $range = urlencode("'{$sheetName}'!A:ZZ");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}";
        $res = $this->api('GET', $url);
        return isset($res['values']) ? $res['values'] : [];
    }

    public function writeRows($sheetName, $startRow, $rows)
    {
        $range = urlencode("'{$sheetName}'!A{$startRow}");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}";
        $this->api('PUT', $url, [
            'values' => $rows,
            'majorDimension' => 'ROWS'
        ]);
    }

    public function appendRows($sheetName, $rows)
    {
        $range = urlencode("'{$sheetName}'!A:A");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}:append?valueInputOption=USER_ENTERED";
        $this->api('POST', $url, [
            'values' => $rows,
            'majorDimension' => 'ROWS'
        ]);
    }

    public function updateCell($sheetName, $row, $col, $value)
    {
        $colLetter = $this->colIndexToLetter($col);
        $range = urlencode("'{$sheetName}'!{$colLetter}{$row}");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}";
        $this->api('PUT', $url, [
            'values' => [[$value]],
            'majorDimension' => 'ROWS'
        ]);
    }

    public function updateRowRange($sheetName, $row, $colStart, $values)
    {
        $colLetter = $this->colIndexToLetter($colStart);
        $colEnd = $this->colIndexToLetter($colStart + count($values) - 1);
        $range = urlencode("'{$sheetName}'!{$colLetter}{$row}:{$colEnd}{$row}");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}";
        $this->api('PUT', $url, [
            'values' => [$values],
            'majorDimension' => 'ROWS'
        ]);
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

    private static function base64url($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

// ====== SleeperService ======
class SleeperService
{
    private $gs;

    public function __construct($gs)
    {
        $this->gs = $gs;
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
            $pCost = $this->findHeader($pH, ['成本','單片成本','成本價','單價']);
            $pSeries = $this->findHeader($pH, ['中文系列','系列']);
            if ($pCode !== -1 && $pSleeper !== -1) {
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
            'date'  => $this->findHeader($h, ['日期','單據日期','銷貨日期']),
            'sales' => $this->findHeader($h, ['負責業務','業務','業務員','負責人']),
            'cust'  => $this->findHeader($h, ['客戶名稱','客戶']),
            'code'  => $this->findHeader($h, ['產品編號','編號','品碼','序號']),
            'amt'   => $this->findHeader($h, ['金額','銷額','銷售金額','成交金額','小計','總計']),
            'qty'   => $this->findHeader($h, ['數量','片數']),
            'note'  => $this->findHeader($h, ['備註','說明'])
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
            $note = trim($this->getVal($row, $idx['note']));
            if (strpos($custName, '樣品') !== false || strpos($note, '樣品') !== false || strpos($note, '扣帶') !== false) continue;

            $code = $this->cleanSku($this->getVal($row, $idx['code']));
            if (!$code || !isset($sleeperMap[$code])) continue;

            $sleeper = $sleeperMap[$code];
            $qty = $this->optFloat($this->getVal($row, $idx['qty']));
            $amt = $this->optFloat($this->getVal($row, $idx['amt']));
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
            $name = $r['業務'] ?: '未指定';
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
        $custTotals = [];
        $prodTotals = [];

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
                $sales = trim($this->getVal($r, $salesIdx));
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
                    if (!isset($custTotals[$cust])) $custTotals[$cust] = 0;
                    $custTotals[$cust] += abs($amt);
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
        arsort($custTotals);
        $ci = 0;
        foreach ($custTotals as $name => $total) {
            if ($ci++ >= 10) break;
            $top10cust[] = ['name' => $name, 'total' => round($total)];
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

    public function getSleeperProductOverview()
    {
        $configRes = $this->getSleeperConfig();
        if (!$configRes['success']) return $configRes;
        $sleeperMap = $configRes['data'];

        $stockMap = $this->getStockMap();
        $metaMap = $this->getMetaMap();

        $raw = $this->gs->readSheet(SALES_SHEET);
        $h = isset($raw[0]) ? $raw[0] : [];
        $idxCode = $this->findHeader($h, ['產品編號','編號']);
        $idxDate = $this->findHeader($h, ['日期','單據日期']);
        $idxQty  = $this->findHeader($h, ['數量','片數']);
        $idxCust = $this->findHeader($h, ['客戶名稱','客戶']);
        $idxNote = $this->findHeader($h, ['備註']);

        $salesStats = [];
        if ($idxCode !== -1 && $idxDate !== -1) {
            for ($i = 1; $i < count($raw); $i++) {
                $sku = $this->cleanSku($this->getVal($raw[$i], $idxCode));
                if (!$sku || !isset($sleeperMap[$sku])) continue;

                $note = trim($this->getVal($raw[$i], $idxNote));
                if (strpos($note, '樣品') !== false || strpos($note, '扣帶') !== false) continue;

                $qty = $this->optFloat($this->getVal($raw[$i], $idxQty));
                if ($qty <= 0) continue;

                $d = $this->parseDate($this->getVal($raw[$i], $idxDate));
                if (!$d) continue;

                $meta = isset($metaMap[$sku]) ? $metaMap[$sku] : ['series' => '', 'perPing' => 36];
                $perPing = $meta['perPing'] ?: 36;
                $pings = $qty / $perPing;
                $cust = trim($this->getVal($raw[$i], $idxCust));

                if (!isset($salesStats[$sku])) {
                    $salesStats[$sku] = ['lastDate' => null, 'totalPings' => 0, 'buyerMap' => []];
                }
                $s = &$salesStats[$sku];
                if (!$s['lastDate'] || $d > $s['lastDate']) $s['lastDate'] = $d;
                $s['totalPings'] += $pings;
                if (!isset($s['buyerMap'][$cust])) $s['buyerMap'][$cust] = 0;
                $s['buyerMap'][$cust] += $pings;
            }
        }

        $now = new DateTime();
        $products = [];
        foreach ($sleeperMap as $sku => $slp) {
            $meta = isset($metaMap[$sku]) ? $metaMap[$sku] : ['series' => '', 'perPing' => 36];
            $stockPing = isset($stockMap[$sku]) ? $stockMap[$sku] : 0;
            $costPerPing = $slp['cost'] * ($meta['perPing'] ?: 36);
            $inventoryCost = round($stockPing * $costPerPing);
            $stats = isset($salesStats[$sku]) ? $salesStats[$sku] : null;

            $daysSinceLastSale = null;
            $lastSaleStr = '從未銷售';
            if ($stats && $stats['lastDate']) {
                $daysSinceLastSale = (int)$now->diff($stats['lastDate'])->days;
                $lastSaleStr = $stats['lastDate']->format('Y/m');
            }

            $totalPings = $stats ? round($stats['totalPings'] * 10) / 10 : 0;
            $buyers = [];
            if ($stats) {
                $sorted = $stats['buyerMap'];
                arsort($sorted);
                $i = 0;
                foreach ($sorted as $name => $pings) {
                    if ($i++ >= 5) break;
                    $buyers[] = ['name' => $name, 'pings' => round($pings * 10) / 10];
                }
            }

            $products[] = [
                'sku' => $sku,
                'series' => $meta['series'],
                'grade' => $slp['grade'],
                'costPerPiece' => $slp['cost'],
                'perPing' => $meta['perPing'],
                'stockPing' => round($stockPing * 10) / 10,
                'inventoryCost' => $inventoryCost,
                'totalPings' => $totalPings,
                'daysSinceLastSale' => $daysSinceLastSale,
                'lastSaleStr' => $lastSaleStr,
                'buyers' => $buyers
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
}

// ====== ROUTER ======
try {
    $gs = new GoogleSheetsClient();
    $svc = new SleeperService($gs);

    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($action) {

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

        case 'products':
            $res = $svc->getSleeperProductOverview();
            echo json_encode($res);
            break;

        case 'read-trial':
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
