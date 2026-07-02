<?php
// ====== CustomerTrait ======
trait CustomerTrait
{

    public function getCustomerDetail($customer, $year = null)
    {
        $seriesMap = $this->getSeriesMap();
        $raw = $this->gs->readSheet(SALES_SHEET);
        if (count($raw) < 2) return ['success' => true, 'data' => []];

        $h = $raw[0];
        $idxDate  = $this->findHeader($h, ['日期','單據日期','銷貨日期']);
        $idxSales = $this->findHeader($h, ['負責業務','業務','業務員','負責人']);
        $idxCust  = $this->findHeader($h, ['客戶名稱','客戶']);
        $idxCode  = $this->findHeader($h, ['產品編號','編號','品碼','序號']);
        $idxQty   = $this->findHeader($h, ['數量','片數']);
        $idxAmt   = $this->findHeader($h, ['金額','銷額','銷售金額','成交金額','小計','總計']);
        $idxNote  = $this->findHeader($h, ['備註','說明','案名','專案','工地']);
        if ($idxCust === -1 || $idxDate === -1 || $idxCode === -1) return ['success' => true, 'data' => []];

        $results = [];
        for ($i = 1; $i < count($raw); $i++) {
            $row = $raw[$i];
            $custName = trim($this->getVal($row, $idxCust));
            if (strpos($custName, $customer) !== 0) continue;

            $d = $this->parseDate($this->getVal($row, $idxDate));
            if (!$d) continue;
            if ($year !== null && $d->format('Y') != $year) continue;

            $note = trim($this->getVal($row, $idxNote));
            if (strpos($note, '樣品') !== false || strpos($note, '扣帶') !== false) continue;

            $sku = $this->cleanSku($this->getVal($row, $idxCode));
            $qty = $this->optFloat($this->getVal($row, $idxQty));
            $amt = $qty > 0 ? $this->optFloat($this->getVal($row, $idxAmt)) : 0;
            if (!$sku || $qty == 0) continue;

            $results[] = [
                'date'   => $d->format('Y/m/d'),
                'month'  => $d->format('Y/m'),
                'sales'  => $idxSales !== -1 ? trim($this->getVal($row, $idxSales)) : '',
                'cust'   => $custName,
                'sku'    => $sku,
                'series' => isset($seriesMap[$sku]) ? $seriesMap[$sku] : '',
                'qty'    => $qty,
                'amt'    => round($amt),
                'note'   => $note
            ];
        }

        usort($results, function($a, $b) { return strcmp($a['date'], $b['date']); });

        return ['success' => true, 'data' => $results];
    }

