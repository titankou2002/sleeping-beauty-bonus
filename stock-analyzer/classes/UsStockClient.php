<?php
class UsStockClient
{
    private $cacheDir;
    private $cacheTtl = 14400;

    public function __construct()
    {
        $this->cacheDir = CACHE_DIR . '/us';
        if (!is_dir($this->cacheDir)) @mkdir($this->cacheDir, 0775, true);
    }

    public function getQuote(string $symbol): array
    {
        $cacheKey = "quote_{$symbol}_" . date('Ymd_H');
        $cached = $this->getCache($cacheKey);
        if ($cached !== null) return $cached;

        $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?range=6mo&interval=1d";
        $data = $this->fetch($url);

        $result = $data['chart']['result'][0] ?? null;
        if (!$result) return [];

        $meta = $result['meta'] ?? [];
        $timestamps = $result['timestamp'] ?? [];
        $indicators = $result['indicators']['quote'][0] ?? [];

        $rows = [];
        foreach ($timestamps as $i => $ts) {
            $close = $indicators['close'][$i] ?? null;
            if ($close === null) continue;
            $rows[] = [
                'date' => date('Y-m-d', $ts),
                'open' => round($indicators['open'][$i] ?? 0, 2),
                'high' => round($indicators['high'][$i] ?? 0, 2),
                'low' => round($indicators['low'][$i] ?? 0, 2),
                'close' => round($close, 2),
                'volume' => (int)($indicators['volume'][$i] ?? 0),
            ];
        }

        $info = [
            'symbol' => $symbol,
            'name' => $meta['shortName'] ?? $symbol,
            'currency' => $meta['currency'] ?? 'USD',
            'exchange' => $meta['exchangeName'] ?? '',
            'lastPrice' => round($meta['regularMarketPrice'] ?? 0, 2),
            'previousClose' => round($meta['previousClose'] ?? 0, 2),
            'quotes' => $rows,
        ];

        $this->setCache($cacheKey, $info, $this->cacheTtl);
        return $info;
    }

    public function getKeyStats(string $symbol): array
    {
        $cacheKey = "stats_{$symbol}_" . date('Ymd');
        $cached = $this->getCache($cacheKey);
        if ($cached !== null) return $cached;

        $url = "https://query1.finance.yahoo.com/v10/finance/quoteSummary/{$symbol}?modules=defaultKeyStatistics,financialData,summaryDetail";
        $data = $this->fetch($url);

        $result = $data['quoteSummary']['result'][0] ?? [];
        $stats = $result['defaultKeyStatistics'] ?? [];
        $financial = $result['financialData'] ?? [];
        $summary = $result['summaryDetail'] ?? [];

        $info = [
            'pe' => $this->extractRaw($summary['trailingPE'] ?? []),
            'forwardPe' => $this->extractRaw($summary['forwardPE'] ?? []),
            'pb' => $this->extractRaw($stats['priceToBook'] ?? []),
            'eps' => $this->extractRaw($financial['revenuePerShare'] ?? []),
            'dividendYield' => $this->extractRaw($summary['dividendYield'] ?? []) * 100,
            'marketCap' => $this->extractRaw($summary['marketCap'] ?? []),
            'beta' => $this->extractRaw($stats['beta'] ?? []),
            'fiftyTwoWeekHigh' => $this->extractRaw($summary['fiftyTwoWeekHigh'] ?? []),
            'fiftyTwoWeekLow' => $this->extractRaw($summary['fiftyTwoWeekLow'] ?? []),
        ];

        $this->setCache($cacheKey, $info, 86400);
        return $info;
    }

    private function extractRaw(array $field): float
    {
        return (float)($field['raw'] ?? $field['fmt'] ?? 0);
    }

    private function fetch(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp ?: '', true) ?: [];
    }

    private function getCache(string $key): ?array
    {
        $file = $this->cacheDir . '/' . md5($key) . '.json';
        if (!is_file($file)) return null;
        if (time() - filemtime($file) > $this->cacheTtl) return null;
        return json_decode(file_get_contents($file), true) ?: null;
    }

    private function setCache(string $key, array $data, int $ttl = 0): void
    {
        $file = $this->cacheDir . '/' . md5($key) . '.json';
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
