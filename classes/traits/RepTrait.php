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
                $cExpiry = $this->findHeader($h, ['最後一張票期']);
                $cSales = $this->findHeader($h, ['業務']);
                $cBal = $cSales > 0 ? $cSales - 1 : -1;
                for ($i = 2; $i < count($cr); $i++) {
                    $row = $cr[$i];
                    $cust = $this->displayCustomerName(trim($this->getVal($row, $cCust)));
                    if ($cust === '') continue;
                    $contractData[$cust] = [
                        'health' => $cHealth !== -1 ? trim($this->getVal($row, $cHealth)) : null,
                        'expiry' => $cExpiry !== -1 ? trim($this->getVal($row, $cExpiry)) : null,
                        'balance' => $cBal >= 0 ? $this->optFloat($this->getVal($row, $cBal)) : null,
                    ];
                }
            }
        } catch (Exception $e) {}

        $visitData = [];
        $noteData = [];
        try {
            $gsLy = new GoogleSheetsClient(SS_ID_LAYOUT);
            $vr = $gsLy->readSheet('工作日誌');
            if (count($vr) <= 1) {
                $vr = $gsLy->readSheet('智能_工作日誌');
            }
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
                    'name' => $rep, 'area' => $areaMap[$rep] ?? '',
                    'customerCount' => 0, 'totalAmount' => 0,
                    'totalThisYear' => 0, 'totalLastYear' => 0, 'avgYoy' => null
                ];
            }
            $reps[$rep]['customerCount']++;
            $tyAmt = array_sum($custMonthly[$cust][$thisYear] ?? []);
            $lyAmt = array_sum($custMonthly[$cust][$lastYear] ?? []);
            $reps[$rep]['totalAmount'] += $custTotal[$cust] ?? 0;
            $reps[$rep]['totalThisYear'] += $tyAmt;
            $reps[$rep]['totalLastYear'] += $lyAmt;

            $yoyPct = $lyAmt > 0 ? round(($tyAmt - $lyAmt) / $lyAmt * 100, 1) : ($tyAmt > 0 ? 100 : 0);
            if ($yoyPct >= 10) $health = 'growth';
            elseif ($yoyPct <= -30) $health = 'decline';
            elseif ($tyAmt == 0 && $lyAmt == 0) $health = 'no_sales';
            else $health = 'normal';

            $monthlyTrend = [];
            for ($m = 1; $m <= 12; $m++) {
                $ty = $custMonthly[$cust][$thisYear][$m] ?? 0;
                $ly = $custMonthly[$cust][$lastYear][$m] ?? 0;
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

            $reps[$rep]['customers'][] = [
                'name' => $cust, 'health' => $health,
                'totalAmount' => round($custTotal[$cust] ?? 0),
                'thisYearAmount' => round($tyAmt), 'lastYearAmount' => round($lyAmt),
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
        }
        unset($rep);

        usort($reps, function ($a, $b) { return $b['totalAmount'] - $a['totalAmount']; });

        return ['success' => true, 'data' => ['reps' => array_values($reps)]];
    }
}