    public function getCustomerAnalysis()
    {
        set_time_limit(120);
        $now = new DateTime();
        $thisYear = (int)$now->format('Y');
        $lastYear = $thisYear - 1;
        $todayMD = $now->format('m-d');
        $areaMap = $this->getSalesRepAreaMap();

        $customers = [];
        $productTypeAmounts = ['sleeper'=>0, 'normal'=>0, 'discontinued'=>0];
        $seriesAmounts = [];

        $getC = function ($key) use (&$customers) {
            if (!isset($customers[$key])) {
                $customers[$key] = [
                    'name' => $key,
                    'totalAmount' => 0, 'thisYearAmount' => 0, 'lastYearAmount' => 0,
                    'lastOrderDate' => null, 'visits' => 0, 'lastVisitDate' => null,
                    'noteCount' => 0, 'lastNote' => '', 'lastNoteDate' => null,
                    'catCounts' => [],
                    'marginAmt' => 0, 'marginRevenue' => 0, 'lowMarginDeals' => [],
                    'salesRepCounts' => [],
                    'contractHealth' => null, 'contractExpiry' => null, 'contractBalance' => null,
                    'activeDisplays' => [],
                    'saleCount' => 0,
                    'area' => null
                ];
            }
            return $key;
        };

        // 0. 預載睡美人/不續辦/系列映射（銷售迴圈中需要）
        $sleeperConfig = $this->getSleeperConfig();
        $sleeperMap = $sleeperConfig['success'] ? $sleeperConfig['data'] : [];
        $seriesMap = $this->getSeriesMap();
        $discMap = [];
        $pDataPre = $this->gs->readSheet(PRICE_SHEET);
        if (count($pDataPre) >= 2) {
            $pHPre = $pDataPre[0];
            $pCodePre = $this->findHeader($pHPre, ['編號','產品編號']);
            $pDiscPre = $this->findHeader($pHPre, ['不續辦']);
            if ($pCodePre !== -1 && $pDiscPre !== -1) {
                for ($pi = 1; $pi < count($pDataPre); $pi++) {
                    $pSku = $this->cleanSku($this->getVal($pDataPre[$pi], $pCodePre));
                    if ($pSku && trim($this->getVal($pDataPre[$pi], $pDiscPre)) !== '') {
                        $discMap[$pSku] = true;
                    }
                }
            }
        }

        // 1. 銷售
        $raw = $this->gs->readSheet(SALES_SHEET);
        if (count($raw) >= 2) {
            $h = $raw[0];
            $idxDate = $this->findHeader($h, ['日期','單據日期','銷貨日期']);
            $idxCust = $this->findHeader($h, ['客戶名稱','客戶']);
            $idxQty  = $this->findHeader($h, ['數量','片數']);
            $idxAmt  = $this->findHeader($h, ['金額','銷額','銷售金額','成交金額','小計','總計']);
            $idxNote = $this->findHeader($h, ['備註','說明','案名','專案','工地']);
            $idxCode = $this->findHeader($h, ['產品編號','編號','品碼','序號']);
            $idxSales = $this->findHeader($h, ['負責業務','業務','業務員','負責人']);
            $sleeperCosts = $this->getSleeperCostMap();
            $priceCosts = $this->getPriceCostMap();
            if ($idxCust !== -1 && $idxDate !== -1) {
                for ($i = 1; $i < count($raw); $i++) {
                    $row = $raw[$i];
                    $custRaw = trim($this->getVal($row, $idxCust));
                    if ($custRaw === '') continue;
                    $note = $idxNote !== -1 ? trim($this->getVal($row, $idxNote)) : '';
                    if (strpos($note, '樣品') !== false || strpos($note, '扣帶') !== false) continue;
                    $d = $this->parseDate($this->getVal($row, $idxDate));
                    if (!$d) continue;
                    $qty = $idxQty !== -1 ? $this->optFloat($this->getVal($row, $idxQty)) : 0;
                    $amt = $idxAmt !== -1 ? $this->optFloat($this->getVal($row, $idxAmt)) : 0;
                    if ($qty == 0 || $amt == 0) continue;

                    $key = $this->displayCustomerName($custRaw);
                    $getC($key);
                    $sku = $idxCode !== -1 ? $this->cleanSku($this->getVal($row, $idxCode)) : '';
                    $customers[$key]['saleCount']++;
                    $customers[$key]['totalAmount'] += $amt;
                    $y = (int)$d->format('Y');
                    if ($y === $thisYear) $customers[$key]['thisYearAmount'] += $amt;
                    if ($y === $lastYear && $d->format('m-d') <= $todayMD) $customers[$key]['lastYearAmount'] += $amt;

                    // 按產品類型統計
                    if (isset($sleeperMap[$sku])) {
                        $productTypeAmounts['sleeper'] += $amt;
                    } elseif (isset($discMap[$sku])) {
                        $productTypeAmounts['discontinued'] += $amt;
                    } else {
                        $productTypeAmounts['normal'] += $amt;
                    }

                    // 按系列統計
                    $series = $seriesMap[$sku] ?? '其他';
                    if (!isset($seriesAmounts[$series])) $seriesAmounts[$series] = 0;
                    $seriesAmounts[$series] += $amt;
                    $ds = $d->format('Y-m-d');
                    if ($customers[$key]['lastOrderDate'] === null || $ds > $customers[$key]['lastOrderDate']) {
                        $customers[$key]['lastOrderDate'] = $ds;
                    }

                    $rep = $idxSales !== -1 ? trim($this->getVal($row, $idxSales)) : '';
                    if ($rep !== '') {
                        $rep = self::$salesMerge[$rep] ?? $rep;
                        if (!isset($customers[$key]['salesRepCounts'][$rep])) $customers[$key]['salesRepCounts'][$rep] = 0;
                        $customers[$key]['salesRepCounts'][$rep]++;
                    }

                    $cost = $sku !== '' ? ($sleeperCosts[$sku] ?? $priceCosts[$sku] ?? null) : null;
                    if ($cost !== null && $amt != 0) {
                        $margin = $amt - $cost * $qty;
                        $marginPct = round($margin / $amt * 100, 1);
                        $customers[$key]['marginAmt'] += $margin;
                        $customers[$key]['marginRevenue'] += $amt;
                        if ($marginPct < 15) {
                            $customers[$key]['lowMarginDeals'][] = [
                                'date' => $ds, 'sku' => $sku, 'qty' => $qty,
                                'amount' => round($amt), 'marginPct' => $marginPct,
                                'note' => $idxNote !== -1 ? trim($this->getVal($row, $idxNote)) : ''
                            ];
                        }
                    }
                }
            }
        }

        // 2. 拜訪 (智能_工作日誌)
        try {
            $gsLayout = new GoogleSheetsClient(SS_ID_LAYOUT);
            $workRows = $gsLayout->readSheet('工作日誌');
            if (count($workRows) >= 2) {
                $h = $workRows[0];
                $idxDate = $this->findHeader($h, ['日期']);
                $idxCustomer = $this->findHeader($h, ['客戶名稱','客戶']);
                $idxTask = $this->findHeader($h, ['任務摘要','摘要']);
                for ($i = 1; $i < count($workRows); $i++) {
                    $row = $workRows[$i];
                    $d = $this->parseDate($this->getVal($row, $idxDate));
                    if (!$d) continue;
                    $names = $this->parseWorkLogCustomers($this->getVal($row, $idxCustomer));
                    $taskText = $idxTask !== -1 ? $this->getVal($row, $idxTask) : '';
                    $pieces = $taskText !== '' ? preg_split('/\|\|/u', $taskText) : [];
                    foreach ($names as $key) {
                        $getC($key);
                        $customers[$key]['visits'] += 1;
                        $ds = $d->format('Y-m-d');
                        if ($customers[$key]['lastVisitDate'] === null || $ds > $customers[$key]['lastVisitDate']) {
                            $customers[$key]['lastVisitDate'] = $ds;
                        }
                        if (count($pieces) > 0) {
                            foreach ($pieces as $piece) {
                                $cat = $this->categorizeVisitTask($piece);
                                if (!isset($customers[$key]['catCounts'][$cat])) $customers[$key]['catCounts'][$cat] = 0;
                                $customers[$key]['catCounts'][$cat]++;
                            }
                        } else {
                            $cat = '其他';
                            if (!isset($customers[$key]['catCounts'][$cat])) $customers[$key]['catCounts'][$cat] = 0;
                            $customers[$key]['catCounts'][$cat]++;
                        }
                    }
                }
            }

            // 3. 客戶備註歷史
            $noteRows = $gsLayout->readSheet('智能_客戶備註歷史');
            if (count($noteRows) >= 2) {
                $h = $noteRows[0];
                $idxDate = $this->findHeader($h, ['日期']);
                $idxCustomer = $this->findHeader($h, ['客戶名稱','客戶']);
                $idxNote = $this->findHeader($h, ['備註']);
                for ($i = 1; $i < count($noteRows); $i++) {
                    $row = $noteRows[$i];
                    $custRaw = trim($this->getVal($row, $idxCustomer));
                    if ($custRaw === '') continue;
                    $key = $this->displayCustomerName($custRaw);
                    $getC($key);
                    $customers[$key]['noteCount'] += 1;
                    $ds = trim($this->getVal($row, $idxDate));
                    $note = trim($this->getVal($row, $idxNote));
                    if ($customers[$key]['lastNoteDate'] === null || $ds > $customers[$key]['lastNoteDate']) {
                        $customers[$key]['lastNoteDate'] = $ds;
                        $customers[$key]['lastNote'] = $note;
                    }
                }
            }
        } catch (Exception $e) {
            // 拜訪/備註資料來源若讀取失敗，仍回傳銷售統計
        }

        // 4. 合約 (高雅瓷內部管理)
        try {
            $contractRows = $this->gs->readSheet('合約');
            if (count($contractRows) >= 3) {
                $h0 = $contractRows[0];
                $h1 = $contractRows[1];
                $h = [];
                for ($ci = 0; $ci < max(count($h0), count($h1)); $ci++) {
                    $v1 = trim($h1[$ci] ?? '');
                    $h[$ci] = $v1 !== '' ? $v1 : ($h0[$ci] ?? '');
                }
                $idxHealth = $this->findHeader($h, ['健康度']);
                $idxCust = $this->findHeader($h, ['客戶']);
                $idxExpiry = $this->findHeader($h, ['最後一張票期']);
                $idxSalesC = $this->findHeader($h, ['業務']);
                $idxBalance = $idxSalesC > 0 ? $idxSalesC - 1 : -1;
                for ($i = 2; $i < count($contractRows); $i++) {
                    $row = $contractRows[$i];
                    $custRaw = trim($this->getVal($row, $idxCust));
                    if ($custRaw === '') continue;
                    $key = $this->displayCustomerName($custRaw);
                    $getC($key);
                    $customers[$key]['contractHealth'] = $idxHealth !== -1 ? trim($this->getVal($row, $idxHealth)) : null;
                    $customers[$key]['contractExpiry'] = $idxExpiry !== -1 ? trim($this->getVal($row, $idxExpiry)) : null;
                    $customers[$key]['contractBalance'] = $idxBalance >= 0 ? $this->optFloat($this->getVal($row, $idxBalance)) : null;
                }
            }
        } catch (Exception $e) {
        }

        // 5. 版面 (現正陳列中)
        try {
            $displaysMap = $this->getActiveDisplaysMap();
            foreach ($displaysMap as $sku => $entries) {
                foreach ($entries as $entry) {
                    $custRaw = trim($entry['cust'] ?? '');
                    if ($custRaw === '') continue;
                    $key = $this->displayCustomerName($custRaw);
                    $getC($key);
                    $d = $this->parseDate($entry['date'] ?? '');
                    $days = $d ? (int)$now->diff($d)->days : null;
                    $customers[$key]['activeDisplays'][] = [
                        'sku' => $sku,
                        'date' => $entry['date'] ?? '',
                        'days' => $days
                    ];
                }
            }
        } catch (Exception $e) {
        }

        $result = [];
        foreach ($customers as $key => $c) {
            $daysSinceLastOrder = $c['lastOrderDate'] ? (int)$now->diff(new DateTime($c['lastOrderDate']))->days : null;
            $yoyPct = $c['lastYearAmount'] > 0
                ? round((($c['thisYearAmount'] - $c['lastYearAmount']) / $c['lastYearAmount']) * 100, 1)
                : null;

            if ($c['totalAmount'] <= 0) {
                $health = 'no_sales';
            } elseif ($daysSinceLastOrder !== null && $daysSinceLastOrder > 180) {
                $health = 'dormant';
            } elseif ($daysSinceLastOrder !== null && $daysSinceLastOrder > 90) {
                $health = 'warning';
            } elseif ($yoyPct !== null && $yoyPct >= 10) {
                $health = 'growth';
            } elseif ($yoyPct !== null && $yoyPct <= -30) {
                $health = 'decline';
            } else {
                $health = 'normal';
            }

            $result[] = [
                'name' => $key,
                'totalAmount' => round($c['totalAmount']),
                'thisYearAmount' => round($c['thisYearAmount']),
                'lastYearAmount' => round($c['lastYearAmount']),
                'yoyPct' => $yoyPct,
                'lastOrderDate' => $c['lastOrderDate'],
                'daysSinceLastOrder' => $daysSinceLastOrder,
                'visits' => $c['visits'],
                'saleCount' => $c['saleCount'],
                'lastVisitDate' => $c['lastVisitDate'],
                'noteCount' => $c['noteCount'],
                'lastNote' => $c['lastNote'],
                'lastNoteDate' => $c['lastNoteDate'],
                'health' => $health,
                'catCounts' => $c['catCounts'],
                'salesRep' => (function ($counts) {
                    if (empty($counts)) return '未分配';
                    arsort($counts);
                    return array_key_first($counts);
                })($c['salesRepCounts']),
                'area' => (function ($counts) use ($areaMap) {
                    if (empty($counts)) return '未分配';
                    arsort($counts);
                    $rep = array_key_first($counts);
                    return $areaMap[$rep] ?? '未分配';
                })($c['salesRepCounts']),
                'contractHealth' => $c['contractHealth'],
                'contractExpiry' => $c['contractExpiry'],
                'contractBalance' => $c['contractBalance'] !== null ? round($c['contractBalance']) : null,
                'avgMarginPct' => $c['marginRevenue'] > 0 ? round($c['marginAmt'] / $c['marginRevenue'] * 100, 1) : null,
                'lowMarginDeals' => (function ($deals) {
                    usort($deals, function ($a, $b) { return $a['marginPct'] <=> $b['marginPct']; });
                    return array_slice($deals, 0, 5);
                })($c['lowMarginDeals']),
                'activeDisplayCount' => count($c['activeDisplays']),
                'activeDisplays' => (function ($disps) {
                    usort($disps, function ($a, $b) { return ($b['days'] ?? -1) <=> ($a['days'] ?? -1); });
                    return array_slice($disps, 0, 10);
                })($c['activeDisplays'])
            ];
        }

        usort($result, function ($a, $b) { return $b['totalAmount'] <=> $a['totalAmount']; });

        // 月報摘要（僅統計有銷售紀錄且交易 >= 15 筆的客戶，排除單一案件與拜訪/備註誤判出來的假客戶）
        $activeCustomers = array_values(array_filter($result, function ($c) { return $c['totalAmount'] > 0 && $c['saleCount'] >= 15; }));
        $summary = [
            'customerCount' => count($activeCustomers),
            'totalAmount' => 0, 'thisYearAmount' => 0, 'lastYearAmount' => 0,
            'healthCounts' => ['growth'=>0,'decline'=>0,'warning'=>0,'dormant'=>0,'normal'=>0,'no_sales'=>0],
            'catCounts' => [],
            'topCustomers' => [],
            'totalActiveDisplays' => 0,
            'productTypeAmounts' => ['sleeper'=>0, 'normal'=>0, 'discontinued'=>0],
            'topSeries' => []
        ];
        foreach ($activeCustomers as $c) {
            $summary['totalAmount'] += $c['totalAmount'];
            $summary['thisYearAmount'] += $c['thisYearAmount'];
            $summary['lastYearAmount'] += $c['lastYearAmount'];
            $summary['healthCounts'][$c['health']]++;
            $summary['totalActiveDisplays'] += $c['activeDisplayCount'];
            foreach ($c['catCounts'] as $cat => $cnt) {
                if (!isset($summary['catCounts'][$cat])) $summary['catCounts'][$cat] = 0;
                $summary['catCounts'][$cat] += $cnt;
            }
        }
        $summary['yoyPct'] = $summary['lastYearAmount'] > 0
            ? round((($summary['thisYearAmount'] - $summary['lastYearAmount']) / $summary['lastYearAmount']) * 100, 1)
            : null;
        $top10 = array_slice($activeCustomers, 0, 10);
        $summary['topCustomers'] = array_map(function ($c) {
            return ['name' => $c['name'], 'totalAmount' => $c['totalAmount'], 'yoyPct' => $c['yoyPct'], 'health' => $c['health']];
        }, $top10);

        // 產品類型佔比
        $summary['productTypeAmounts'] = $productTypeAmounts;

        // 前10大系列
        arsort($seriesAmounts);
        $topSeriesArray = array_slice($seriesAmounts, 0, 10, true);
        $summary['topSeries'] = array_map(function ($series, $amount) {
            return ['name' => $series, 'amount' => $amount];
        }, array_keys($topSeriesArray), array_values($topSeriesArray));

        return ['success' => true, 'data' => $result, 'summary' => $summary];
    }

