<?php
namespace App;

use Firebase\JWT\JWT;

/**
 * 直接用 REST API 讀寫 Google Sheets，不吃 google/apiclient 肥套件
 */
class GoogleSheetsClient
{
    private string $ssId;
    private ?string $accessToken = null;
    private float $tokenExpires = 0;

    public function __construct(string $ssId = null)
    {
        $this->ssId = $ssId ?: SS_ID_MAIN;
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken && microtime(true) < $this->tokenExpires) {
            return $this->accessToken;
        }

        $sa = json_decode(file_get_contents(SERVICE_ACCOUNT_FILE), true);

        $now = time();
        $payload = [
            'iss'   => $sa['client_email'],
            'sub'   => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now
        ];

        $jwt = JWT::encode($payload, $sa['private_key'], 'RS256');

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => http_build_query([
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

    private function api(string $method, string $url, array $body = null): array
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

        return json_decode($resp, true) ?: [];
    }

    public function readSheet(string $sheetName): array
    {
        $range = urlencode("'{$sheetName}'!A:ZZ");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}";
        $res = $this->api('GET', $url);
        return $res['values'] ?? [];
    }

    public function writeRows(string $sheetName, int $startRow, array $rows): void
    {
        $range = urlencode("'{$sheetName}'!A{$startRow}");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}";
        $this->api('PUT', $url, [
            'values' => $rows,
            'majorDimension' => 'ROWS'
        ]);
    }

    public function appendRows(string $sheetName, array $rows): void
    {
        $range = urlencode("'{$sheetName}'!A:A");
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->ssId}/values/{$range}:append?valueInputOption=USER_ENTERED";
        $this->api('POST', $url, [
            'values' => $rows,
            'majorDimension' => 'ROWS'
        ]);
    }
}
