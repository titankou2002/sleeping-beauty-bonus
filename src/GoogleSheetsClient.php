<?php
namespace App;

/**
 * 用 REST API 直接讀寫 Google Sheets（不吃任何外部套件）
 * 使用 Service Account JWT 認證
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

    // ─── Private ───

    private function getAccessToken(): string
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

    private static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
