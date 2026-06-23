<?php
class TwseClient
{
    private $cacheDir;
    private $cacheTtl = 14400; // 4 hours

    public function __construct()
    {
        $this->cacheDir = CACHE_DIR . '/twse';
        if (!is_dir($this->cacheDir)) @mkdir($this->cacheDir, 0775, true);
    }

    public function getDailyQuote(string $stockId, string $date = ''): array
    {
        if (!$date) $date = date('Ymd');
        $ym = substr($date, 0, 6) . '01';

        $cacheKey = "daily_{$stockId}_{$ym}";
        $cached = $this->getCache($cacheKey);
        if ($cached !== null) return $cached;

        $url = "https://www.twse.com.tw/exchangeReport/STOCK_DAY?response=json&date={$ym}&stockNo={$stockId}";
        $data = $this->fetch($url);

        if (!isset($data['data'])) return [];

        $rows = [];
        foreach ($data['data'] as $row) {
            $d = $this->parseRocDate($row[0]);
            if (!$d) continue;
            $rows[] = [
                'date' => $d,
                'volume' => (int)str_replace(',', '', $row[1]),
                'turnover' => (float)str_replace(',', '', $row[2]),
                'open' => (float)str_replace(',', '', $row[3]),
                'high' => (float)str_replace(',', '', $row[4]),
                'low' => (float)str_replace(',', '', $row[5]),
                'close' => (float)str_replace(',', '', $row[6]),
                'change' => (float)str_replace(',', '', $row[7]),
                'txCount' => (int)str_replace(',', '', $row[8]),
            ];
        }

        $this->setCache($cacheKey, $rows, $this->cacheTtl);
        return $rows;
    }

    public function getMultiMonthQuotes(string $stockId, int $months = 6): array
    {
        $all = [];
        $now = new DateTime();
        for ($i = 0; $i < $months; $i++) {
            $d = clone $now;
            $d->modify("-{$i} months");
            $ym = $d->format('Ymd');
            $rows = $this->getDailyQuote($stockId, $ym);
            $all = array_merge($rows, $all);
        }
        usort($all, function($a, $b) { return strcmp($a['date'], $b['date']); });

        $seen = [];
        $unique = [];
        foreach ($all as $row) {
            if (!isset($seen[$row['date']])) {
                $seen[$row['date']] = true;
                $unique[] = $row;
            }
        }
        return $unique;
    }

    public function getInstitutionalBuySell(string $stockId, string $date = ''): array
    {
        if (!$date) $date = date('Ymd');

        $cacheKey = "inst_{$stockId}_{$date}";
        $cached = $this->getCache($cacheKey);
        if ($cached !== null) return $cached;

        $url = "https://www.twse.com.tw/fund/T86?response=json&date={$date}&selectType=ALLBUT0999";
        $data = $this->fetch($url);

        if (!isset($data['data'])) return [];

        $result = null;
        foreach ($data['data'] as $row) {
            $code = trim($row[0]);
            if ($code === $stockId) {
                $result = [
                    'date' => $date,
                    'stockId' => $stockId,
                    'foreignBuy' => (int)str_replace(',', '', $row[2]),
                    'foreignSell' => (int)str_replace(',', '', $row[3]),
                    'foreignNet' => (int)str_replace(',', '', $row[4]),
                    'trustBuy' => (int)str_replace(',', '', $row[5]),
                    'trustSell' => (int)str_replace(',', '', $row[6]),
                    'trustNet' => (int)str_replace(',', '', $row[7]),
                    'dealerNet' => (int)str_replace(',', '', $row[8] ?? 0) + (int)str_replace(',', '', $row[11] ?? 0),
                    'totalNet' => (int)str_replace(',', '', $row[14] ?? $row[8] ?? 0),
                ];
                break;
            }
        }

        if ($result) $this->setCache($cacheKey, $result, 86400);
        return $result ?: [];
    }

    public function getInstitutional30Days(string $stockId): array
    {
        $results = [];
        $now = new DateTime();
        for ($i = 0; $i < 35; $i++) {
            $d = clone $now;
            $d->modify("-{$i} days");
            if ($d->format('N') >= 6) continue;
            $dateStr = $d->format('Ymd');
            $data = $this->getInstitutionalBuySell($stockId, $dateStr);
            if ($data) $results[] = $data;
            if (count($results) >= 20) break;
            usleep(300000);
        }
        return array_reverse($results);
    }

