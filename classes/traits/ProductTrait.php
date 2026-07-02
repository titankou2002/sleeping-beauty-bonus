<?php
// ====== ProductTrait ======
trait ProductTrait
{

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

    private function computeStagnancyDiagnostics($daysSinceLastSale, $stats, $stockPing)
    {
        $pings6M = $stats ? $stats['pings6M'] : 0;
        $monthlySpeedPings = $pings6M / 6;
        $mos = $monthlySpeedPings > 0 ? round(($stockPing / $monthlySpeedPings) * 10) / 10 : ($stockPing > 0 ? 888 : 0);

        $action = '正常去化';
        $actionColor = '#10b981';
        $stagnantReason = '正常';
        $mosLevel = 0;

        if ($daysSinceLastSale === null) {
            $action = '折扣促銷';
            $actionColor = '#ef4444';
            $stagnantReason = '從未銷售';
            $mosLevel = 2;
        } elseif ($daysSinceLastSale > 180) {
            $action = '折扣促銷';
            $actionColor = '#ef4444';
            $stagnantReason = '6M無交易';
            $mosLevel = 2;
        } elseif ($daysSinceLastSale > 90) {
            $action = '觀察/加強推廣';
            $actionColor = '#f59e0b';
            $stagnantReason = '3M無交易';
            $mosLevel = 1;
        } elseif ($mos > 12) {
            $action = '折扣促銷';
            $actionColor = '#ef4444';
            $stagnantReason = '銷速過慢 (MOS > 12M)';
            $mosLevel = 2;
        } elseif ($mos > 6) {
            $action = '觀察/加強推廣';
            $actionColor = '#f59e0b';
            $stagnantReason = '銷速偏慢 (MOS > 6M)';
            $mosLevel = 1;
        }

        return [
                'mos' => $diag['mos'],
                'monthlySpeedPings' => $diag['monthlySpeedPings'],
                'action' => $diag['action'],
                'actionColor' => $diag['actionColor'],
                'stagnantReason' => $diag['stagnantReason'],
                'mosLevel' => $diag['mosLevel'],
        ];
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

            $diag = $this->computeStagnancyDiagnostics($daysSinceLastSale, $stats, $stockPing);

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

            $diag = $this->computeStagnancyDiagnostics($daysSinceLastSale, $stats, $stockPing);

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
                'mos' => $diag['mos'],
                'monthlySpeedPings' => $diag['monthlySpeedPings'],
                'action' => $diag['action'],
                'actionColor' => $diag['actionColor'],
                'stagnantReason' => $diag['stagnantReason'],
                'mosLevel' => $diag['mosLevel'],
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

            $diag = $this->computeStagnancyDiagnostics($daysSinceLastSale, $stats, $stockPing);

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
                'mos' => $diag['mos'],
                'monthlySpeedPings' => $diag['monthlySpeedPings'],
                'action' => $diag['action'],
                'actionColor' => $diag['actionColor'],
                'stagnantReason' => $diag['stagnantReason'],
                'mosLevel' => $diag['mosLevel'],
                'displayCount' => count($activeDisplays),
                'displays' => $activeDisplays
            ];
        }

        usort($products, function($a, $b) { return $b['totalPings'] <=> $a['totalPings']; });

        return ['success' => true, 'data' => $products];
    }

    public function getNewProductAnalysis($cohortMonths = 24)
    {
        $profileMap = $this->getProductProfileMap();
        $areaMap = $this->getSalesRepAreaMap();

        $cutoff = new DateTime("-{$cohortMonths} months");
        $today = new DateTime('today');
        $newSkus = [];
        foreach ($profileMap as $sku => $p) {
            $firstIn = trim($p['firstInDate'] ?? '');
            if ($firstIn === '') continue;
            $d = $this->parseDate($firstIn);
            if (!$d) continue;
            if ($d < $cutoff || $d > $today) continue;
            $newSkus[$sku] = [
                'sku' => $sku,
                'series' => $p['seriesCn'] ?: ($p['series'] ?: ''),
                'brand' => $p['brand'] ?? '',
                'category' => $p['category'] ?? '',
                'firstInDate' => $d,
                'firstInYear' => (int)$d->format('Y'),
                'firstInMonth' => (int)$d->format('n'),
                'isSleeper' => !empty($p['isSleeper']),
                'isDiscontinued' => !empty($p['isDiscontinued']),
                'cost' => $this->optFloat($p['cost'] ?? 0),
                'imageUrl' => $p['imageUrl'] ?? ''
            ];
        }

        $cacheRows = $this->gs->readSheet(CACHE_SHEET);
        $cacheBySku = [];
        $skuReps = [];
        $skuCustRep = [];
        $skuAreaAmount = [];
        if (count($cacheRows) > 1) {
            $h = $cacheRows[0];
            $idx = [
                'year' => $this->findHeader($h, ['年度']),
                'month' => $this->findHeader($h, ['月份']),
                'sku' => $this->findHeader($h, ['產品編號']),
                'customer' => $this->findHeader($h, ['客戶名稱']),
                'amount' => $this->findHeader($h, ['銷售金額']),
                'count' => $this->findHeader($h, ['交易筆數']),
                'pings' => $this->findHeader($h, ['銷售坪數']),
                'qty' => $this->findHeader($h, ['銷售片數', '數量']),
                'sales' => $this->findHeader($h, ['負責業務', '業務']),
                'prodName' => $this->findHeader($h, ['產品名稱'])
            ];
            for ($i = 1; $i < count($cacheRows); $i++) {
                $row = $cacheRows[$i];
                $sku = $this->cleanSku($this->getVal($row, $idx['sku']));
                if ($sku === '' || !isset($newSkus[$sku])) continue;
                $y = (int)$this->getVal($row, $idx['year']);
                $m = (int)$this->getVal($row, $idx['month']);
                $cust = $this->displayCustomerName($this->getVal($row, $idx['customer']));
                $amt = $this->optFloat($this->getVal($row, $idx['amount']));
                $cnt = (int)$this->getVal($row, $idx['count']);
                $ping = $this->optFloat($this->getVal($row, $idx['pings']));
                $qty = (int)$this->getVal($row, $idx['qty']);
                $prodName = $idx['prodName'] !== -1 ? $this->getVal($row, $idx['prodName']) : '';
                if ($this->isSampleRow($sku, $cust, $prodName, $amt)) continue;
                $monthKey = sprintf('%04d-%02d', $y, $m);
                if (!isset($cacheBySku[$sku])) $cacheBySku[$sku] = [];
                if (!isset($cacheBySku[$sku][$monthKey])) {
                    $cacheBySku[$sku][$monthKey] = ['amount' => 0, 'count' => 0, 'pings' => 0, 'qty' => 0, 'customers' => []];
                }
                $cacheBySku[$sku][$monthKey]['amount'] += $amt;
                $cacheBySku[$sku][$monthKey]['count'] += $cnt;
                $cacheBySku[$sku][$monthKey]['pings'] += $ping;
                $cacheBySku[$sku][$monthKey]['qty'] += $qty;
                $cacheBySku[$sku][$monthKey]['customers'][$cust] = true;

                $rep = trim((string)$this->getVal($row, $idx['sales']));
                if ($rep !== '') {
                    $rep = self::$salesMerge[$rep] ?? $rep;
                    if (!isset($skuReps[$sku])) $skuReps[$sku] = [];
                    $skuReps[$sku][$rep] = true;
                    $skuCustRep[$sku][$cust] = $rep;
                    $area = $areaMap[$rep] ?? '未分配';
                    if (!isset($skuAreaAmount[$sku])) $skuAreaAmount[$sku] = [];
                    if (!isset($skuAreaAmount[$sku][$area])) $skuAreaAmount[$sku][$area] = 0;
                    $skuAreaAmount[$sku][$area] += $amt;
                }
            }
        }

        $displayBySku = [];
        try {
            $gsLayout = new GoogleSheetsClient(SS_ID_LAYOUT);
            $layoutRows = $gsLayout->readSheet(LAYOUT_SHEET);
            if (count($layoutRows) > 2) {
                $h = $layoutRows[1];
                $lCust = $this->findHeader($h, ['客戶名稱', '客戶']);
                $lSku = $this->findHeader($h, ['編號', '產品編號']);
                for ($i = 2; $i < count($layoutRows); $i++) {
                    $row = $layoutRows[$i];
                    $sku = $this->cleanSku($this->getVal($row, $lSku));
                    if ($sku === '' || !isset($newSkus[$sku])) continue;
                    $cust = $this->displayCustomerName($this->getVal($row, $lCust));
                    if (!isset($displayBySku[$sku])) $displayBySku[$sku] = [];
                    $displayBySku[$sku][$cust] = true;
                }
            }
        } catch (Exception $e) {
        }

        $products = [];
        $allMonths = [];

        foreach ($newSkus as $sku => $info) {
            $fy = $info['firstInYear'];
            $fm = $info['firstInMonth'];
            $monthlySales = array_fill(0, $cohortMonths, 0);
            $monthlyCount = array_fill(0, $cohortMonths, 0);
            $monthlyPings = array_fill(0, $cohortMonths, 0);
            $monthlyQty = array_fill(0, $cohortMonths, 0);
            $monthlyCustCount = array_fill(0, $cohortMonths, 0);
            $totalAmount = 0;
            $totalCount = 0;
            $totalPings = 0;
            $totalQty = 0;
            $customerSet = [];
            $firstTxMonth = null;

            $data = $cacheBySku[$sku] ?? [];
            foreach ($data as $monthKey => $d) {
                $parts = explode('-', $monthKey);
                $y = (int)$parts[0];
                $m = (int)$parts[1];
                $monthsSince = ($y - $fy) * 12 + ($m - $fm);
                if ($monthsSince < 0 || $monthsSince >= $cohortMonths) continue;

                $monthlySales[$monthsSince] += $d['amount'];
                $monthlyCount[$monthsSince] += $d['count'];
                $monthlyPings[$monthsSince] += $d['pings'];
                $monthlyQty[$monthsSince] += $d['qty'];
                $monthlyCustCount[$monthsSince] += count($d['customers']);
                $totalAmount += $d['amount'];
                $totalCount += $d['count'];
                $totalPings += $d['pings'];
                $totalQty += $d['qty'];
                foreach ($d['customers'] as $c => $v) $customerSet[$c] = true;

                if ($firstTxMonth === null && $d['amount'] > 0) {
                    $firstTxMonth = $monthsSince;
                }
            }

            $ttfs = $firstTxMonth !== null ? $firstTxMonth : -1;
            $customerCount = count($customerSet);
            $displayCount = count($displayBySku[$sku] ?? []);
            $reps = array_keys($skuReps[$sku] ?? []);
            $areaSales = [];
            if (isset($skuAreaAmount[$sku])) {
                foreach ($skuAreaAmount[$sku] as $area => $amt) {
                    $areaSales[] = ['area' => $area, 'amount' => round($amt)];
                }
                usort($areaSales, function ($a, $b) { return $b['amount'] - $a['amount']; });
            }

            $products[] = [
                'sku' => $sku,
                'series' => $info['series'],
                'brand' => $info['brand'],
                'category' => $info['category'],
                'firstInDate' => $info['firstInDate']->format('Y/m/d'),
                'firstInYear' => $info['firstInYear'],
                'firstInMonth' => $info['firstInMonth'],
                'isSleeper' => $info['isSleeper'],
                'isDiscontinued' => $info['isDiscontinued'],
                'imageUrl' => $info['imageUrl'],
                'ttfs' => $ttfs,
                'ttfsDesc' => $ttfs < 0 ? '從未交易' : ($ttfs === 0 ? '當月即成交' : "上市後 {$ttfs} 個月才首次成交"),
                'totalAmount' => round($totalAmount),
                'totalCount' => $totalCount,
                'totalQty' => $totalQty,
                'totalPings' => round($totalPings, 2),
                'customerCount' => $customerCount,
                'displayCount' => $displayCount,
                'customers' => array_keys($customerSet),
                'salesReps' => array_keys($skuReps[$sku] ?? []),
                'areaSales' => $areaSales,
                'monthlySales' => array_map('round', $monthlySales),
                'monthlyCount' => $monthlyCount,
                'monthlyQty' => $monthlyQty,
                'monthlyCustCount' => $monthlyCustCount
            ];

            for ($ms = 0; $ms < $cohortMonths; $ms++) {
                if (!isset($allMonths[$ms])) $allMonths[$ms] = [];
                $allMonths[$ms][] = $monthlySales[$ms];
            }
        }

        $cohortCurve = [];
        for ($ms = 0; $ms < $cohortMonths; $ms++) {
            $vals = $allMonths[$ms] ?? [];
            sort($vals);
            $n = count($vals);
            $median = $n > 0 ? $vals[(int)($n / 2)] : 0;
            $p25 = $n > 0 ? $vals[(int)($n * 0.25)] : 0;
            $p75 = $n > 0 ? $vals[(int)($n * 0.75)] : 0;
            $cohortCurve[] = [
                'month' => $ms + 1,
                'label' => '第' . ($ms + 1) . '月',
                'median' => round($median),
                'p25' => round($p25),
                'p75' => round($p75)
            ];
        }

        $gradeDefs = [
            'A' => ['label' => '明星產品', 'desc' => '短期內即獲得市場認可，銷售動能強勁', 'suggestion' => '維持上架優勢，可加推相關系列延伸品'],
            'B' => ['label' => '潛力產品', 'desc' => '表現中上，有進一步成長空間', 'suggestion' => '加強業務推廣與陳列曝光，提升客戶迴轉率'],
            'C' => ['label' => '觀察產品', 'desc' => '市場反應平淡，需檢討行銷策略', 'suggestion' => '檢討訂價策略與陳列位置，考慮搭售或促銷方案'],
            'D' => ['label' => '弱勢產品', 'desc' => '幾乎無市場動能，面臨淘汰風險', 'suggestion' => '評估是否續留，建議降價出清或退場'],
        ];

        $graded = [];
        foreach ($products as &$p) {
            $score = 0;
            if ($p['ttfs'] >= 0 && $p['ttfs'] <= 1) $score += 3;
            elseif ($p['ttfs'] >= 0 && $p['ttfs'] <= 3) $score += 2;
            elseif ($p['ttfs'] > 3) $score += 1;
            if ($p['customerCount'] >= 5) $score += 3;
            elseif ($p['customerCount'] >= 2) $score += 2;
            elseif ($p['customerCount'] >= 1) $score += 1;
            if ($p['displayCount'] >= 5) $score += 3;
            elseif ($p['displayCount'] >= 2) $score += 2;
            elseif ($p['displayCount'] >= 1) $score += 1;
            if ($p['totalAmount'] >= 300000) $score += 3;
            elseif ($p['totalAmount'] >= 100000) $score += 2;
            elseif ($p['totalAmount'] > 0) $score += 1;
            if ($score >= 10) $p['grade'] = 'A';
            elseif ($score >= 7) $p['grade'] = 'B';
            elseif ($score >= 4) $p['grade'] = 'C';
            else $p['grade'] = 'D';
            $p['score'] = $score;
            $p['gradeLabel'] = $gradeDefs[$p['grade']]['label'];
            $p['gradeDesc'] = $gradeDefs[$p['grade']]['desc'];
            $p['suggestion'] = $gradeDefs[$p['grade']]['suggestion'];
            $graded[] = $p;
        }
        unset($p);

        usort($graded, function ($a, $b) { return $b['score'] - $a['score']; });

        $seriesGroups = [];
        foreach ($graded as $p) {
            $s = $p['series'] ?: '未分類';
            if (!isset($seriesGroups[$s])) {
                $seriesGroups[$s] = ['series' => $s, 'productCount' => 0, 'totalAmount' => 0, 'scoreSum' => 0, 'grades' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0], 'products' => []];
            }
            $seriesGroups[$s]['productCount']++;
            $seriesGroups[$s]['totalAmount'] += $p['totalAmount'];
            $seriesGroups[$s]['scoreSum'] += $p['score'];
            $seriesGroups[$s]['grades'][$p['grade']]++;
            $seriesGroups[$s]['products'][] = $p['sku'];
        }
        foreach ($seriesGroups as &$sg) {
            $sg['avgScore'] = round($sg['scoreSum'] / $sg['productCount'], 1);
            $sg['avgSales'] = round($sg['totalAmount'] / $sg['productCount']);
            $grades = $sg['grades'];
            $gc = $sg['productCount'];
            if ($grades['A'] >= $gc * 0.5) $sg['mainGrade'] = 'A';
            elseif ($grades['B'] >= $gc * 0.5) $sg['mainGrade'] = 'B';
            elseif ($grades['D'] >= $gc * 0.5) $sg['mainGrade'] = 'D';
            else $sg['mainGrade'] = 'C';
        }
        unset($sg);
        usort($seriesGroups, function ($a, $b) { return $b['totalAmount'] - $a['totalAmount']; });

        $gradeGroups = [];
        foreach ($gradeDefs as $g => $def) {
            $gradeGroups[$g] = [
                'grade' => $g, 'label' => $def['label'], 'desc' => $def['desc'], 'suggestion' => $def['suggestion'],
                'productCount' => 0, 'seriesCount' => 0, 'totalAmount' => 0, 'seriesBreakdown' => []
            ];
        }
        foreach ($graded as $p) {
            $g = $p['grade'];
            $gradeGroups[$g]['productCount']++;
            $gradeGroups[$g]['totalAmount'] += $p['totalAmount'];
            $s = $p['series'] ?: '未分類';
            if (!isset($gradeGroups[$g]['seriesBreakdown'][$s])) {
                $gradeGroups[$g]['seriesBreakdown'][$s] = ['series' => $s, 'count' => 0, 'amount' => 0];
            }
            $gradeGroups[$g]['seriesBreakdown'][$s]['count']++;
            $gradeGroups[$g]['seriesBreakdown'][$s]['amount'] += $p['totalAmount'];
        }
        foreach ($gradeGroups as &$gg) {
            $gg['seriesCount'] = count($gg['seriesBreakdown']);
            $gg['seriesBreakdown'] = array_values($gg['seriesBreakdown']);
            usort($gg['seriesBreakdown'], function ($a, $b) { return $b['count'] - $a['count']; });
        }
        unset($gg);

        $summary = [
            'totalProducts' => count($graded),
            'gradeA' => count(array_filter($graded, function ($p) { return $p['grade'] === 'A'; })),
            'gradeB' => count(array_filter($graded, function ($p) { return $p['grade'] === 'B'; })),
            'gradeC' => count(array_filter($graded, function ($p) { return $p['grade'] === 'C'; })),
            'gradeD' => count(array_filter($graded, function ($p) { return $p['grade'] === 'D'; })),
            'avgTTFS' => $this->safeAvg(array_map(function ($p) { return $p['ttfs']; }, $graded)),
            'avgCustomerCount' => round(array_sum(array_column($graded, 'customerCount')) / max(1, count($graded)), 1),
            'avgDisplayCount' => round(array_sum(array_column($graded, 'displayCount')) / max(1, count($graded)), 1),
            'avgTotalAmount' => round(array_sum(array_column($graded, 'totalAmount')) / max(1, count($graded))),
        ];

        return [
            'success' => true,
            'data' => [
                'cohortInfo' => [
                    'cohortMonths' => $cohortMonths,
                    'cohortStart' => $cutoff->format('Y/m/d'),
                    'cohortEnd' => $today->format('Y/m/d'),
                    'productCount' => count($graded),
                    'filter' => '首次進貨日在 ' . $cutoff->format('Y/m') . ' ~ ' . $today->format('Y/m')
                ],
                'summary' => $summary,
                'products' => $graded,
                'cohortCurve' => $cohortCurve,
                'seriesGroups' => $seriesGroups,
                'gradeGroups' => $gradeGroups
            ]
        ];
    }
}