    public function getCustomerTimeline($customer)
    {
        $key = $this->displayCustomerName($customer);
        $timeline = [];

        // 銷售
        $raw = $this->gs->readSheet(SALES_SHEET);
        if (count($raw) >= 2) {
            $h = $raw[0];
            $idxDate = $this->findHeader($h, ['日期','單據日期','銷貨日期']);
            $idxSales = $this->findHeader($h, ['負責業務','業務','業務員','負責人']);
            $idxCust  = $this->findHeader($h, ['客戶名稱','客戶']);
            $idxCode  = $this->findHeader($h, ['產品編號','編號','品碼','序號']);
            $idxQty   = $this->findHeader($h, ['數量','片數']);
            $idxAmt   = $this->findHeader($h, ['金額','銷額','銷售金額','成交金額','小計','總計']);
            $idxNote  = $this->findHeader($h, ['備註','說明','案名','專案','工地']);
            if ($idxCust !== -1 && $idxDate !== -1) {
                for ($i = 1; $i < count($raw); $i++) {
                    $row = $raw[$i];
                    $custRaw = trim($this->getVal($row, $idxCust));
                    if ($custRaw === '' || $this->displayCustomerName($custRaw) !== $key) continue;
                    $d = $this->parseDate($this->getVal($row, $idxDate));
                    if (!$d) continue;
                    $note = $idxNote !== -1 ? trim($this->getVal($row, $idxNote)) : '';
                    if (strpos($note, '樣品') !== false || strpos($note, '扣帶') !== false) continue;
                    $sku = $idxCode !== -1 ? $this->cleanSku($this->getVal($row, $idxCode)) : '';
                    $qty = $idxQty !== -1 ? $this->optFloat($this->getVal($row, $idxQty)) : 0;
                    $amt = $idxAmt !== -1 ? $this->optFloat($this->getVal($row, $idxAmt)) : 0;
                    if ($qty == 0 || $amt == 0) continue;
                    $sales = $idxSales !== -1 ? trim($this->getVal($row, $idxSales)) : '';
                    $sales = self::$salesMerge[$sales] ?? $sales;
                    $timeline[] = [
                        'date' => $d->format('Y-m-d'),
                        'type' => '銷售',
                        'sales' => $sales,
                        'desc' => $sku . ' ' . $qty . '片 / ' . round($amt) . '元'
                    ];
                }
            }
        }

        try {
            $gsLayout = new GoogleSheetsClient(SS_ID_LAYOUT);

            // 拜訪
            $workRows = $gsLayout->readSheet('工作日誌');
            if (count($workRows) >= 2) {
                $h = $workRows[0];
                $idxDate = $this->findHeader($h, ['日期']);
                $idxSales = $this->findHeader($h, ['業務姓名','業務']);
                $idxCustomer = $this->findHeader($h, ['客戶名稱','客戶']);
                $idxTask = $this->findHeader($h, ['任務摘要','摘要']);
                for ($i = 1; $i < count($workRows); $i++) {
                    $row = $workRows[$i];
                    $d = $this->parseDate($this->getVal($row, $idxDate));
                    if (!$d) continue;
                    $names = $this->parseWorkLogCustomers($this->getVal($row, $idxCustomer));
                    if (!in_array($key, $names)) continue;
                    $taskDesc = $idxTask !== -1 ? trim($this->getVal($row, $idxTask)) : '';
                    $timeline[] = [
                        'date' => $d->format('Y-m-d'),
                        'type' => '拜訪',
                        'sales' => $idxSales !== -1 ? trim($this->getVal($row, $idxSales)) : '',
                        'desc' => $taskDesc,
                        'category' => $this->categorizeVisitTask($taskDesc)
                    ];
                }
            }

            // 客戶備註歷史
            $noteRows = $gsLayout->readSheet('智能_客戶備註歷史');
            if (count($noteRows) >= 2) {
                $h = $noteRows[0];
                $idxDate = $this->findHeader($h, ['日期']);
                $idxSales = $this->findHeader($h, ['業務姓名','業務']);
                $idxCustomer = $this->findHeader($h, ['客戶名稱','客戶']);
                $idxNote = $this->findHeader($h, ['備註']);
                for ($i = 1; $i < count($noteRows); $i++) {
                    $row = $noteRows[$i];
                    $custRaw = trim($this->getVal($row, $idxCustomer));
                    if ($custRaw === '' || $this->displayCustomerName($custRaw) !== $key) continue;
                    $ds = trim($this->getVal($row, $idxDate));
                    if ($ds === '') continue;
                    $timeline[] = [
                        'date' => $ds,
                        'type' => '備註',
                        'sales' => $idxSales !== -1 ? trim($this->getVal($row, $idxSales)) : '',
                        'desc' => trim($this->getVal($row, $idxNote))
                    ];
                }
            }
        } catch (Exception $e) {
        }

        usort($timeline, function ($a, $b) { return strcmp($b['date'], $a['date']); });
        $timeline = array_slice($timeline, 0, 150);

        return ['success' => true, 'data' => ['name' => $key, 'timeline' => $timeline]];
    }

