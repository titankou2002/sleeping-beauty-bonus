<?php
// ====== ReportTrait ======
trait ReportTrait
{

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

        // Auto-rebuild cache if requested month has no data
        $rebuildFlag = sys_get_temp_dir() . '/sb_srebuilt_' . $year . '_' . $period;
        if (!file_exists($rebuildFlag) || filemtime($rebuildFlag) < time() - 21600) {
            $tmpRows = $this->gs->readSheet(CACHE_SHEET);
            if (count($tmpRows) >= 2) {
                $h = $tmpRows[0];
                $yIdx = $this->findHeader($h, ['年度']);
                $mIdx = $this->findHeader($h, ['月份']);
                if ($yIdx !== -1 && $mIdx !== -1) {
                    $hasMonth = false;
                    for ($i = 1; $i < count($tmpRows); $i++) {
                        if ((int)$tmpRows[$i][$yIdx] === $year && (int)$tmpRows[$i][$mIdx] === $period) {
                            $hasMonth = true;
                            break;
                        }
                    }
                    if (!$hasMonth) {
                        $this->rebuildSalesYearCache([$year]);
                    }
                }
            }
            @touch($rebuildFlag);
        }

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
            $profile = $productProfiles[$sku] ?? [];
            $customer = $this->displayCustomerName($this->getVal($row, $idx['customer']));
            $project = trim($idx['project'] !== -1 ? $this->getVal($row, $idx['project']) : '');
            $hasProject = $project !== '';
            if (!$hasProject) $project = '非專案';
            $sales = $this->normalizeSalesRep($idx['sales'] !== -1 ? $this->getVal($row, $idx['sales']) : '');
            if (isset(self::$salesMerge[$sales])) $sales = self::$salesMerge[$sales];
            $pings = $idx['pings'] !== -1 ? $this->optFloat($this->getVal($row, $idx['pings'])) : 0;
            $txCount = $idx['count'] !== -1 ? (int)$this->getVal($row, $idx['count']) : 0;
            $productName = trim($this->getVal($row, $idx['productName']));
            $series = !empty($profile) ? trim(($profile['seriesCn'] ?? '') ?: ($profile['series'] ?? '')) : (isset($metaMap[$sku]) ? trim($metaMap[$sku]['series']) : '');
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
        try { return $this->_getMeetingReportInner((int)$year, (int)$month); }
        catch (\Throwable $e) {
            $frames = array_map(fn($f) => basename($f['file'] ?? '?') . ':' . ($f['line'] ?? '?'), array_slice(array_filter($e->getTrace(), fn($f) => isset($f['file'])), 0, 6));
            throw new \Exception($e->getMessage() . ' | ' . basename($e->getFile()) . ':' . $e->getLine() . ' | ' . implode(' > ', $frames));
        }
    }
    private function _getMeetingReportInner($year, $month)
    {
        if ($month < 1 || $month > 12) $month = (int)date('n');

        // Auto-rebuild cache if requested month has no data
        $rebuildFlag = sys_get_temp_dir() . '/sb_mrebuilt_' . $year . '_' . $month;
        if (!file_exists($rebuildFlag) || filemtime($rebuildFlag) < time() - 21600) {
            $tmpRows = $this->gs->readSheet(CACHE_SHEET);
            if (count($tmpRows) >= 2) {
                $h = $tmpRows[0];
                $yIdx = $this->findHeader($h, ['年度']);
                $mIdx = $this->findHeader($h, ['月份']);
                if ($yIdx !== -1 && $mIdx !== -1) {
                    $hasMonth = false;
                    for ($i = 1; $i < count($tmpRows); $i++) {
                        if ((int)$tmpRows[$i][$yIdx] === $year && (int)$tmpRows[$i][$mIdx] === $month) {
                            $hasMonth = true;
                            break;
                        }
                    }
                    if (!$hasMonth) {
                        $this->rebuildSalesYearCache([$year]);
                    }
                }
            }
            @touch($rebuildFlag);
        }

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
            $profile = $profiles[$sku] ?? [];

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
                    'series' => trim(($profile['seriesCn'] ?? '') ?: ($profile['series'] ?? '')),
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

    private function getCompanyReportStats($ssId, $year, $month)
    {
        try {
            $gsClient = $this->getClient($ssId);
            $cacheRows = $gsClient->readSheet(CACHE_SHEET);
            if (count($cacheRows) < 2) {
                return ['success' => false, 'msg' => '快取工作表為空或格式錯誤'];
            }
            $h = $cacheRows[0];
            $idx = [
                'year' => $this->findHeader($h, ['年度']),
                'month' => $this->findHeader($h, ['月份']),
                'amount' => $this->findHeader($h, ['銷售金額']),
                'pings' => $this->findHeader($h, ['銷售坪數']),
                'count' => $this->findHeader($h, ['交易筆數'])
            ];
            
            if ($idx['year'] === -1 || $idx['month'] === -1 || $idx['amount'] === -1) {
                return ['success' => false, 'msg' => '找不到必要之快取標題欄位'];
            }

            $sales = 0;
            $salesYoyBase = 0;
            $pings = 0;
            $txCount = 0;
            $ytdSales = 0;
            $ytdPrevSales = 0;
            $monthlySales = array_fill(1, 12, 0);

            for ($i = 1; $i < count($cacheRows); $i++) {
                $row = $cacheRows[$i];
                $rowYear = (int)$this->getVal($row, $idx['year']);
                $rowMonth = (int)$this->getVal($row, $idx['month']);
                $amount = $this->optFloat($this->getVal($row, $idx['amount']));
                $rowPings = $idx['pings'] !== -1 ? $this->optFloat($this->getVal($row, $idx['pings'])) : 0;
                $rowCount = $idx['count'] !== -1 ? (int)$this->getVal($row, $idx['count']) : 0;

                if ($rowYear === $year) {
                    $monthlySales[$rowMonth] += $amount;
                }

                if ($rowYear === $year && $rowMonth === $month) {
                    $sales += $amount;
                    $pings += $rowPings;
                    $txCount += $rowCount;
                }

                if ($rowYear === ($year - 1) && $rowMonth === $month) {
                    $salesYoyBase += $amount;
                }

                if ($rowYear === $year && $rowMonth <= $month) {
                    $ytdSales += $amount;
                }

                if ($rowYear === ($year - 1) && $rowMonth <= $month) {
                    $ytdPrevSales += $amount;
                }
            }

            $salesYoyPct = $salesYoyBase > 0 ? (($sales - $salesYoyBase) / $salesYoyBase * 100) : 0;
            $ytdYoyPct = $ytdPrevSales > 0 ? (($ytdSales - $ytdPrevSales) / $ytdPrevSales * 100) : 0;
            $avgTicket = $txCount > 0 ? ($sales / $txCount) : 0;

            return [
                'success' => true,
                'kpis' => [
                    'sales' => round($sales),
                    'salesYoyBase' => round($salesYoyBase),
                    'salesYoyPct' => round($salesYoyPct, 1),
                    'pings' => round($pings, 2),
                    'txCount' => $txCount,
                    'avgTicket' => round($avgTicket),
                    'ytdSales' => round($ytdSales),
                    'ytdPrevSales' => round($ytdPrevSales),
                    'ytdYoyPct' => round($ytdYoyPct, 1)
                ],
                'monthlySales' => array_values($monthlySales)
            ];

        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    public function getGroupMeetingReport($year, $month)
    {
        $year = (int)$year;
        $month = (int)$month;
        if ($month < 1 || $month > 12) $month = (int)date('n');

        $sbData = $this->getCompanyReportStats(SS_ID_MAIN, $year, $month);
        $adData = $this->getCompanyReportStats(SS_ID_ANDYGA, $year, $month);
        $xyData = $this->getCompanyReportStats(SS_ID_XIYENA, $year, $month);

        $groupKpis = [
            'sales' => 0,
            'salesYoyBase' => 0,
            'salesYoyPct' => 0,
            'pings' => 0,
            'txCount' => 0,
            'avgTicket' => 0,
            'ytdSales' => 0,
            'ytdPrevSales' => 0,
            'ytdYoyPct' => 0
        ];
        $groupMonthlySales = array_fill(0, 12, 0);

        $companies = [
            'sleepingBeauty' => $sbData,
            'andyga' => $adData,
            'xiyena' => $xyData
        ];

        foreach ($companies as $key => $data) {
            if ($data['success']) {
                $kpis = $data['kpis'];
                $groupKpis['sales'] += $kpis['sales'];
                $groupKpis['salesYoyBase'] += $kpis['salesYoyBase'];
                $groupKpis['pings'] += $kpis['pings'];
                $groupKpis['txCount'] += $kpis['txCount'];
                $groupKpis['ytdSales'] += $kpis['ytdSales'];
                $groupKpis['ytdPrevSales'] += $kpis['ytdPrevSales'];

                for ($m = 0; $m < 12; $m++) {
                    $groupMonthlySales[$m] += $data['monthlySales'][$m] ?? 0;
                }
            }
        }

        $groupKpis['salesYoyPct'] = $groupKpis['salesYoyBase'] > 0 
            ? round((($groupKpis['sales'] - $groupKpis['salesYoyBase']) / $groupKpis['salesYoyBase'] * 100), 1) 
            : 0;
        
        $groupKpis['ytdYoyPct'] = $groupKpis['ytdPrevSales'] > 0 
            ? round((($groupKpis['ytdSales'] - $groupKpis['ytdPrevSales']) / $groupKpis['ytdPrevSales'] * 100), 1) 
            : 0;

        $groupKpis['avgTicket'] = $groupKpis['txCount'] > 0 
            ? round($groupKpis['sales'] / $groupKpis['txCount']) 
            : 0;

        for ($m = 0; $m < 12; $m++) {
            $groupMonthlySales[$m] = round($groupMonthlySales[$m]);
        }

        return [
            'success' => true,
            'data' => [
                'year' => $year,
                'month' => $month,
                'companies' => [
                    'sleepingBeauty' => [
                        'name' => '高雅瓷 (睡美人)',
                        'success' => $sbData['success'],
                        'error' => $sbData['msg'] ?? '',
                        'kpis' => $sbData['success'] ? $sbData['kpis'] : null,
                        'monthlySales' => $sbData['success'] ? $sbData['monthlySales'] : null
                    ],
                    'andyga' => [
                        'name' => '安帝嘉',
                        'success' => $adData['success'],
                        'error' => $adData['msg'] ?? '',
                        'kpis' => $adData['success'] ? $adData['kpis'] : null,
                        'monthlySales' => $adData['success'] ? $adData['monthlySales'] : null
                    ],
                    'xiyena' => [
                        'name' => '喜悅納',
                        'success' => $xyData['success'],
                        'error' => $xyData['msg'] ?? '',
                        'kpis' => $xyData['success'] ? $xyData['kpis'] : null,
                        'monthlySales' => $xyData['success'] ? $xyData['monthlySales'] : null
                    ]
                ],
                'group' => [
                    'name' => '集團加總',
                    'kpis' => $groupKpis,
                    'monthlySales' => $groupMonthlySales
                ]
            ]
        ];
    }

    private function getCompanyCacheCustomers($ssId, $year, $month)
    {
        try {
            $gsClient = $this->getClient($ssId);
            $cacheRows = $gsClient->readSheet(CACHE_SHEET);
            if (count($cacheRows) < 2) return [];

            $h = $cacheRows[0];
            $idx = [
                'year' => $this->findHeader($h, ['年度']),
                'month' => $this->findHeader($h, ['月份']),
                'customer' => $this->findHeader($h, ['客戶名稱', '客戶']),
                'sku' => $this->findHeader($h, ['產品編號', '編號']),
                'amount' => $this->findHeader($h, ['銷售金額']),
                'pings' => $this->findHeader($h, ['銷售坪數']),
                'count' => $this->findHeader($h, ['交易筆數']),
                'rep' => $this->findHeader($h, ['負責業務', '業務']),
            ];
            if ($idx['year'] === -1 || $idx['customer'] === -1 || $idx['amount'] === -1) return [];

            $customers = [];
            for ($i = 1; $i < count($cacheRows); $i++) {
                $row = $cacheRows[$i];
                $rowYear = (int)$this->getVal($row, $idx['year']);
                $rowMonth = (int)$this->getVal($row, $idx['month']);
                $cust = $this->displayCustomerName($this->getVal($row, $idx['customer']));
                if ($cust === '未知客戶') continue;
                $amount = $this->optFloat($this->getVal($row, $idx['amount']));
                $pings = $idx['pings'] !== -1 ? $this->optFloat($this->getVal($row, $idx['pings'])) : 0;
                $txCount = $idx['count'] !== -1 ? (int)$this->getVal($row, $idx['count']) : 0;
                $sku = $idx['sku'] !== -1 ? $this->cleanSku($this->getVal($row, $idx['sku'])) : '';

                $prevYear = $month === 1 ? $year - 1 : $year;
                $prevMonth = $month === 1 ? 12 : $month - 1;

                if (!isset($customers[$cust])) {
                    $customers[$cust] = ['curMonth' => 0, 'curMonthPings' => 0, 'curMonthTx' => 0, 'prevMonth' => 0, 'lastMonth' => 0, 'ytd' => 0, 'ytdPrev' => 0, 'skus' => []];
                }
                if ($rowYear === $year && $rowMonth === $month) {
                    $customers[$cust]['curMonth'] += $amount;
                    $customers[$cust]['curMonthPings'] += $pings;
                    $customers[$cust]['curMonthTx'] += $txCount;
                    if ($sku && !in_array($sku, $customers[$cust]['skus'])) $customers[$cust]['skus'][] = $sku;
                }
                if ($rowYear === $prevYear && $rowMonth === $prevMonth) {
                    $customers[$cust]['lastMonth'] += $amount;
                }
                if ($rowYear === ($year - 1) && $rowMonth === $month) {
                    $customers[$cust]['prevMonth'] += $amount;
                }
                if ($rowYear === $year && $rowMonth <= $month) {
                    $customers[$cust]['ytd'] += $amount;
                }
                if ($rowYear === ($year - 1) && $rowMonth <= $month) {
                    $customers[$cust]['ytdPrev'] += $amount;
                }
            }
            return $customers;
        } catch (Exception $e) {
            return [];
        }
    }

    private function getCompanyProductStats($ssId, $year, $month)
    {
        try {
            $gsClient = $this->getClient($ssId);
            $cacheRows = $gsClient->readSheet(CACHE_SHEET);
            if (count($cacheRows) < 2) return ['bySize' => [], 'byBrand' => [], 'byCategory' => []];

            $h = $cacheRows[0];
            $idx = [
                'year'    => $this->findHeader($h, ['年度']),
                'month'   => $this->findHeader($h, ['月份']),
                'sku'     => $this->findHeader($h, ['產品編號', '編號']),
                'amount'  => $this->findHeader($h, ['銷售金額']),
                'pings'   => $this->findHeader($h, ['銷售坪數']),
                'project' => $this->findHeader($h, ['專案名稱']),
            ];
            if ($idx['year'] === -1 || $idx['amount'] === -1) return ['bySize' => [], 'byBrand' => [], 'byCategory' => [], 'byCategoryRetail' => [], 'byCategoryProject' => []];

            $priceData = $gsClient->readSheet(PRICE_SHEET);
            $metaMap = [];
            if (count($priceData) >= 2) {
                $pH = $priceData[0];
                $pCode = $this->findHeader($pH, ['編號', '產品編號']);
                $pSize = $this->findHeader($pH, ['尺寸(cm)', '尺寸']);
                $pBrand = $this->findHeader($pH, ['廠牌', '品牌']);
                $pCat = $this->findHeader($pH, ['產品大類', '大類']);
                for ($i = 1; $i < count($priceData); $i++) {
                    $sku = $this->cleanSku($this->getVal($priceData[$i], $pCode));
                    if (!$sku) continue;
                    $metaMap[$sku] = [
                        'size' => $pSize !== -1 ? $this->normalizeSizeLabel($this->getVal($priceData[$i], $pSize)) : '未標尺寸',
                        'brand' => $pBrand !== -1 ? $this->normalizeBrand($this->getVal($priceData[$i], $pBrand)) : '未知',
                        'category' => $pCat !== -1 ? trim($this->getVal($priceData[$i], $pCat)) : '未分類',
                    ];
                }
            }

            $bySize = [];
            $byBrand = [];
            $byCategory = [];
            $byCategoryRetail = [];
            $byCategoryProject = [];
            for ($i = 1; $i < count($cacheRows); $i++) {
                $row = $cacheRows[$i];
                $rowYear = (int)$this->getVal($row, $idx['year']);
                $rowMonth = (int)$this->getVal($row, $idx['month']);
                if ($rowYear !== $year || $rowMonth !== $month) continue;

                $amount = $this->optFloat($this->getVal($row, $idx['amount']));
                $pings = $idx['pings'] !== -1 ? $this->optFloat($this->getVal($row, $idx['pings'])) : 0;
                $sku = $idx['sku'] !== -1 ? $this->cleanSku($this->getVal($row, $idx['sku'])) : '';
                $projectName = $idx['project'] !== -1 ? trim($this->getVal($row, $idx['project'])) : '';
                $isProject = $projectName !== '';
                $meta = $metaMap[$sku] ?? ['size' => '未標尺寸', 'brand' => '未知', 'category' => '未分類'];

                $size = $meta['size'] ?: '未標尺寸';
                $brand = $meta['brand'] ?: '未知';
                $cat = $meta['category'] ?: '未分類';

                if (!isset($bySize[$size])) $bySize[$size] = ['amount' => 0, 'pings' => 0];
                $bySize[$size]['amount'] += $amount;
                $bySize[$size]['pings'] += $pings;

                if (!isset($byBrand[$brand])) $byBrand[$brand] = ['amount' => 0, 'pings' => 0];
                $byBrand[$brand]['amount'] += $amount;
                $byBrand[$brand]['pings'] += $pings;

                if (!isset($byCategory[$cat])) $byCategory[$cat] = ['amount' => 0, 'pings' => 0];
                $byCategory[$cat]['amount'] += $amount;
                $byCategory[$cat]['pings'] += $pings;

                if ($isProject) {
                    if (!isset($byCategoryProject[$cat])) $byCategoryProject[$cat] = ['amount' => 0, 'pings' => 0];
                    $byCategoryProject[$cat]['amount'] += $amount;
                    $byCategoryProject[$cat]['pings'] += $pings;
                } else {
                    if (!isset($byCategoryRetail[$cat])) $byCategoryRetail[$cat] = ['amount' => 0, 'pings' => 0];
                    $byCategoryRetail[$cat]['amount'] += $amount;
                    $byCategoryRetail[$cat]['pings'] += $pings;
                }
            }

            arsort($bySize);
            arsort($byBrand);
            arsort($byCategory);

            return ['bySize' => $bySize, 'byBrand' => $byBrand, 'byCategory' => $byCategory, 'byCategoryRetail' => $byCategoryRetail, 'byCategoryProject' => $byCategoryProject];
        } catch (Exception $e) {
            return ['bySize' => [], 'byBrand' => [], 'byCategory' => []];
        }
    }

    private function getCompanyContractSummary($ssId, $year, $month)
    {
        try {
            $gsClient = $this->getClient($ssId);
            $rows = $gsClient->readSheet('合約');
            if (count($rows) < 2) return ['customers' => [], 'healthCounts' => [], 'active' => 0, 'monthlyTarget' => 0, 'detail' => []];

            $h = $rows[0];
            $idxHealth = $this->findHeader($h, ['健康度']);
            $idxCustomer = $this->findHeader($h, ['客戶']);
            $idxContractAmt = $this->findHeader($h, ['合約內容']);
            $idxQty = $this->findHeader($h, ['張數']);
            $idxPayment = $this->findHeader($h, ['沖帳方式']);
            $idxFirstDue = $this->findHeader($h, ['第一張票期']);
            $idxLastDue = $this->findHeader($h, ['最後一張票期']);
            $idxPrepay = $this->findHeader($h, ['預收金額']);
            $idxSales = $this->findHeader($h, ['業務']);

            $balanceCols = [];
            foreach ($h as $i => $col) {
                $c = trim((string)$col);
                if (mb_strpos($c, '餘額') !== false) {
                    $dateInfo = ['rocY' => null, 'm' => null];
                    if (preg_match('/(\d{2,4})[\/年](\d{1,2})月/u', $c, $m)) {
                        $y = (int)$m[1];
                        if ($y < 100) $y += 1911;
                        $dateInfo = ['rocY' => $y, 'm' => (int)$m[2]];
                    }
                    $balanceCols[] = ['idx' => $i, 'date' => $dateInfo];
                }
            }
            usort($balanceCols, function ($a, $b) {
                if ($a['date']['rocY'] === null && $b['date']['rocY'] === null) return 0;
                if ($a['date']['rocY'] === null) return 1;
                if ($b['date']['rocY'] === null) return -1;
                if ($a['date']['rocY'] !== $b['date']['rocY']) return $a['date']['rocY'] - $b['date']['rocY'];
                return $a['date']['m'] - $b['date']['m'];
            });
            $idxBalance = count($balanceCols) > 0 ? $balanceCols[count($balanceCols) - 1]['idx'] : -1;
            $idxPrevBalance = count($balanceCols) > 1 ? $balanceCols[count($balanceCols) - 2]['idx'] : -1;

            $healthCounts = [];
            $customers = [];
            $detail = [];
            $monthlyTarget = 0;
            $today = new DateTime('today');

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $health = trim($this->getVal($row, $idxHealth));
                // 跳過歷史紀錄（沖完合約未續約）
                if (mb_strpos($health, '沖完') !== false || mb_strpos($health, '未續約') !== false) continue;
                $customer = $this->displayCustomerName($this->getVal($row, $idxCustomer));
                if ($customer === '未知客戶') continue;

                $contractAmt = $idxContractAmt !== -1 ? $this->optFloat($this->getVal($row, $idxContractAmt)) : 0;
                $qty = $idxQty !== -1 ? max((int)$this->getVal($row, $idxQty), 1) : 1;
                $totalContract = $contractAmt * $qty;

                $bal = $idxBalance !== -1 ? $this->optFloat($this->getVal($row, $idxBalance)) : 0;
                $prevBal = $idxPrevBalance !== -1 ? $this->optFloat($this->getVal($row, $idxPrevBalance)) : null;
                $consumption = ($prevBal !== null && $prevBal > $bal) ? ($prevBal - $bal) : 0;

                $balRatio = $totalContract > 0 ? ($bal / $totalContract) : 1;

                $due = $this->parseDate($this->getVal($row, $idxLastDue));
                $dueDays = $due ? (int)$today->diff($due)->format('%r%a') : null;

                $paymentMethod = $idxPayment !== -1 ? trim($this->getVal($row, $idxPayment)) : '';
                $firstDue = $this->parseDate($this->getVal($row, $idxFirstDue));
                $prepay = $idxPrepay !== -1 ? $this->optFloat($this->getVal($row, $idxPrepay)) : 0;
                $rep = $idxSales !== -1 ? trim($this->getVal($row, $idxSales)) : '';

                $dueMonths = $dueDays !== null ? round(abs($dueDays) / 30, 1) : null;

                // === 健康分級 ===
                if ($bal <= 0) {
                    $bucket = '待續約';
                } elseif ($dueDays === null || $dueDays >= 0) {
                    $bucket = $balRatio > 0.9 ? '觀察' : '正常';
                } else {
                    $od = abs($dueDays);
                    if ($od >= 730) {
                        $bucket = '掛點';
                    } elseif ($od >= 365) {
                        $bucket = '警示';
                    } elseif ($od >= 180) {
                        $bucket = $balRatio > 0.5 ? '警示' : '觀察';
                    } else {
                        $bucket = $balRatio > 0.4 ? '觀察' : '正常';
                    }
                }

                // 餘額狀態（純視覺）
                $balStatus = 'normal';
                if ($bal <= 0) $balStatus = 'none';
                elseif ($bal > 0 && $bal <= 30000 && $balRatio < 0.3) $balStatus = 'low';

                if (!isset($healthCounts[$bucket])) $healthCounts[$bucket] = 0;
                $healthCounts[$bucket]++;

                if (in_array($bucket, ['正常', '觀察', '警示', '掛點', '待續約'], true)) {
                    $monthlyTarget += $contractAmt;
                }

                $dueText = '';
                $dueLevel = 0;
                if ($dueDays !== null) {
                    if ($dueDays < 0) {
                        $m = round(abs($dueDays) / 30, 1);
                        $dueText = '逾期 ' . $m . ' 個月';
                        if (abs($dueDays) >= 300) $dueLevel = 3;
                        elseif (abs($dueDays) >= 180) $dueLevel = 2;
                        elseif (abs($dueDays) >= 90) $dueLevel = 1;
                    } elseif ($dueDays > 0) {
                        $m = round($dueDays / 30, 1);
                        $dueText = $m . ' 個月後到期';
                    } else {
                        $dueText = '今天到期';
                    }
                }

                $customers[$customer] = ['health' => $bucket, 'target' => $contractAmt, 'rep' => $rep];
                $detail[] = [
                    'name' => $customer,
                    'health' => $bucket,
                    'balStatus' => $balStatus,
                    'paymentMethod' => $paymentMethod,
                    'target' => round($contractAmt),
                    'qty' => $qty,
                    'totalContract' => round($totalContract),
                    'balance' => round($bal),
                    'prevBalance' => $prevBal !== null ? round($prevBal) : null,
                    'consumption' => round($consumption),
                    'balRatio' => round($balRatio * 100, 1),
                    'prepay' => round($prepay),
                    'firstDue' => $firstDue ? $firstDue->format('Y/m/d') : '',
                    'lastDue' => $due ? $due->format('Y/m/d') : '',
                    'dueDays' => $dueDays,
                    'dueMonths' => $dueMonths,
                    'dueLevel' => $dueLevel,
                    'dueText' => $dueText,
                    'rep' => $rep,
                ];
            }
            usort($detail, function ($a, $b) { return $b['balance'] - $a['balance']; });
            return ['customers' => $customers, 'healthCounts' => $healthCounts, 'active' => count($customers), 'monthlyTarget' => round($monthlyTarget), 'detail' => $detail];
        } catch (Exception $e) {
            return ['customers' => [], 'healthCounts' => [], 'active' => 0, 'monthlyTarget' => 0, 'detail' => []];
        }
    }

    private function getCompanyTopProducts($ssId, $year, $month, $limit = 10)
    {
        try {
            $gsClient = $this->getClient($ssId);
            $cacheRows = $gsClient->readSheet(CACHE_SHEET);
            if (count($cacheRows) < 2) return [];

            $h = $cacheRows[0];
            $idx = [
                'year' => $this->findHeader($h, ['年度']),
                'month' => $this->findHeader($h, ['月份']),
                'sku' => $this->findHeader($h, ['產品編號', '編號']),
                'amount' => $this->findHeader($h, ['銷售金額']),
                'pings' => $this->findHeader($h, ['銷售坪數']),
                'count' => $this->findHeader($h, ['交易筆數'])
            ];
            if ($idx['year'] === -1 || $idx['amount'] === -1) return [];

            $priceData = $gsClient->readSheet(PRICE_SHEET);
            $profiles = [];
            if (count($priceData) >= 2) {
                $pH = $priceData[0];
                $idxCode = $this->findHeader($pH, ['編號','產品編號']);
                $idxSeries = $this->findHeader($pH, ['系列','英文系列']);
                $idxSeriesCn = $this->findHeader($pH, ['中文系列','系列中文','中文名稱']);
                $idxProduct = $this->findHeader($pH, ['原廠品名','產品名稱','品名']);
                $idxSize = $this->findHeader($pH, ['尺寸(cm)','尺寸']);
                $idxCategory = $this->findHeader($pH, ['產品大類','大類']);
                $idxImage = $this->findHeader($pH, ['單片連結網址','單片圖','圖片網址','單片網址','圖片連結']);
                
                for ($i = 1; $i < count($priceData); $i++) {
                    $row = $priceData[$i];
                    $sku = $this->cleanSku($this->getVal($row, $idxCode));
                    if (!$sku) continue;
                    $profiles[$sku] = [
                        'series' => $idxSeries !== -1 ? trim($this->getVal($row, $idxSeries)) : '',
                        'seriesCn' => $idxSeriesCn !== -1 ? trim($this->getVal($row, $idxSeriesCn)) : '',
                        'productName' => $idxProduct !== -1 ? trim($this->getVal($row, $idxProduct)) : '',
                        'size' => $idxSize !== -1 ? $this->normalizeSizeLabel($this->getVal($row, $idxSize)) : '',
                        'category' => $idxCategory !== -1 ? trim($this->getVal($row, $idxCategory)) : '',
                        'imageUrl' => $idxImage !== -1 ? trim($this->getVal($row, $idxImage)) : '',
                    ];
                }
            }

            $topProducts = [];
            for ($i = 1; $i < count($cacheRows); $i++) {
                $row = $cacheRows[$i];
                $rowYear = (int)$this->getVal($row, $idx['year']);
                $rowMonth = (int)$this->getVal($row, $idx['month']);
                if ($rowYear !== $year || $rowMonth !== $month) continue;

                $sku = $idx['sku'] !== -1 ? $this->cleanSku($this->getVal($row, $idx['sku'])) : '';
                if ($sku === '') continue;

                $amount = $this->optFloat($this->getVal($row, $idx['amount']));
                $pings = $idx['pings'] !== -1 ? $this->optFloat($this->getVal($row, $idx['pings'])) : 0;
                $txCount = $idx['count'] !== -1 ? (int)$this->getVal($row, $idx['count']) : 0;
                
                $profile = $profiles[$sku] ?? [];

                if (!isset($topProducts[$sku])) {
                    $topProducts[$sku] = [
                        'sku' => $sku,
                        'name' => trim($profile['productName'] ?? ''),
                        'series' => trim(($profile['seriesCn'] ?? '') ?: ($profile['series'] ?? '')),
                        'category' => trim($profile['category'] ?? ''),
                        'size' => trim($profile['size'] ?? ''),
                        'imageUrl' => trim($profile['imageUrl'] ?? ''),
                        'amount' => 0,
                        'pings' => 0,
                        'count' => 0
                    ];
                }
                $topProducts[$sku]['amount'] += $amount;
                $topProducts[$sku]['pings'] += $pings;
                $topProducts[$sku]['count'] += $txCount;
            }

            usort($topProducts, function ($a, $b) {
                return $b['amount'] <=> $a['amount'];
            });

            return array_slice($topProducts, 0, $limit);
        } catch (Exception $e) {
            return [];
        }
    }

    private function getCompanySeriesRanks($ssId, $year, $month)
    {
        try {
            $gsClient = $this->getClient($ssId);
            $cacheRows = $gsClient->readSheet(CACHE_SHEET);
            if (count($cacheRows) < 2) return [];
            $h = $cacheRows[0];
            $idxYear = $this->findHeader($h, ['年度']);
            $idxMonth = $this->findHeader($h, ['月份']);
            $idxSku = $this->findHeader($h, ['產品編號', '編號']);
            $idxAmt = $this->findHeader($h, ['銷售金額']);
            $idxPing = $this->findHeader($h, ['銷售坪數']);
            $idxCust = $this->findHeader($h, ['客戶名稱']);
            if ($idxYear === -1 || $idxAmt === -1 || $idxSku === -1) return [];

            $priceData = $gsClient->readSheet(PRICE_SHEET);
            $profiles = [];
            if (count($priceData) >= 2) {
                $pH = $priceData[0];
                $pCode = $this->findHeader($pH, ['編號','產品編號']);
                $pSeries = $this->findHeader($pH, ['系列','英文系列']);
                $pSeriesCn = $this->findHeader($pH, ['中文系列','系列中文','中文名稱']);
                $pBrand = $this->findHeader($pH, ['廠牌','品牌']);
                $pProduct = $this->findHeader($pH, ['原廠品名','產品名稱','品名']);
                $pSize = $this->findHeader($pH, ['尺寸(cm)','尺寸']);
                $pImage = $this->findHeader($pH, ['單片連結網址','單片圖','圖片網址']);
                for ($i = 1; $i < count($priceData); $i++) {
                    $sku = $this->cleanSku($this->getVal($priceData[$i], $pCode));
                    if (!$sku) continue;
                    $profiles[$sku] = [
                        'series' => $pSeries !== -1 ? trim($this->getVal($priceData[$i], $pSeries)) : '',
                        'seriesCn' => $pSeriesCn !== -1 ? trim($this->getVal($priceData[$i], $pSeriesCn)) : '',
                        'brand' => $pBrand !== -1 ? $this->normalizeBrand($this->getVal($priceData[$i], $pBrand)) : '',
                        'productName' => $pProduct !== -1 ? trim($this->getVal($priceData[$i], $pProduct)) : '',
                        'size' => $pSize !== -1 ? $this->normalizeSizeLabel($this->getVal($priceData[$i], $pSize)) : '',
                        'imageUrl' => $pImage !== -1 ? trim($this->getVal($priceData[$i], $pImage)) : '',
                    ];
                }
            }

            $seriesMap = [];
            for ($i = 1; $i < count($cacheRows); $i++) {
                $row = $cacheRows[$i];
                $ry = (int)$this->getVal($row, $idxYear);
                $rm = (int)$this->getVal($row, $idxMonth);
                if ($ry !== $year || $rm !== $month) continue;
                $sku = $this->cleanSku($this->getVal($row, $idxSku));
                if (!$sku) continue;
                $amt = $this->optFloat($this->getVal($row, $idxAmt));
                $ping = $idxPing !== -1 ? $this->optFloat($this->getVal($row, $idxPing)) : 0;
                $cust = $idxCust !== -1 ? $this->displayCustomerName($this->getVal($row, $idxCust)) : '';
                $p = $profiles[$sku] ?? [];
                $seriesKey = ($p['seriesCn'] ?? '') ?: ($p['series'] ?? '') ?: '未分類';
                if (!isset($seriesMap[$seriesKey])) {
                    $seriesMap[$seriesKey] = [
                        'seriesCn' => $p['seriesCn'] ?? '',
                        'series' => $p['series'] ?? '',
                        'brand' => $p['brand'] ?? '',
                        'totalPings' => 0, 'totalAmount' => 0,
                        'items' => []
                    ];
                }
                $seriesMap[$seriesKey]['totalPings'] += $ping;
                $seriesMap[$seriesKey]['totalAmount'] += $amt;
                if (!isset($seriesMap[$seriesKey]['items'][$sku])) {
                    $seriesMap[$seriesKey]['items'][$sku] = [
                        'sku' => $sku,
                        'name' => trim(($p['productName'] ?? '') . ' ' . ($p['size'] ?? '')),
                        'pings' => 0, 'amount' => 0,
                        'imageUrl' => $p['imageUrl'] ?? '',
                        'customers' => []
                    ];
                }
                $seriesMap[$seriesKey]['items'][$sku]['pings'] += $ping;
                $seriesMap[$seriesKey]['items'][$sku]['amount'] += $amt;
                if ($cust && $cust !== '未知客戶') {
                    if (!isset($seriesMap[$seriesKey]['items'][$sku]['customers'][$cust])) {
                        $seriesMap[$seriesKey]['items'][$sku]['customers'][$cust] = ['name' => $cust, 'amount' => 0, 'pings' => 0];
                    }
                    $seriesMap[$seriesKey]['items'][$sku]['customers'][$cust]['amount'] += $amt;
                    $seriesMap[$seriesKey]['items'][$sku]['customers'][$cust]['pings'] += $ping;
                }
            }

            $totalAmt = array_sum(array_column($seriesMap, 'totalAmount'));
            foreach ($seriesMap as &$s) {
                $s['sharePct'] = $totalAmt > 0 ? round($s['totalAmount'] / $totalAmt * 100, 1) : 0;
                $s['items'] = array_values($s['items']);
                foreach ($s['items'] as &$item) {
                    $item['customers'] = array_values($item['customers']);
                    usort($item['customers'], function ($a, $b) { return $b['amount'] <=> $a['amount']; });
                }
                usort($s['items'], function ($a, $b) { return $b['pings'] <=> $a['pings']; });
            }
            usort($seriesMap, function ($a, $b) { return $b['totalPings'] <=> $a['totalPings']; });
            return array_slice(array_values($seriesMap), 0, 12);
        } catch (Exception $e) {
            return [];
        }
    }

    private function getCompanyMonthlyHistory($ssId, $year)
    {
        try {
            $gsClient = $this->getClient($ssId);
            $cacheRows = $gsClient->readSheet(CACHE_SHEET);
            if (count($cacheRows) < 2) return [];
            $h = $cacheRows[0];
            $idxYear = $this->findHeader($h, ['年度']);
            $idxMonth = $this->findHeader($h, ['月份']);
            $idxAmt = $this->findHeader($h, ['銷售金額']);
            if ($idxYear === -1 || $idxMonth === -1 || $idxAmt === -1) return [];

            $months = [];
            for ($y = $year - 2; $y <= $year; $y++) {
                $months[$y] = array_fill(1, 12, 0);
            }
            for ($i = 1; $i < count($cacheRows); $i++) {
                $row = $cacheRows[$i];
                $ry = (int)$this->getVal($row, $idxYear);
                $rm = (int)$this->getVal($row, $idxMonth);
                if ($ry < $year - 2 || $ry > $year) continue;
                $amt = $this->optFloat($this->getVal($row, $idxAmt));
                $months[$ry][$rm] += $amt;
            }

            $result = [];
            foreach ($months as $y => $mData) {
            $result[] = [
                'year' => $y,
                'months' => array_map(function ($v, $m) { return ['month' => $m, 'amount' => round($v)]; }, $mData, array_keys($mData)),
                'total' => round(array_sum($mData)),
            ];
            }
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }

    private function getCompanyQuarterStats($ssId, $year, $month)
    {
        try {
            $gsClient = $this->getClient($ssId);
            $cacheRows = $gsClient->readSheet(CACHE_SHEET);
            if (count($cacheRows) < 2) return [];

            $h = $cacheRows[0];
            $idxYear = $this->findHeader($h, ['年度']);
            $idxMonth = $this->findHeader($h, ['月份']);
            $idxAmount = $this->findHeader($h, ['銷售金額']);
            if ($idxYear === -1 || $idxMonth === -1 || $idxAmount === -1) return [];

            $quarters = [];
            for ($i = 1; $i < count($cacheRows); $i++) {
                $row = $cacheRows[$i];
                $ry = (int)$this->getVal($row, $idxYear);
                $rm = (int)$this->getVal($row, $idxMonth);
                $amt = $this->optFloat($this->getVal($row, $idxAmount));
                $q = ceil($rm / 3);
                $key = $ry . 'Q' . $q;
                if (!isset($quarters[$key])) $quarters[$key] = 0;
                $quarters[$key] += $amt;
            }
            return $quarters;
        } catch (Exception $e) {
            return [];
        }
    }

    private function getCompanySalesRepStats($ssId, $year, $month)
    {
        try {
            $gsClient = $this->getClient($ssId);
            $cacheRows = $gsClient->readSheet(CACHE_SHEET);
            if (count($cacheRows) < 2) return [];

            $h = $cacheRows[0];
            $idxYear = $this->findHeader($h, ['年度']);
            $idxMonth = $this->findHeader($h, ['月份']);
            $idxRep = $this->findHeader($h, ['負責業務', '業務']);
            $idxAmount = $this->findHeader($h, ['銷售金額']);
            $idxPings = $this->findHeader($h, ['銷售坪數']);
            $idxTx = $this->findHeader($h, ['交易筆數']);
            $idxCust = $this->findHeader($h, ['客戶名稱', '客戶']);
            if ($idxYear === -1 || $idxRep === -1 || $idxAmount === -1) return [];

            $reps = [];
            for ($i = 1; $i < count($cacheRows); $i++) {
                $row = $cacheRows[$i];
                $ry = (int)$this->getVal($row, $idxYear);
                $rm = (int)$this->getVal($row, $idxMonth);
                $rep = trim($this->getVal($row, $idxRep));
                $rep = self::$salesMerge[$rep] ?? $rep;
                if ($rep === '') continue;
                $amt = $this->optFloat($this->getVal($row, $idxAmount));
                $pings = $idxPings !== -1 ? $this->optFloat($this->getVal($row, $idxPings)) : 0;
                $tx = $idxTx !== -1 ? (int)$this->getVal($row, $idxTx) : 0;
                $cust = $idxCust !== -1 ? $this->displayCustomerName($this->getVal($row, $idxCust)) : '';

                if (!isset($reps[$rep])) $reps[$rep] = ['curMonth' => 0, 'prevMonth' => 0, 'ytd' => 0, 'pings' => 0, 'tx' => 0, 'customers' => []];
                if ($ry === $year && $rm === $month) {
                    $reps[$rep]['curMonth'] += $amt;
                    $reps[$rep]['pings'] += $pings;
                    $reps[$rep]['tx'] += $tx;
                    if ($cust && $cust !== '未知客戶') $reps[$rep]['customers'][$cust] = true;
                }
                if ($ry === ($year - 1) && $rm === $month) $reps[$rep]['prevMonth'] += $amt;
                if ($ry === $year && $rm <= $month) $reps[$rep]['ytd'] += $amt;
            }

            $result = [];
            foreach ($reps as $name => $data) {
                if ($data['curMonth'] <= 0 && $data['ytd'] <= 0) continue;
                $yoy = $data['prevMonth'] > 0 ? round(($data['curMonth'] - $data['prevMonth']) / $data['prevMonth'] * 100, 1) : null;
                $result[] = [
                    'name' => $name,
                    'curMonth' => round($data['curMonth']),
                    'prevMonth' => round($data['prevMonth']),
                    'ytd' => round($data['ytd']),
                    'pings' => round($data['pings'], 1),
                    'tx' => $data['tx'],
                    'customerCount' => count($data['customers']),
                    'yoy' => $yoy,
                ];
            }
            usort($result, function ($a, $b) { return $b['curMonth'] - $a['curMonth']; });
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }

    private function getCompanyInventoryBreakdown($ssId)
    {
        try {
            $gsClient = $this->getClient($ssId);

            $priceData = $gsClient->readSheet(PRICE_SHEET);
            $metaMap = [];
            $costMap = [];
            if (count($priceData) >= 2) {
                $pH = $priceData[0];
                $pCode = $this->findHeader($pH, ['編號', '產品編號']);
                $pSleeper = $this->findHeader($pH, ['睡美人']);
                $pDisc = $this->findHeader($pH, ['不續辦']);
                $pPerPing = $this->findHeader($pH, ['片/坪']);
                $pCost = $this->findHeader($pH, ['成本', '單片成本', '成本價']);
                for ($i = 1; $i < count($priceData); $i++) {
                    $sku = $this->cleanSku($this->getVal($priceData[$i], $pCode));
                    if (!$sku) continue;
                    $isSleeper = $pSleeper !== -1 && trim($this->getVal($priceData[$i], $pSleeper)) !== '';
                    $isDisc = $pDisc !== -1 && trim($this->getVal($priceData[$i], $pDisc)) !== '';
                    $type = $isDisc ? 'discontinued' : ($isSleeper ? 'sleeper' : 'normal');
                    $perPing = $pPerPing !== -1 ? ($this->optFloat($this->getVal($priceData[$i], $pPerPing)) ?: 36) : 36;
                    $cost = $pCost !== -1 ? $this->optFloat($this->getVal($priceData[$i], $pCost)) : 0;
                    $metaMap[$sku] = ['type' => $type, 'perPing' => $perPing];
                    if ($cost > 0) $costMap[$sku] = $cost;
                }
            }

            try {
                $sleeperData = $gsClient->readSheet(SLEEPER_SHEET);
                if (count($sleeperData) >= 2) {
                    $sH = $sleeperData[0];
                    $sSku = $this->findHeader($sH, ['編號', '產品編號', '品號']);
                    $sCost = $this->findHeader($sH, ['成本', '單片成本', '成本價']);
                    if ($sSku !== -1 && $sCost !== -1) {
                        for ($i = 1; $i < count($sleeperData); $i++) {
                            $sku = $this->cleanSku($this->getVal($sleeperData[$i], $sSku));
                            if (!$sku) continue;
                            $c = $this->optFloat($this->getVal($sleeperData[$i], $sCost));
                            if ($c > 0) $costMap[$sku] = $c;
                        }
                    }
                }
            } catch (Exception $e) {}

            $stockData = $gsClient->readSheet(STOCK_SHEET);
            $result = [
                'normal' => ['cost' => 0, 'pings' => 0, 'skuCount' => 0],
                'sleeper' => ['cost' => 0, 'pings' => 0, 'skuCount' => 0],
                'discontinued' => ['cost' => 0, 'pings' => 0, 'skuCount' => 0],
                'total' => ['cost' => 0, 'pings' => 0, 'skuCount' => 0],
            ];
            if (count($stockData) < 2) return $result;

            $sH = $stockData[0];
            $sCode = $this->findHeader($sH, ['編號', '產品編號']);
            $sPing = $this->findHeader($sH, ['庫存坪數', '坪數', '庫存']);
            if ($sCode === -1) return $result;

            $seen = [];
            for ($i = 1; $i < count($stockData); $i++) {
                $sku = $this->cleanSku($this->getVal($stockData[$i], $sCode));
                if (!$sku) continue;
                $ping = $sPing !== -1 ? $this->optFloat($this->getVal($stockData[$i], $sPing)) : 0;
                if ($ping <= 0) continue;

                $meta = $metaMap[$sku] ?? ['type' => 'normal', 'perPing' => 36];
                $cost = $costMap[$sku] ?? 0;
                $costPerPing = $cost * $meta['perPing'];
                $totalCost = $ping * $costPerPing;
                $type = $meta['type'];

                $result[$type]['cost'] += $totalCost;
                $result[$type]['pings'] += $ping;
                if (!isset($seen[$type][$sku])) { $result[$type]['skuCount']++; $seen[$type][$sku] = true; }
                $result['total']['cost'] += $totalCost;
                $result['total']['pings'] += $ping;
                if (!isset($seen['total'][$sku])) { $result['total']['skuCount']++; $seen['total'][$sku] = true; }
            }

            foreach (['normal', 'sleeper', 'discontinued', 'total'] as $t) {
                $result[$t]['cost'] = round($result[$t]['cost']);
                $result[$t]['pings'] = round($result[$t]['pings'], 1);
            }
            return $result;
        } catch (Exception $e) {
            return [
                'normal' => ['cost' => 0, 'pings' => 0, 'skuCount' => 0],
                'sleeper' => ['cost' => 0, 'pings' => 0, 'skuCount' => 0],
                'discontinued' => ['cost' => 0, 'pings' => 0, 'skuCount' => 0],
                'total' => ['cost' => 0, 'pings' => 0, 'skuCount' => 0],
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getGroupDetailedReport($year, $month)
    {
        $year = (int)$year;
        $month = (int)$month;
        if ($month < 1 || $month > 12) $month = (int)date('n');

        $companyIds = [
            'sleepingBeauty' => ['id' => SS_ID_MAIN, 'name' => '高雅瓷', 'color' => '#ff2a85'],
            'andyga' => ['id' => SS_ID_ANDYGA, 'name' => '安帝嘉', 'color' => '#10b981'],
            'xiyena' => ['id' => SS_ID_XIYENA, 'name' => '喜悅納', 'color' => '#38bdf8'],
        ];

        $basicStats = [];
        $allCustomers = [];
        $allProducts = [];
        $allContracts = [];
        $allQuarters = [];
        $allReps = [];
        $allInventory = [];

        foreach ($companyIds as $key => $info) {
            $basicStats[$key] = $this->getCompanyReportStats($info['id'], $year, $month);
            $allCustomers[$key] = $this->getCompanyCacheCustomers($info['id'], $year, $month);
            $allProducts[$key] = $this->getCompanyProductStats($info['id'], $year, $month);
            $allContracts[$key] = $this->getCompanyContractSummary($info['id'], $year, $month);
            $allQuarters[$key] = $this->getCompanyQuarterStats($info['id'], $year, $month);
            $allReps[$key] = $this->getCompanySalesRepStats($info['id'], $year, $month);
            $allInventory[$key] = $this->getCompanyInventoryBreakdown($info['id']);
        }

        // Get series ranking per company
        $allSeriesRanks = [];
        foreach ($companyIds as $key => $info) {
            $allSeriesRanks[$key] = $this->getCompanySeriesRanks($info['id'], $year, $month);
        }

        // Get 3-year monthly history
        $allMonthlyHistory = [];
        foreach ($companyIds as $key => $info) {
            $allMonthlyHistory[$key] = $this->getCompanyMonthlyHistory($info['id'], $year);
        }

        $groupThreeYear = [];
        foreach ($companyIds as $key => $info) {
            foreach ($allMonthlyHistory[$key] as $yrData) {
                $y = $yrData['year'];
                if (!isset($groupThreeYear[$y])) {
                    $groupThreeYear[$y] = ['year' => $y, 'months' => array_fill(1, 12, 0)];
                }
                foreach ($yrData['months'] as $m) {
                    $groupThreeYear[$y]['months'][$m['month']] += $m['amount'];
                }
            }
        }
        ksort($groupThreeYear);
        foreach ($groupThreeYear as $y => &$yr) {
            $yr['total'] = round(array_sum($yr['months']));
            $yr['months'] = array_map(function ($v, $m) { return ['month' => $m, 'amount' => round($v)]; }, $yr['months'], array_keys($yr['months']));
        }
        unset($yr);
        $groupThreeYear = array_values($groupThreeYear);

        $groupSeriesRanks = [];
        foreach ($companyIds as $key => $info) {
            foreach ($allSeriesRanks[$key] as $s) {
                $sk = ($s['seriesCn'] ?: $s['series']) ?: '未分類';
                if (!isset($groupSeriesRanks[$sk])) {
                    $groupSeriesRanks[$sk] = $s + ['companies' => [$info['name']], 'items' => []];
                } else {
                    $groupSeriesRanks[$sk]['totalPings'] += $s['totalPings'];
                    $groupSeriesRanks[$sk]['totalAmount'] += $s['totalAmount'];
                    $groupSeriesRanks[$sk]['companies'][] = $info['name'];
                }
            }
        }
        $gTotalAmt = array_sum(array_column($groupSeriesRanks, 'totalAmount'));
        foreach ($groupSeriesRanks as &$s) {
            $s['sharePct'] = $gTotalAmt > 0 ? round($s['totalAmount'] / $gTotalAmt * 100, 1) : 0;
        }
        usort($groupSeriesRanks, function ($a, $b) { return $b['totalPings'] <=> $a['totalPings']; });
        $groupSeriesRanks = array_slice(array_values($groupSeriesRanks), 0, 12);

        $groupKpis = ['sales' => 0, 'salesYoyBase' => 0, 'pings' => 0, 'ytdSales' => 0, 'ytdPrevSales' => 0];
        $groupMonthlySales = array_fill(1, 12, 0);
        foreach ($companyIds as $key => $info) {
            $bs = $basicStats[$key];
            if ($bs['success']) {
                $groupKpis['sales'] += $bs['kpis']['sales'];
                $groupKpis['salesYoyBase'] += $bs['kpis']['salesYoyBase'];
                $groupKpis['pings'] += $bs['kpis']['pings'];
                $groupKpis['ytdSales'] += $bs['kpis']['ytdSales'];
                $groupKpis['ytdPrevSales'] += $bs['kpis']['ytdPrevSales'];
                foreach ($bs['monthlySales'] as $mi => $mv) {
                    $groupMonthlySales[$mi + 1] = ($groupMonthlySales[$mi + 1] ?? 0) + $mv;
                }
            }
        }
        $groupKpis['salesYoyPct'] = $groupKpis['salesYoyBase'] > 0 ? round(($groupKpis['sales'] - $groupKpis['salesYoyBase']) / $groupKpis['salesYoyBase'] * 100, 1) : 0;
        $groupKpis['ytdYoyPct'] = $groupKpis['ytdPrevSales'] > 0 ? round(($groupKpis['ytdSales'] - $groupKpis['ytdPrevSales']) / $groupKpis['ytdPrevSales'] * 100, 1) : 0;

        $groupLastMonthSales = 0;
        foreach ($companyIds as $key => $info) {
            if (isset($allCustomers[$key])) {
                foreach ($allCustomers[$key] as $cust => $data) {
                    $groupLastMonthSales += $data['lastMonth'] ?? 0;
                }
            }
        }
        $groupKpis['lastMonthSales'] = round($groupLastMonthSales);

        $mergedCustomers = [];
        foreach ($companyIds as $key => $info) {
            foreach ($allCustomers[$key] as $cust => $data) {
                if (!isset($mergedCustomers[$cust])) {
                    $mergedCustomers[$cust] = ['companies' => [], 'totalMonth' => 0, 'totalPrevMonth' => 0, 'totalLastMonth' => 0, 'totalYtd' => 0, 'totalYtdPrev' => 0, 'totalTx' => 0];
                }
                $lastMonthAmt = $data['lastMonth'] ?? 0;
                if ($data['curMonth'] > 0 || $data['ytd'] > 0 || $data['prevMonth'] > 0 || $data['ytdPrev'] > 0 || $lastMonthAmt > 0) {
                    $mergedCustomers[$cust]['companies'][$key] = [
                        'name' => $info['name'],
                        'curMonth' => round($data['curMonth']),
                        'prevMonth' => round($data['prevMonth']),
                        'lastMonth' => round($lastMonthAmt),
                        'ytd' => round($data['ytd']),
                    ];
                    $mergedCustomers[$cust]['totalMonth'] += $data['curMonth'];
                    $mergedCustomers[$cust]['totalPrevMonth'] += $data['prevMonth'];
                    $mergedCustomers[$cust]['totalLastMonth'] += $lastMonthAmt;
                    $mergedCustomers[$cust]['totalYtd'] += $data['ytd'];
                    $mergedCustomers[$cust]['totalYtdPrev'] += $data['ytdPrev'];
                    $mergedCustomers[$cust]['totalTx'] += $data['curMonthTx'];
                }
            }
        }

        uasort($mergedCustomers, function ($a, $b) { return $b['totalMonth'] - $a['totalMonth']; });
        $top20 = [];
        $i = 0;
        foreach ($mergedCustomers as $name => $data) {
            if ($i >= 20) break;
            $yoy = $data['totalPrevMonth'] > 0 ? round(($data['totalMonth'] - $data['totalPrevMonth']) / $data['totalPrevMonth'] * 100, 1) : ($data['totalMonth'] > 0 ? 999 : 0);
            $alerts = [];
            if ($yoy <= -30) $alerts[] = '衰退警告';
            if ($yoy >= 50 && $yoy < 999) $alerts[] = '急升';
            if ($yoy === 999) $alerts[] = '新客';
            if ($data['totalPrevMonth'] > 300000 && $data['totalMonth'] === 0) $alerts[] = '流失';
            $top20[] = [
                'name' => $name,
                'companies' => $data['companies'],
                'totalMonth' => round($data['totalMonth']),
                'totalPrevMonth' => round($data['totalPrevMonth']),
                'totalYtd' => round($data['totalYtd']),
                'totalYtdPrev' => round($data['totalYtdPrev']),
                'yoy' => $yoy === 999 ? null : $yoy,
                'isNew' => $yoy === 999,
                'alerts' => $alerts,
            ];
            $i++;
        }

        $crossCompanyCustomers = [];
        foreach ($mergedCustomers as $name => $data) {
            if (count($data['companies']) >= 2 && $data['totalMonth'] > 0) {
                $crossCompanyCustomers[] = [
                    'name' => $name,
                    'companies' => $data['companies'],
                    'totalMonth' => round($data['totalMonth']),
                    'totalTx' => $data['totalTx'],
                ];
            }
        }
        usort($crossCompanyCustomers, function ($a, $b) { return $b['totalMonth'] - $a['totalMonth']; });
        $crossCompanyCustomers = array_slice($crossCompanyCustomers, 0, 10);

        $contractTotal = ['active' => 0, 'monthlyTarget' => 0, 'healthCounts' => []];
        $contractOverlap = [];
        $contractAllCustomers = [];
        foreach ($companyIds as $key => $info) {
            $c = $allContracts[$key];
            $contractTotal['monthlyTarget'] += $c['monthlyTarget'];
            foreach ($c['healthCounts'] as $bucket => $cnt) {
                $contractTotal['healthCounts'][$bucket] = ($contractTotal['healthCounts'][$bucket] ?? 0) + $cnt;
            }
            foreach ($c['customers'] as $custName => $custData) {
                $contractAllCustomers[$custName] = true;
                if (!isset($contractOverlap[$custName])) $contractOverlap[$custName] = [];
                $contractOverlap[$custName][$key] = ['name' => $info['name'], 'health' => $custData['health'], 'target' => $custData['target']];
            }
        }
        $contractTotal['active'] = count($contractAllCustomers);
        $overlapContracts = [];
        foreach ($contractOverlap as $custName => $coMap) {
            if (count($coMap) >= 2) {
                $totalTarget = 0;
                foreach ($coMap as $co) $totalTarget += $co['target'];
                $actualMonth = isset($mergedCustomers[$custName]) ? round($mergedCustomers[$custName]['totalMonth']) : 0;
                $actualLastMonth = isset($mergedCustomers[$custName]) ? round($mergedCustomers[$custName]['totalLastMonth']) : 0;
                $overlapContracts[] = ['name' => $custName, 'companies' => $coMap, 'totalTarget' => round($totalTarget), 'actual' => $actualMonth, 'actualLastMonth' => $actualLastMonth];
            }
        }

        $companyData = [];
        foreach ($companyIds as $key => $info) {
            $bs = $basicStats[$key];
            $ct = $allContracts[$key];
            
            // Calculate signed store sales and health percentage
            $signedStoreSales = 0;
            if (isset($ct['customers']) && isset($allCustomers[$key])) {
                foreach ($allCustomers[$key] as $cust => $cData) {
                    if (isset($ct['customers'][$cust])) {
                        $healthBucket = $ct['customers'][$cust]['health'] ?? '';
                        if (in_array($healthBucket, ['正常', '觀察', '警示', '掛點', '待續約'], true)) {
                            $signedStoreSales += $cData['curMonth'] ?? 0;
                        }
                    }
                }
            }
            $healthPct = $ct['monthlyTarget'] > 0 ? round($signedStoreSales / $ct['monthlyTarget'] * 100, 1) : 0;
            
            // Get top 10 products
            $topProds = $this->getCompanyTopProducts($info['id'], $year, $month, 10);
            
            $curQ = ceil($month / 3);
            $qKey = $year . 'Q' . $curQ;
            $prevQKey = $curQ > 1 ? ($year . 'Q' . ($curQ - 1)) : (($year - 1) . 'Q4');
            $lastYearQKey = ($year - 1) . 'Q' . $curQ;
            $companyData[$key] = [
                'name' => $info['name'],
                'color' => $info['color'],
                'success' => $bs['success'],
                'error' => $bs['msg'] ?? '',
                'kpis' => $bs['success'] ? $bs['kpis'] : null,
                'monthlySales' => $bs['success'] ? $bs['monthlySales'] : null,
                'products' => $allProducts[$key],
                'topProducts' => $topProds,
                'seriesRanking' => $allSeriesRanks[$key] ?? [],
                'monthlyHistory' => $allMonthlyHistory[$key] ?? [],
                'contract' => [
                    'active' => $ct['active'],
                    'monthlyTarget' => $ct['monthlyTarget'],
                    'healthCounts' => $ct['healthCounts'],
                    'detail' => $ct['detail'],
                    'signedStoreSales' => round($signedStoreSales),
                    'healthPct' => $healthPct,
                ],
                'quarter' => [
                    'current' => ['label' => $qKey, 'amount' => round($allQuarters[$key][$qKey] ?? 0)],
                    'prev' => ['label' => $prevQKey, 'amount' => round($allQuarters[$key][$prevQKey] ?? 0)],
                    'lastYear' => ['label' => $lastYearQKey, 'amount' => round($allQuarters[$key][$lastYearQKey] ?? 0)],
                ],
                'inventory' => $allInventory[$key],
            ];
        }

        $groupAlerts = [];
        foreach ($companyIds as $key => $info) {
            $bs = $basicStats[$key];
            if ($bs['success'] && $bs['kpis']['salesYoyPct'] < -5) {
                $groupAlerts[] = $info['name'] . ' 本月 YOY ' . $bs['kpis']['salesYoyPct'] . '%，需留意';
            }
        }
        foreach ($top20 as $c) {
            if (in_array('衰退警告', $c['alerts'])) {
                $groupAlerts[] = $c['name'] . ' 集團合計 YOY ' . $c['yoy'] . '%（去年同期 ' . round($c['totalPrevMonth'] / 10000) . ' 萬 → ' . round($c['totalMonth'] / 10000) . ' 萬）';
            }
            if (in_array('流失', $c['alerts'])) {
                $groupAlerts[] = $c['name'] . ' 去年同期 ' . round($c['totalPrevMonth'] / 10000) . ' 萬，今年歸零，疑似流失';
            }
        }

        return [
            'success' => true,
            'data' => [
                'year' => $year,
                'month' => $month,
                'group' => [
                    'kpis' => $groupKpis,
                    'monthlySales' => array_values($groupMonthlySales),
                    'contractTotal' => $contractTotal,
                    'threeYear' => $groupThreeYear,
                    'seriesRanking' => $groupSeriesRanks,
                ],
                'companies' => $companyData,
                'top20' => $top20,
                'crossCompany' => $crossCompanyCustomers,
                'overlapContracts' => $overlapContracts,
                'alerts' => $groupAlerts,
            ]
        ];
    }

    public function getManagerReports($year, $month)
    {
        try {
            if (!is_dir(AI_ADVISOR_CACHE_DIR)) {
                @mkdir(AI_ADVISOR_CACHE_DIR, 0775, true);
            }
            $cacheFile = AI_ADVISOR_CACHE_DIR . "/mgr_reports_{$year}_{$month}.json";
            if (is_file($cacheFile)) {
                $cached = json_decode(file_get_contents($cacheFile), true);
                if ($cached) {
                    return ['success' => true, 'data' => $cached];
                }
            }

            $gsClient = new GoogleSheetsClient(SS_ID_MAIN);
            try {
                $rows = $gsClient->readSheet('主管報告');
            } catch (Exception $e) {
                $rows = [];
            }
            if (count($rows) < 2) {
                $default = [
                    'sleepingBeauty' => ['marketingPlan' => '', 'communication' => '', 'otherReport' => ''],
                    'andyga' => ['marketingPlan' => '', 'communication' => '', 'otherReport' => ''],
                    'xiyena' => ['marketingPlan' => '', 'communication' => '', 'otherReport' => ''],
                ];
                @file_put_contents($cacheFile, json_encode($default, JSON_UNESCAPED_UNICODE));
                return ['success' => true, 'data' => $default];
            }

            $h = $rows[0];
            $idx = [
                'year' => $this->findHeader($h, ['年度']),
                'month' => $this->findHeader($h, ['月份']),
                'co' => $this->findHeader($h, ['公司別']),
                'mkt' => $this->findHeader($h, ['行銷計畫']),
                'comm' => $this->findHeader($h, ['集團內部溝通']),
                'other' => $this->findHeader($h, ['其它報告']),
            ];

            $result = [
                'sleepingBeauty' => ['marketingPlan' => '', 'communication' => '', 'otherReport' => ''],
                'andyga' => ['marketingPlan' => '', 'communication' => '', 'otherReport' => ''],
                'xiyena' => ['marketingPlan' => '', 'communication' => '', 'otherReport' => ''],
            ];

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $rY = (int)$this->getVal($row, $idx['year']);
                $rM = (int)$this->getVal($row, $idx['month']);
                if ($rY !== (int)$year || $rM !== (int)$month) continue;

                $coKey = trim($this->getVal($row, $idx['co']));
                if (isset($result[$coKey])) {
                    $result[$coKey] = [
                        'marketingPlan' => $this->getVal($row, $idx['mkt']),
                        'communication' => $this->getVal($row, $idx['comm']),
                        'otherReport' => $this->getVal($row, $idx['other']),
                    ];
                }
            }

            @file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE));
            return ['success' => true, 'data' => $result];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    public function saveManagerReport($year, $month, $coKey, $mkt, $comm, $other)
    {
        try {
            $gsClient = new GoogleSheetsClient(SS_ID_MAIN);
            
            try {
                $rows = $gsClient->readSheet('主管報告');
            } catch (Exception $e) {
                $gsClient->createSheet('主管報告');
                $rows = [];
            }

            $headers = ['年度', '月份', '公司別', '行銷計畫', '集團內部溝通', '其它報告', '更新時間'];
            if (empty($rows)) {
                $gsClient->appendRows('主管報告', [$headers]);
                $rows = [$headers];
            }

            $h = $rows[0];
            $idx = [
                'year' => $this->findHeader($h, ['年度']),
                'month' => $this->findHeader($h, ['月份']),
                'co' => $this->findHeader($h, ['公司別']),
                'mkt' => $this->findHeader($h, ['行銷計畫']),
                'comm' => $this->findHeader($h, ['集團內部溝通']),
                'other' => $this->findHeader($h, ['其它報告']),
                'time' => $this->findHeader($h, ['更新時間']),
            ];

            $foundRowIdx = -1;
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $rY = (int)$this->getVal($row, $idx['year']);
                $rM = (int)$this->getVal($row, $idx['month']);
                $rCo = trim($this->getVal($row, $idx['co']));
                if ($rY === (int)$year && $rM === (int)$month && $rCo === $coKey) {
                    $foundRowIdx = $i + 1;
                    break;
                }
            }

            $newRow = [];
            $newRow[$idx['year']] = (int)$year;
            $newRow[$idx['month']] = (int)$month;
            $newRow[$idx['co']] = $coKey;
            $newRow[$idx['mkt']] = $mkt;
            $newRow[$idx['comm']] = $comm;
            $newRow[$idx['other']] = $other;
            $newRow[$idx['time']] = date('Y-m-d H:i:s');

            $maxCol = count($headers);
            $normalizedRow = [];
            for ($c = 0; $c < $maxCol; $c++) {
                $normalizedRow[] = $newRow[$c] ?? '';
            }

            if ($foundRowIdx !== -1) {
                $gsClient->writeRows('主管報告', $foundRowIdx, [$normalizedRow]);
            } else {
                $gsClient->appendRows('主管報告', [$normalizedRow]);
            }

            if (!is_dir(AI_ADVISOR_CACHE_DIR)) {
                @mkdir(AI_ADVISOR_CACHE_DIR, 0775, true);
            }
            $cacheFile = AI_ADVISOR_CACHE_DIR . "/mgr_reports_{$year}_{$month}.json";
            
            $cached = [];
            if (is_file($cacheFile)) {
                $cached = json_decode(file_get_contents($cacheFile), true);
            }
            if (!$cached) {
                $cached = [
                    'sleepingBeauty' => ['marketingPlan' => '', 'communication' => '', 'otherReport' => ''],
                    'andyga' => ['marketingPlan' => '', 'communication' => '', 'otherReport' => ''],
                    'xiyena' => ['marketingPlan' => '', 'communication' => '', 'otherReport' => ''],
                ];
            }
            $cached[$coKey] = [
                'marketingPlan' => $mkt,
                'communication' => $comm,
                'otherReport' => $other,
            ];
            @file_put_contents($cacheFile, json_encode($cached, JSON_UNESCAPED_UNICODE));

            return ['success' => true, 'msg' => '儲存成功'];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }
}