    public function getPERatio(string $stockId): array
    {
        $cacheKey = "pe_{$stockId}_" . date('Ymd');
        $cached = $this->getCache($cacheKey);
        if ($cached !== null) return $cached;

        $date = date('Ymd');
        $url = "https://www.twse.com.tw/exchangeReport/BWIBBU?response=json&date={$date}&stockNo={$stockId}";
        $data = $this->fetch($url);

        if (!isset($data['data']) || empty($data['data'])) return [];

        $rows = [];
        foreach ($data['data'] as $row) {
            $d = $this->parseRocDate($row[0]);
            if (!$d) continue;
            $rows[] = [
                'date' => $d,
                'per' => $this->safeFloat($row[4] ?? ''),
                'pbr' => $this->safeFloat($row[5] ?? ''),
                'dividendYield' => $this->safeFloat($row[2] ?? ''),
            ];
        }

        $this->setCache($cacheKey, $rows, 86400);
        return $rows;
    }

    public function getMarginTrading(string $stockId, string $date = ''): array
    {
        if (!$date) $date = date('Ymd');

        $cacheKey = "margin_{$stockId}_{$date}";
        $cached = $this->getCache($cacheKey);
        if ($cached !== null) return $cached;

        $url = "https://www.twse.com.tw/exchangeReport/MI_MARGN?response=json&date={$date}&selectType=STOCK&stockNo={$stockId}";
        $data = $this->fetch($url);

        $result = [];
        if (isset($data['tables'][1]['data'])) {
            foreach ($data['tables'][1]['data'] as $row) {
                $code = trim($row[0] ?? '');
                if ($code === $stockId || $code === '') {
                    $result = [
                        'marginBuy' => (int)str_replace(',', '', $row[2] ?? 0),
                        'marginSell' => (int)str_replace(',', '', $row[3] ?? 0),
                        'marginBalance' => (int)str_replace(',', '', $row[6] ?? 0),
                        'shortBuy' => (int)str_replace(',', '', $row[8] ?? 0),
                        'shortSell' => (int)str_replace(',', '', $row[9] ?? 0),
                        'shortBalance' => (int)str_replace(',', '', $row[12] ?? 0),
                    ];
                    break;
                }
            }
        }

        if ($result) $this->setCache($cacheKey, $result, 86400);
        return $result;
    }

    public function getStockInfo(string $stockId): array
    {
        $cacheKey = "info_{$stockId}";
        $cached = $this->getCache($cacheKey, 86400 * 7);
        if ($cached !== null) return $cached;

        $url = "https://mis.twse.com.tw/stock/api/getStockInfo.jsp?ex_ch=tse_{$stockId}.tw";
        $data = $this->fetch($url);

        $info = [];
        if (isset($data['msgArray'][0])) {
            $s = $data['msgArray'][0];
            $info = [
                'stockId' => $stockId,
                'name' => $s['n'] ?? '',
                'fullName' => $s['nf'] ?? '',
                'lastPrice' => (float)($s['z'] ?? 0),
                'open' => (float)($s['o'] ?? 0),
                'high' => (float)($s['h'] ?? 0),
                'low' => (float)($s['l'] ?? 0),
                'volume' => (int)($s['v'] ?? 0),
                'yesterday' => (float)($s['y'] ?? 0),
            ];
        }

        if ($info) $this->setCache($cacheKey, $info, 86400 * 7);
        return $info;
    }

    private function parseRocDate(string $s): string
    {
        $s = trim(str_replace('/', '', str_replace(' ', '', $s)));
        if (strlen($s) === 7 || strlen($s) === 8) {
            // Format: 114/06/20 or 1140620
            $parts = explode('/', trim(str_replace(' ', '', $s)));
            if (count($parts) === 3) {
                $y = (int)$parts[0] + 1911;
                return sprintf('%04d-%02d-%02d', $y, (int)$parts[1], (int)$parts[2]);
            }
        }
        // Try raw string parse
        if (preg_match('/^(\d{2,3})(\d{2})(\d{2})$/', $s, $m)) {
            $y = (int)$m[1] + 1911;
            return sprintf('%04d-%02d-%02d', $y, (int)$m[2], (int)$m[3]);
        }
        return '';
    }

    private function safeFloat(string $s): float
    {
        $s = trim(str_replace(',', '', $s));
        return $s === '' || $s === '-' ? 0.0 : (float)$s;
    }

    private function fetch(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'Accept: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || !$resp) return [];
        return json_decode($resp, true) ?: [];
    }

    private function getCache(string $key, int $ttl = 0): ?array
    {
        $file = $this->cacheDir . '/' . md5($key) . '.json';
        if (!is_file($file)) return null;
        $age = time() - filemtime($file);
        if ($age > ($ttl ?: $this->cacheTtl)) return null;
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    private function setCache(string $key, array $data, int $ttl = 0): void
    {
        $file = $this->cacheDir . '/' . md5($key) . '.json';
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
