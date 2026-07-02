<?php
// ====== DataTrait ======
trait DataTrait
{

    private function getClient($ssId)
    {
        if (!isset($this->clientsCache[$ssId])) {
            $this->clientsCache[$ssId] = new GoogleSheetsClient($ssId);
        }
        return $this->clientsCache[$ssId];
    }

    private function getSalesRepAreaMap()
    {
        if ($this->areaMap !== null) return $this->areaMap;
        $this->areaMap = [];
        try {
            $rows = $this->gs->readSheet('業務分區');
            if (count($rows) >= 2) {
                $h = $rows[0];
                $idxRep = $this->findHeader($h, ['業務', '業務名稱', '負責業務']);
                $idxArea = $this->findHeader($h, ['區域', '地區']);
                if ($idxRep !== -1 && $idxArea !== -1) {
                    for ($i = 1; $i < count($rows); $i++) {
                        $rep = trim($this->getVal($rows[$i], $idxRep));
                        $area = trim($this->getVal($rows[$i], $idxArea));
                        if ($rep !== '' && $area !== '') {
                            $rep = self::$salesMerge[$rep] ?? $rep;
                            $this->areaMap[$rep] = $area;
                        }
                    }
                }
            }
        } catch (Exception $e) {
        }
        return $this->areaMap;
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

    public function getReservationMap()
    {
        $rows = $this->gs->readSheet('保留單');
        if (count($rows) < 2) return [];
        $h = $rows[0];
        $skuIdx = $this->findHeader($h, ['編號','產品編號']);
        $qtyIdx = $this->findHeader($h, ['數量','片數']);
        if ($skuIdx === -1 || $qtyIdx === -1) return [];
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
                'brand' => $idxBrand !== -1 ? $this->normalizeBrand($this->getVal($row, $idxBrand)) : '',
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

    public function getSalesYearCacheRows()
    {
        $data = $this->gs->readSheet(CACHE_SHEET);
        if (count($data) < 2) return [];
        return array_slice($data, 1); // skip header row
    }

    public function rebuildSalesYearCache($years = null)
    {
        set_time_limit(120);
        ini_set('memory_limit', '256M');

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