    public function getCustomerWarRoom($customer)
    {
        $key = $this->displayCustomerName($customer);
        $now = new DateTime();

        // 1. 該客戶目前現正陳列的 SKU (含照片/上架日期)
        $entries = [];
        foreach ($this->getActiveDisplaysMap() as $sku => $list) {
            foreach ($list as $e) {
                if ($this->displayCustomerName($e['cust'] ?? '') === $key) {
                    $entries[$sku] = $e;
                }
            }
        }
        if (empty($entries)) {
            return ['success' => true, 'data' => ['displays' => [], 'totalAmt' => 0, 'totalPing' => 0, 'displayCount' => 0]];
        }

        // 2. 該客戶近一年針對這些 SKU 的銷售統計
        $metaMap = $this->getMetaMap();
        $stockMap = $this->getStockMap();
        $reservedMap = $this->getReservationMap();

        $salesBySku = [];
        $raw = $this->gs->readSheet(SALES_SHEET);
        if (count($raw) >= 2) {
            $h = $raw[0];
            $idxDate = $this->findHeader($h, ['日期','單據日期','銷貨日期']);
            $idxCust = $this->findHeader($h, ['客戶名稱','客戶']);
            $idxQty  = $this->findHeader($h, ['數量','片數']);
            $idxAmt  = $this->findHeader($h, ['金額','銷額','銷售金額','成交金額','小計','總計']);
            $idxCode = $this->findHeader($h, ['產品編號','編號','品碼','序號']);
            for ($i = 1; $i < count($raw); $i++) {
                $row = $raw[$i];
                $custRaw = trim($this->getVal($row, $idxCust));
                if ($custRaw === '' || $this->displayCustomerName($custRaw) !== $key) continue;
                $sku = $this->cleanSku($this->getVal($row, $idxCode));
                if (!isset($entries[$sku])) continue;
                $d = $this->parseDate($this->getVal($row, $idxDate));
                if (!$d || $now->diff($d)->days > 365) continue;
                $qty = $this->optFloat($this->getVal($row, $idxQty));
                $amt = $this->optFloat($this->getVal($row, $idxAmt));
                if (!isset($salesBySku[$sku])) $salesBySku[$sku] = ['amt' => 0, 'qty' => 0, 'count' => 0];
                $salesBySku[$sku]['amt'] += $amt;
                $salesBySku[$sku]['qty'] += $qty;
                $salesBySku[$sku]['count']++;
            }
        }

        // 3. 整理每個 SKU 並依照片分組（同一版面照片視為一張陳列牆）
        $groups = [];
        foreach ($entries as $sku => $e) {
            $meta = $metaMap[$sku] ?? ['series' => '一般', 'perPing' => 36];
            $sales = $salesBySku[$sku] ?? ['amt' => 0, 'qty' => 0, 'count' => 0];
            $perPing = $meta['perPing'] ?: 36;
            $d = $this->parseDate($e['date'] ?? '');
            $days = $d ? (int)$now->diff($d)->days : null;

            $item = [
                'sku' => $sku,
                'series' => $meta['series'] ?: '一般',
                'installDate' => $e['date'] ?? '',
                'days' => $days,
                'salesAmt' => round($sales['amt']),
                'salesPing' => round($sales['qty'] / $perPing, 1),
                'frequency' => $sales['count'],
                'stockPing' => round($stockMap[$sku] ?? 0, 1),
                'reservedQty' => $reservedMap[$sku]['reservedQty'] ?? 0
            ];

            $gk = ($e['photoUrl'] ?? '') !== '' ? $e['photoUrl'] : $sku;
            if (!isset($groups[$gk])) {
                $groups[$gk] = ['photoUrl' => $e['photoUrl'] ?? '', 'series' => $item['series'], 'days' => null, 'totalAmt' => 0, 'totalPing' => 0, 'items' => []];
            }
            $groups[$gk]['totalAmt'] += $item['salesAmt'];
            $groups[$gk]['totalPing'] += $item['salesPing'];
            $groups[$gk]['items'][] = $item;
            if ($days !== null && ($groups[$gk]['days'] === null || $days > $groups[$gk]['days'])) {
                $groups[$gk]['days'] = $days;
            }
        }

        $displays = [];
        $totalAmt = 0;
        $totalPing = 0;
        foreach ($groups as $g) {
            usort($g['items'], function ($a, $b) { return $b['salesAmt'] <=> $a['salesAmt']; });
            $g['topSku'] = $g['items'][0]['sku'];
            $g['topSharePct'] = $g['totalAmt'] > 0 ? round($g['items'][0]['salesAmt'] / $g['totalAmt'] * 100) : 0;
            $g['totalPing'] = round($g['totalPing'], 1);
            foreach ($g['items'] as &$it) { $it['salesPing'] = round($it['salesPing'], 1); }
            unset($it);
            $totalAmt += $g['totalAmt'];
            $totalPing += $g['totalPing'];
            $displays[] = $g;
        }
        usort($displays, function ($a, $b) { return $b['totalAmt'] <=> $a['totalAmt']; });

        return ['success' => true, 'data' => [
            'displays' => $displays,
            'totalAmt' => round($totalAmt),
            'totalPing' => round($totalPing, 1),
            'displayCount' => count($entries)
        ]];
    }

