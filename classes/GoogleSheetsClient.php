<?php
// ====== GoogleSheetsClient ======
class GoogleSheetsClient
{
    private $ssId;
    private $accessToken = null;
    private $tokenExpires = 0;
    private $sheetIdCache = [];
    private $sheetPropsCache = [];
    private $sheetValueCache = [];
    private static $cacheDir = null;

    public function __construct($ssId = null)
    {
        $this->ssId = $ssId ?: SS_ID_MAIN;
        if (self::$cacheDir === null) {
            self::$cacheDir = defined('AI_ADVISOR_CACHE_DIR') ? AI_ADVISOR_CACHE_DIR . '/sheets' : (dirname(__DIR__) . '/cache/sheets');
            if (!is_dir(self::$cacheDir)) {
                @mkdir(self::$cacheDir, 0755, true);
            }
        }
    }

    public function readSheet($sheetName)
    {
        if (isset($this->sheetValueCache[$sheetName])) {
            return $this->sheetValueCache[$sheetName];
        }

        $cacheFile = self::$cacheDir . '/' . md5($this->ssId . '_' . $sheetName) . '.json';
        $ttl = ($sheetName === CACHE_SHEET) ? 120 : 60;
        if (is_file($cacheFile) && time() - filemtime($cacheFile) < $ttl) {
            $rows = json_decode(file_get_contents($cacheFile), true);
            if (is_array($rows)) {
                $this->sheetValueCache[$sheetName] = $rows;
                return $rows;
            }
        }

        $range = urlencode("'{$sheetName}'!A:ZZ");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}";
        $res = $this->api('GET', $url);
        $rows = isset($res['values']) ? $res['values'] : [];
        $this->sheetValueCache[$sheetName] = $rows;
        file_put_contents($cacheFile, json_encode($rows, JSON_UNESCAPED_UNICODE));
        return $rows;
    }

    private function clearCache($sheetName)
    {
        $this->clearCache($sheetName);
        if (self::$cacheDir) {
            $cacheFile = self::$cacheDir . '/' . md5($this->ssId . '_' . $sheetName) . '.json';
            if (is_file($cacheFile)) @unlink($cacheFile);
        }
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
        $this->clearCache($sheetName);
    }

    public function appendRows($sheetName, $rows)
    {
        $range = urlencode("'{$sheetName}'!A:A");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}:append?valueInputOption=USER_ENTERED";
        $this->api('POST', $url, [
            'values' => $rows,
            'majorDimension' => 'ROWS'
        ]);
        $this->clearCache($sheetName);
    }

    public function clearSheet($sheetName)
    {
        $range = urlencode("'{$sheetName}'!A:ZZ");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}:clear";
        $this->api('POST', $url, (object)[]);
        $this->clearCache($sheetName);
    }

    public function createSheet($sheetName)
    {
        $body = [
            'requests' => [
                [
                    'addSheet' => [
                        'properties' => [
                            'title' => $sheetName
                        ]
                    ]
                ]
            ]
        ];
        $this->batchUpdate($body);
        unset($this->sheetIdCache[$sheetName]);
        unset($this->sheetPropsCache[$sheetName]);
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
        $this->clearCache($sheetName);
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
        $this->clearCache($sheetName);
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
        $this->clearCache($sheetName);
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
