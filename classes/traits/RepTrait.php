<?php
// ====== RepTrait ======
trait RepTrait
{

    public function getRepAnalysis()
    {
        set_time_limit(120);
        $now = new DateTime();
        $thisYear = (int)$now->format('Y');
        $lastYear = $thisYear - 1;
        $areaMap = $this->getSalesRepAreaMap();
        $seriesMap = $this->getSeriesMap();

        // 從業務分區讀取客戶等級、目標、貢獻度（工作表有多個業務區塊，各自有表頭行）
        $custGradeMap = []; $custTargetMap = []; $custContribMap = []; $custRepMapFromSheet = []; $custAreaMap = [];
        $repAreaCount = [];
        try {
            $areaRows = $this->gs->readSheet('業務分區');
            $idxCN = $idxGr = $idxTg = $idxCo = $idxRpS = $idxArS = -1;
            foreach ($areaRows as $row) {
                $firstCell = trim($this->getVal($row, 0));
                if ($firstCell === '客戶' || $firstCell === '客戶名稱') {
                    $idxCN  = $this->findHeader($row, ['客戶','客戶名稱']);
                    $idxGr  = $this->findHeader($row, ['等級','級別']);
                    $idxTg  = $this->findHeader($row, ['目標']);
                    $idxCo  = $this->findHeader($row, ['貢獻度']);
                    $idxRpS = $this->findHeader($row, ['負責業務','業務','業務員']);
                    $idxArS = $this->findHeader($row, ['區域','地區']);
                    continue;
                }
                if ($idxCN === -1) continue;
                $cn = trim($this->getVal($row, $idxCN));
                if ($cn === '' || $cn === '客戶') continue;
                $key = $this->displayCustomerName($cn);
                if ($key === '未知客戶') continue;
                if ($idxGr !== -1)  { $g = trim($this->getVal($row, $idxGr));  if ($g !== '') $custGradeMap[$key] = $g; }
                if ($idxTg !== -1)  { $t = $this->optFloat($this->getVal($row, $idxTg)); if ($t > 0) $custTargetMap[$key] = $t * 10000; }
                if ($idxCo !== -1)  { $co = trim($this->getVal($row, $idxCo)); if ($co !== '') $custContribMap[$key] = $co; }
                $repName = '';
                if ($idxRpS !== -1) { $r = trim($this->getVal($row, $idxRpS)); if ($r !== '') { $repName = self::$salesMerge[$r] ?? $r; $custRepMapFromSheet[$key] = $repName; } }
                if ($idxArS !== -1) { $ar = trim($this->getVal($row, $idxArS)); if ($ar !== '') { $custAreaMap[$key] = $ar; if ($repName !== '') $repAreaCount[$repName][$ar] = ($repAreaCount[$repName][$ar] ?? 0) + 1; } }
            }
        } catch (Exception $e) {}
        // 每個業務取最多客戶的區域
        $repAreaMap = [];
        foreach ($repAreaCount as $rn => $arCounts) { arsort($arCounts); $repAreaMap[$rn] = array_key_first($arCounts); }

        $cacheRows = $this->gs->readSheet(CACHE_SHEET);
        $custMonthly = [];
        $custSeries = [];
        $custRepAmt = [];
        $custTotal = [];
        $custCount = [];

        if (count($cacheRows) > 1) {
            $h = $cacheRows[0];
            $idx = [
                'year' => $this->findHeader($h, ['年度']),
                'month' => $this->findHeader($h, ['月份']),
                'sku' => $this->findHeader($h, ['產品編號']),
                'cust' => $this->findHeader($h, ['客戶名稱']),
                'amt' => $this->findHeader($h, ['銷售金額']),
                'sales' => $this->findHeader($h, ['負責業務', '業務']),
                'prodName' => $this->findHeader($h, ['產品名稱'])
            ];
            for ($i = 1; $i < count($cacheRows); $i++) {
                $row = $cacheRows[$i];
                $sku = $this->cleanSku($this->getVal($row, $idx['sku']));
                if ($sku === '') continue;
                $cust = $this->displayCustomerName($this->getVal($row, $idx['cust']));
                $amt = $this->optFloat($this->getVal($row, $idx['amt']));
                $y = (int)$this->getVal($row, $idx['year']);
                $m = (int)$this->getVal($row, $idx['month']);
                $prodName = $idx['prodName'] !== -1 ? $this->getVal($row, $idx['prodName']) : '';
                if ($this->isSampleRow($sku, $cust, $prodName, $amt)) continue;
                if ($y !== $thisYear && $y !== $lastYear) continue;

                if (!isset($custMonthly[$cust])) $custMonthly[$cust] = [];
                if (!isset($custMonthly[$cust][$y])) $custMonthly[$cust][$y] = array_fill(1, 12, 0);
                $custMonthly[$cust][$y][$m] += $amt;

                $series = $seriesMap[$sku] ?? '其他';
                if (!isset($custSeries[$cust])) $custSeries[$cust] = [];
                if (!isset($custSeries[$cust][$y])) $custSeries[$cust][$y] = [];
                if (!isset($custSeries[$cust][$y][$series])) $custSeries[$cust][$y][$series] = 0;
                $custSeries[$cust][$y][$series] += $amt;

                $rep = trim((string)$this->getVal($row, $idx['sales']));
                if ($rep !== '') {
                    $rep = self::$salesMerge[$rep] ?? $rep;
                    if (!isset($custRepAmt[$cust])) $custRepAmt[$cust] = [];
                    if (!isset($custRepAmt[$cust][$rep])) $custRepAmt[$cust][$rep] = 0;
                    $custRepAmt[$cust][$rep] += $amt;
                }
                if (!isset($custTotal[$cust])) $custTotal[$cust] = 0;
                $custTotal[$cust] += $amt;
                if (!isset($custCount[$cust])) $custCount[$cust] = 0;
                $custCount[$cust]++;
            }
        }

        $custMainRep = [];
        foreach ($custRepAmt as $cust => $reps) {
            arsort($reps);
            $custMainRep[$cust] = key($reps);
        }

        $contractData = [];
        try {
            $cr = $this->gs->readSheet('合約');
            if (count($cr) > 2) {
                $h = $cr[0];
                $cHealth = $this->findHeader($h, ['健康度']);
                $cCust = $this->findHeader($h, ['客戶']);
                $cContractAmt = $this->findHeader($h, ['合約內容']);
                $cQty = $this->findHeader($h, ['張數']);
                $cExpiry = $this->findHeader($h, ['最後一張票期']);
                $cSales = $this->findHeader($h, ['業務']);
                $cPrepay = $this->findHeader($h, ['預收金額']);
                // Dynamic balance column
                $balanceColIdx = -1;
                $balanceColDate = '';
                foreach ($h as $i => $col) {
                    $c = trim((string)$col);
                    if (mb_strpos($c, '餘額') !== false) {
                        $balanceColIdx = $i;
                        if (preg_match('/(\d{2,4})[\/年](\d{1,2})月/u', $c, $m)) {
                            $balanceColDate = $m[0];
                        }
                    }
                }
                for ($i = 2; $i < count($cr); $i++) {
                    $row = $cr[$i];
                    $cust = $this->displayCustomerName(trim($this->getVal($row, $cCust)));
                    if ($cust === '') continue;
                    $skipLabels = ['正常','觀察','警示','掛點','待續約','嚴重','沖完','未續約','合計','小計'];
                    if (in_array($cust, $skipLabels)) continue;
                    $healthRaw = $cHealth !== -1 ? trim($this->getVal($row, $cHealth)) : '';
                    if (mb_strpos($healthRaw, '沖完') !== false || mb_strpos($healthRaw, '未續約') !== false) continue;
                    $contractAmt = $cContractAmt !== -1 ? $this->optFloat($this->getVal($row, $cContractAmt)) : 0;
                    $qty = $cQty !== -1 ? max((int)$this->getVal($row, $cQty), 1) : 1;
                    $totalContract = $contractAmt * $qty;
                    $bal = $balanceColIdx >= 0 ? $this->optFloat($this->getVal($row, $balanceColIdx)) : null;
                    $balRatio = $totalContract > 0 ? ($bal / $totalContract) : 1;
                    $due = $this->parseDate($this->getVal($row, $cExpiry));
                    $dueDays = $due ? (int)(new DateTime())->diff($due)->format('%r%a') : null;
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
                            $dueText = round($dueDays / 30, 1) . ' 個月後到期';
                        } else {
                            $dueText = '今天到期';
                        }
                    }
                    // Health classification (same as ReportTrait)
                    $bucket = '';
                    if ($bal !== null && $bal <= 0) {
                        $bucket = '待續約';
                    } elseif ($dueDays === null || $dueDays >= 0) {
                        $bucket = $balRatio !== null && $balRatio > 0.9 ? '觀察' : '正常';
                    } else {
                        $od = abs($dueDays);
                        if ($od >= 730) $bucket = '掛點';
                        elseif ($od >= 365) $bucket = '警示';
                        elseif ($od >= 180) $bucket = ($balRatio !== null && $balRatio > 0.5) ? '警示' : '觀察';
                        else $bucket = ($balRatio !== null && $balRatio > 0.4) ? '觀察' : '正常';
                    }
                    $contractData[$cust] = [
                        'health' => $bucket ?: ($healthRaw ?: null),
                        'healthRaw' => $healthRaw ?: null,
                        'totalContract' => round($totalContract),
                        'balance' => $bal !== null ? round($bal) : null,
                        'balRatio' => $balRatio !== null ? round($balRatio * 100, 1) : null,
                        'expiry' => $cExpiry !== -1 ? trim($this->getVal($row, $cExpiry)) : null,
                        'dueText' => $dueText,
                        'dueLevel' => $dueLevel,
                        'balDate' => $balanceColDate,
                    ];
                }
            }
        } catch (Exception $e) {}

        $visitData = [];
        $noteData = [];
        try {
            $gsLy = new GoogleSheetsClient(SS_ID_LAYOUT);
            $vr = $gsLy->readSheet('智能_工作日誌');
            if (count($vr) > 1) {
                $h = $vr[0];
                $vDate = $this->findHeader($h, ['日期']);
                $vRep = $this->findHeader($h, ['業務姓名', '業務']);
                $vCust = $this->findHeader($h, ['客戶名稱', '客戶']);
                $vSum = $this->findHeader($h, ['任務摘要', '摘要']);
                for ($i = 1; $i < count($vr); $i++) {
                    $row = $vr[$i];
                    $rep = trim($this->getVal($row, $vRep));
                    if ($rep === '') continue;
                    $rep = self::$salesMerge[$rep] ?? $rep;
                    $cust = $this->displayCustomerName(trim($this->getVal($row, $vCust)));
                    if ($cust === '') continue;
                    $k = $rep . '|' . $cust;
                    if (!isset($visitData[$k])) $visitData[$k] = [];
                    $visitData[$k][] = [
                        'date' => trim($this->getVal($row, $vDate)),
                        'summary' => $vSum !== -1 ? trim($this->getVal($row, $vSum)) : ''
                    ];
                }
            }
            $nr = $gsLy->readSheet('智能_客戶備註歷史');
            if (count($nr) > 1) {
                $h = $nr[0];
                $nDate = $this->findHeader($h, ['日期']);
                $nRep = $this->findHeader($h, ['業務姓名', '業務']);
                $nCust = $this->findHeader($h, ['客戶名稱', '客戶']);
                $nNote = $this->findHeader($h, ['備註']);
                for ($i = 1; $i < count($nr); $i++) {
                    $row = $nr[$i];
                    $rep = trim($this->getVal($row, $nRep));
                    if ($rep === '') continue;
                    $rep = self::$salesMerge[$rep] ?? $rep;
                    $cust = $this->displayCustomerName(trim($this->getVal($row, $nCust)));
                    if ($cust === '') continue;
                    $k = $rep . '|' . $cust;
                    if (!isset($noteData[$k])) $noteData[$k] = [];
                    $noteData[$k][] = [
                        'date' => trim($this->getVal($row, $nDate)),
                        'note' => $nNote !== -1 ? trim($this->getVal($row, $nNote)) : ''
                    ];
                }
            }
        } catch (Exception $e) {}

        $reps = [];
        foreach ($custMonthly as $cust => $yrData) {
            $rep = $custMainRep[$cust] ?? '未分配';
            if (!isset($reps[$rep])) {
                $reps[$rep] = [
                    'name' => $rep, 'area' => $repAreaMap[$rep] ?? $areaMap[$rep] ?? '',
                    'customerCount' => 0, 'totalAmount' => 0,
                    'totalThisYear' => 0, 'totalLastYear' => 0, 'avgYoy' => null
                ];
            }
            $reps[$rep]['customerCount']++;
            $tyAmtRaw = array_sum($custMonthly[$cust][$thisYear] ?? []);
            $lyFullAmt = array_sum($custMonthly[$cust][$lastYear] ?? []);
            $currentMonth = (int)$now->format('n');
            $lySamePeriodAmt = 0;
            for ($m = 1; $m <= $currentMonth; $m++) {
                $lySamePeriodAmt += $custMonthly[$cust][$lastYear][$m] ?? 0;
            }
            $weight = 1.0;
            if (mb_strpos($cust, '漢樺') !== false) $weight = 1/3;
            $tyAmt = $tyAmtRaw * $weight;
            $lyFullAmt *= $weight;
            $lySamePeriodAmt *= $weight;
            $reps[$rep]['totalAmount'] += $custTotal[$cust] ?? 0;
            $reps[$rep]['totalThisYear'] += $tyAmt;
            $reps[$rep]['totalLastYear'] += $lySamePeriodAmt;

            $yoyPct = $lySamePeriodAmt > 0 ? round(($tyAmt - $lySamePeriodAmt) / $lySamePeriodAmt * 100, 1) : ($tyAmt > 0 ? 100 : 0);
            $achieveRate = $lyFullAmt > 0 ? round($tyAmt / $lyFullAmt * 100, 1) : 0;
            if ($yoyPct >= 10) $health = 'growth';
            elseif ($yoyPct <= -30) $health = 'decline';
            elseif ($tyAmt == 0 && $lyFullAmt == 0 && $lySamePeriodAmt == 0) $health = 'no_sales';
            else $health = 'normal';

            $monthlyTrend = [];
            for ($m = 1; $m <= 12; $m++) {
                $ty = ($custMonthly[$cust][$thisYear][$m] ?? 0) * $weight;
                $ly = ($custMonthly[$cust][$lastYear][$m] ?? 0) * $weight;
                $yoy = $ly > 0 ? round(($ty - $ly) / $ly * 100, 1) : ($ty > 0 ? 100 : 0);
                $pm = ($m > 1) ? ($custMonthly[$cust][$thisYear][$m - 1] ?? 0) : 0;
                $mom = $pm > 0 ? round(($ty - $pm) / $pm * 100, 1) : ($ty > 0 ? 100 : ($m === 1 ? null : 0));
                $monthlyTrend[] = [
                    'month' => $m, 'thisYear' => round($ty), 'lastYear' => round($ly),
                    'yoy' => $yoy, 'mom' => $mom
                ];
            }

            $seriesTrend = ['thisYear' => [], 'lastYear' => []];
            foreach ([$thisYear, $lastYear] as $yr) {
                $label = $yr === $thisYear ? 'thisYear' : 'lastYear';
                $sd = $custSeries[$cust][$yr] ?? [];
                arsort($sd);
                $totalYr = array_sum($sd);
                $top5 = array_slice($sd, 0, 5);
                foreach ($top5 as $series => $amt) {
                    $seriesTrend[$label][] = [
                        'series' => $series, 'amount' => round($amt),
                        'pct' => $totalYr > 0 ? round($amt / $totalYr * 100, 1) : 0
                    ];
                }
            }

            $lod = null;
            for ($m = 12; $m >= 1; $m--) {
                if (($custMonthly[$cust][$thisYear][$m] ?? 0) > 0) {
                    $lod = "{$thisYear}-" . str_pad($m, 2, '0', STR_PAD_LEFT);
                    break;
                }
            }
            if ($lod === null) {
                for ($m = 12; $m >= 1; $m--) {
                    if (($custMonthly[$cust][$lastYear][$m] ?? 0) > 0) {
                        $lod = "{$lastYear}-" . str_pad($m, 2, '0', STR_PAD_LEFT);
                        break;
                    }
                }
            }

            $contract = $contractData[$cust] ?? ['health' => null, 'expiry' => null, 'balance' => null];

            $k = $rep . '|' . $cust;
            $visits = $visitData[$k] ?? [];
            usort($visits, function ($a, $b) { return strcmp($b['date'] ?? '', $a['date'] ?? ''); });
            $notes = $noteData[$k] ?? [];
            usort($notes, function ($a, $b) { return strcmp($b['date'] ?? '', $a['date'] ?? ''); });

            $custTarget = $custTargetMap[$cust] ?? null;
            $targetAchieve = ($custTarget > 0) ? round($tyAmt / $custTarget * 100, 1) : null;
            $reps[$rep]['customers'][] = [
                'name' => $cust, 'health' => $health,
                'totalAmount' => round($custTotal[$cust] ?? 0),
                'thisYearAmount' => round($tyAmt), 'lastYearAmount' => round($lyFullAmt),
                'lastYearSamePeriod' => round($lySamePeriodAmt),
                'achieveRate' => $achieveRate,
                'grade' => $custGradeMap[$cust] ?? null,
                'target' => $custTarget,
                'targetAchieve' => $targetAchieve,
                'contrib' => $custContribMap[$cust] ?? null,
                'yoyPct' => $yoyPct, 'saleCount' => $custCount[$cust] ?? 0,
                'lastOrderDate' => $lod, 'contract' => $contract,
                'monthlyTrend' => $monthlyTrend, 'seriesTrend' => $seriesTrend,
                'visits' => array_slice($visits, 0, 10),
                'notes' => array_slice($notes, 0, 10),
            ];
        }

        foreach ($reps as &$rep) {
            usort($rep['customers'], function ($a, $b) { return $b['totalAmount'] - $a['totalAmount']; });
            $yoyVals = array_filter(array_column($rep['customers'], 'yoyPct'), function ($v) { return $v !== 0; });
            $rep['avgYoy'] = count($yoyVals) > 0 ? round(array_sum($yoyVals) / count($yoyVals), 1) : 0;
            // 整體達成率
            $totalTarget = array_sum(array_filter(array_column($rep['customers'], 'target')));
            $totalActual = array_sum(array_column($rep['customers'], 'thisYearAmount'));
            $rep['totalTarget'] = $totalTarget > 0 ? round($totalTarget) : null;
            $rep['overallAchieve'] = ($totalTarget > 0) ? round($totalActual / $totalTarget * 100, 1) : null;
            // 等級分布
            $gradeDist = [];
            foreach ($rep['customers'] as $cc) {
                $g = $cc['grade'] ?? '無';
                $gradeDist[$g] = ($gradeDist[$g] ?? 0) + 1;
            }
            $rep['gradeDist'] = $gradeDist;
        }
        unset($rep);

        usort($reps, function ($a, $b) { return $b['totalAmount'] - $a['totalAmount']; });

        return ['success' => true, 'data' => ['reps' => array_values($reps)]];
    }
}