    public function getCustomerSalesBreakdown($customer)
    {
        $key = $this->displayCustomerName($customer);
        $now = new DateTime();
        $metaMap = $this->getMetaMap();

        // 時段邊界
        $monthStart  = new DateTime($now->format('Y-m-01'));
        $qStart      = new DateTime($now->format('Y-') . sprintf('%02d', (int)ceil($now->format('n') / 3) * 3 - 2) . '-01');
        $halfStart   = (clone $now)->modify('-6 months');
        $yearStart   = new DateTime(($now->format('Y') - 1) . '-01-01');
        $yearEnd     = new DateTime(($now->format('Y') - 1) . '-12-31');

        $skuMap = [];
        $raw = $this->gs->readSheet(SALES_SHEET);
        if (count($raw) >= 2) {
            $h = $raw[0];
            $idxDate  = $this->findHeader($h, ['日期','單據日期','銷貨日期']);
            $idxCust  = $this->findHeader($h, ['客戶名稱','客戶']);
            $idxQty   = $this->findHeader($h, ['數量','片數']);
            $idxAmt   = $this->findHeader($h, ['金額','銷額','銷售金額','成交金額','小計','總計']);
            $idxCode  = $this->findHeader($h, ['產品編號','編號','品碼','序號']);
            $idxNote  = $this->findHeader($h, ['備註','說明','案名']);
            for ($i = 1; $i < count($raw); $i++) {
                $row = $raw[$i];
                $custRaw = trim($this->getVal($row, $idxCust));
                if ($custRaw === '' || $this->displayCustomerName($custRaw) !== $key) continue;
                $note = $idxNote !== -1 ? trim($this->getVal($row, $idxNote)) : '';
                if (strpos($note, '樣品') !== false || strpos($note, '扣帶') !== false) continue;
                $d = $this->parseDate($this->getVal($row, $idxDate));
                if (!$d) continue;
                $sku = $this->cleanSku($this->getVal($row, $idxCode));
                if (!$sku) continue;
                $qty = $this->optFloat($this->getVal($row, $idxQty));
                $amt = $this->optFloat($this->getVal($row, $idxAmt));
                if ($qty == 0 && $amt == 0) continue;

                $ts = $d->format('Y-m-d');
                $inMonth  = $d >= $monthStart;
                $inQ      = $d >= $qStart;
                $inHalf   = $d >= $halfStart;
                $inPrevY  = $d >= $yearStart && $d <= $yearEnd;

                if (!isset($skuMap[$sku])) {
                    $meta = $metaMap[$sku] ?? ['series' => '一般', 'perPing' => 36];
                    $skuMap[$sku] = ['sku'=>$sku,'series'=>$meta['series']??'一般','perPing'=>$meta['perPing']??36,
                        'month'=>0,'quarter'=>0,'half'=>0,'prevYear'=>0,
                        'monthQ'=>0,'quarterQ'=>0,'halfQ'=>0,'prevYearQ'=>0];
                }
                if ($inMonth)  { $skuMap[$sku]['month']   += $amt; $skuMap[$sku]['monthQ']   += $qty; }
                if ($inQ)      { $skuMap[$sku]['quarter']  += $amt; $skuMap[$sku]['quarterQ']  += $qty; }
                if ($inHalf)   { $skuMap[$sku]['half']     += $amt; $skuMap[$sku]['halfQ']     += $qty; }
                if ($inPrevY)  { $skuMap[$sku]['prevYear'] += $amt; $skuMap[$sku]['prevYearQ'] += $qty; }
            }
        }

        $list = array_values($skuMap);
        usort($list, function($a, $b){ return ($b['half'] + $b['prevYear']) <=> ($a['half'] + $a['prevYear']); });
        $list = array_slice($list, 0, 20);
        foreach ($list as &$it) {
            $pp = $it['perPing'] ?: 36;
            $it['month']    = round($it['month']);   $it['monthPing']    = round($it['monthQ']/$pp,1);
            $it['quarter']  = round($it['quarter']); $it['quarterPing']  = round($it['quarterQ']/$pp,1);
            $it['half']     = round($it['half']);     $it['halfPing']     = round($it['halfQ']/$pp,1);
            $it['prevYear'] = round($it['prevYear']); $it['prevYearPing'] = round($it['prevYearQ']/$pp,1);
            unset($it['monthQ'],$it['quarterQ'],$it['halfQ'],$it['prevYearQ'],$it['perPing']);
        }
        unset($it);

        $totals = ['month'=>0,'quarter'=>0,'half'=>0,'prevYear'=>0];
        foreach ($list as $it) {
            foreach ($totals as $k => $_) $totals[$k] += $it[$k];
        }

        return ['success'=>true,'data'=>['skus'=>$list,'totals'=>$totals,'prevYearLabel'=>($now->format('Y')-1).'年全年']];
    }
}
