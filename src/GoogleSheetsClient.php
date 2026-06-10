<?php
namespace App;

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
            throw new \RuntimeException("Google Auth 失敗 (HTTP {$httpCode}): {$resp}");
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
            throw new \RuntimeException("Google Sheets API 錯誤 (HTTP {$httpCode}): {$resp}");
        }

        $result = json_decode($resp, true);
        return $result ?: [];
    }

    private static function base64url($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
