<?php
// ====== BonusTrait ======
trait BonusTrait
{

    public function getSleeperSalesByMonth($year, $month)
    {
        $configRes = $this->getSleeperConfig();
        if (!$configRes['success']) return $configRes;
        $sleeperMap = $configRes['data'];
        $seriesMap = $this->getSeriesMap();

        $raw = $this->gs->readSheet(SALES_SHEET);
        if (count($raw) < 2) return ['success' => false, 'msg' => '經銷銷售報表無資料'];

        $h = $raw[0];
        $idx = [
            'date'     => $this->findHeader($h, ['日期','單據日期','銷貨日期']),
            'sales'    => $this->findHeader($h, ['負責業務','業務','業務員','負責人']),
            'cust'     => $this->findHeader($h, ['客戶名稱','客戶']),
            'custCode' => $this->findHeader($h, ['客戶編號','客戶代碼','代碼']),
            'code'     => $this->findHeader($h, ['產品編號','編號','品碼','序號']),
            'amt'      => $this->findHeader($h, ['金額','銷額','銷售金額','成交金額','小計','總計']),
            'qty'      => $this->findHeader($h, ['數量','片數']),
            'note'     => $this->findHeader($h, ['備註','說明'])
        ];
        if ($idx['date'] === -1 || $idx['code'] === -1) {
            return ['success' => false, 'msg' => '無法定位銷貨報表欄位'];
        }

        $results = [];
        for ($i = 1; $i < count($raw); $i++) {
            $row = $raw[$i];
            $d = $this->parseDate($this->getVal($row, $idx['date']));
            if (!$d) continue;
            if ($d->format('Y') != $year || $d->format('n') != $month + 1) continue;

            $custName = trim($this->getVal($row, $idx['cust']));
            $custCode = $idx['custCode'] !== -1 ? trim($this->getVal($row, $idx['custCode'])) : '';
            $note = trim($this->getVal($row, $idx['note']));
            $amt = $this->optFloat($this->getVal($row, $idx['amt']));
            if ($this->isSampleRow($custCode, $custName, $note, $amt)) continue;

            $code = $this->cleanSku($this->getVal($row, $idx['code']));
            if (!$code || !isset($sleeperMap[$code])) continue;

            $sleeper = $sleeperMap[$code];
            $qty = $this->optFloat($this->getVal($row, $idx['qty']));
            if ($qty == 0 || $amt == 0) continue;


            $unitPrice = $qty > 0 ? $amt / $qty : 0;
            $totalCost = $sleeper['cost'] * $qty;
            $margin = $amt > 0 ? ($amt - $totalCost) / $amt : 0;
            $priceCostRatio = $sleeper['cost'] > 0 ? $unitPrice / $sleeper['cost'] : 1;
            $multiplier = $this->calcMultiplier($sleeper['grade'], $margin, $priceCostRatio, false);

            $results[] = [
                'salesName'  => trim($this->getVal($row, $idx['sales'])),
                'month'      => sprintf('%d/%02d', $year, $month + 1),
                'cust'       => $custName,
                'sku'        => $code,
                'series'     => isset($seriesMap[$code]) ? $seriesMap[$code] : '一般',
                'qty'        => $qty,
                'unitPrice'  => round($unitPrice, 2),
                'amt'        => round($amt),
                'cost'       => $sleeper['cost'],
                'totalCost'  => $totalCost,
                'margin'     => round($margin * 10000) / 100,
                'grade'      => $sleeper['grade'],
                'multiplier' => $multiplier,
                'bonus'      => round($amt * $multiplier),
                'isFullClearance' => false,
                'note'       => ''
            ];
        }
        return ['success' => true, 'data' => $results];
    }

    public function syncTrialSheet($year, $month)
    {
        $res = $this->getSleeperSalesByMonth($year, $month);
        if (!$res['success']) return $res;
        $newData = $res['data'];

        $this->ensureTrialSheet();

        $existing = $this->gs->readSheet(TRIAL_SHEET);
        $existingIds = [];
        if (count($existing) > 1) {
            $idCol = array_search('識別碼', $existing[0]);
            if ($idCol !== false) {
                for ($i = 1; $i < count($existing); $i++) {
                    $id = trim($this->getVal($existing[$i], $idCol));
                    if ($id) $existingIds[$id] = true;
                }
            }
        }

        $rowsToAdd = [];
        $seqCount = [];
        foreach ($newData as $d) {
            $key = $d['salesName'] . '|' . $d['month'] . '|' . $d['cust'] . '|' . $d['sku'];
            $seqCount[$key] = isset($seqCount[$key]) ? $seqCount[$key] + 1 : 1;
            $rowId = $d['salesName'] . '|' . $d['month'] . '|' . $d['cust'] . '|' . $d['sku'] . '|' . $seqCount[$key];
            if (isset($existingIds[$rowId])) continue;

            $rowsToAdd[] = [
                $rowId,
                $d['salesName'],
                "'" . $d['month'],
                $d['cust'],
                $d['sku'],
                $d['series'],
                $d['qty'],
                $d['unitPrice'],
                $d['amt'],
                $d['cost'],
                $d['margin'] . '%',
                $d['grade'],
                $d['multiplier'],
                $d['bonus'],
                '',
                '',
                '系統'
            ];
        }

        if (count($rowsToAdd) > 0) {
            $this->gs->appendRows(TRIAL_SHEET, $rowsToAdd);
        }

        return ['success' => true, 'added' => count($rowsToAdd), 'total' => count($newData)];
    }

    public function recalcTrialSheet()
    {
        $data = $this->gs->readSheet(TRIAL_SHEET);
        if (count($data) < 2) return ['success' => true, 'count' => 0];

        $h = $data[0];
        $cols = [
            'qty'       => array_search('片數', $h),
            'price'     => array_search('單價', $h),
            'amt'       => array_search('金額', $h),
            'cost'      => array_search('成本', $h),
            'margin'    => array_search('毛利率', $h),
            'multiplier'=> array_search('倍數', $h),
            'bonus'     => array_search('獎金', $h),
            'clearance' => array_search('全出清', $h),
            'grade'     => array_search('等級', $h)
        ];

        if ($cols['margin'] === false || $cols['multiplier'] === false || $cols['bonus'] === false) {
            return ['success' => false, 'msg' => '試算表欄位不完整'];
        }

        $marginVals = []; $multVals = []; $bonusVals = [];
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            $qty = $this->optFloat($this->getVal($row, $cols['qty']));
            $unitPrice = $this->optFloat($this->getVal($row, $cols['price']));
            $amt = $this->optFloat($this->getVal($row, $cols['amt']));
            if ($amt == 0) $amt = $qty * $unitPrice;
            $cost = $this->optFloat($this->getVal($row, $cols['cost']));
            $totalCost = $cost * $qty;
            $margin = $amt > 0 ? round(($amt - $totalCost) / $amt * 10000) / 100 : 0;
            $grade = strtoupper(trim($this->getVal($row, $cols['grade'])));
            $priceCostRatio = $cost > 0 ? $unitPrice / $cost : 1;
            $isFullClearance = (trim($this->getVal($row, $cols['clearance'])) === '是');
            $autoMult = $this->calcMultiplier($grade, $margin / 100, $priceCostRatio, $isFullClearance);

            $marginVals[] = [$margin . '%'];
            $multVals[] = [$autoMult];
            $bonusVals[] = [round($amt * $autoMult)];
        }

        $count = count($marginVals);
        if ($count > 0) {
            $this->gs->writeRows(TRIAL_SHEET, 2, $this->padToColumn($marginVals, $cols['margin'] + 1));
            $this->gs->writeRows(TRIAL_SHEET, 2, $this->padToColumn($multVals, $cols['multiplier'] + 1));
            $this->gs->writeRows(TRIAL_SHEET, 2, $this->padToColumn($bonusVals, $cols['bonus'] + 1));
        }

        return ['success' => true, 'count' => $count];
    }

    public function updateTrialRow($rowIdx, $qty, $unitPrice, $multiplier, $clearance, $note)
    {
        $amt = round($qty * $unitPrice);
        $data = $this->gs->readSheet(TRIAL_SHEET);
        if ($rowIdx < 2 || $rowIdx > count($data)) {
            return ['success' => false, 'msg' => '行號無效'];
        }

        $row = $data[$rowIdx - 1];
        $h = $data[0];
        $cols = [
            'qty'       => array_search('片數', $h),
            'price'     => array_search('單價', $h),
            'amt'       => array_search('金額', $h),
            'cost'      => array_search('成本', $h),
            'margin'    => array_search('毛利率', $h),
            'multiplier'=> array_search('倍數', $h),
            'bonus'     => array_search('獎金', $h),
            'clearance' => array_search('全出清', $h),
            'grade'     => array_search('等級', $h),
            'note'      => array_search('備註', $h)
        ];

        $cost = $this->optFloat($this->getVal($row, $cols['cost']));
        $totalCost = $cost * $qty;
        $grade = strtoupper(trim($this->getVal($row, $cols['grade'])));
        $priceCostRatio = $cost > 0 ? $unitPrice / $cost : 1;
        $isFullClearance = ($clearance === '是');
        $margin = $amt > 0 ? round(($amt - $totalCost) / $amt * 10000) / 100 : 0;
        $autoMult = $multiplier ?: $this->calcMultiplier($grade, $margin / 100, $priceCostRatio, $isFullClearance);
        $bonus = round($amt * $autoMult);

        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['qty'], $qty);
        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['price'], $unitPrice);
        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['amt'], $amt);
        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['margin'], $margin . '%');
        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['multiplier'], $autoMult);
        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['bonus'], $bonus);
        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['clearance'], $clearance ?: '');
        $this->gs->updateCell(TRIAL_SHEET, $rowIdx, $cols['note'], $note);

        return ['success' => true, 'rowIdx' => $rowIdx];
    }

    public function readTrialSheet($year, $month, $salesName = '')
    {
        $data = $this->gs->readSheet(TRIAL_SHEET);
        if (count($data) < 2) return ['success' => true, 'data' => []];

        $h = $data[0];
        $monthCol = array_search('月份', $h);
        if ($monthCol === false) return ['success' => false, 'msg' => '找不到月份欄'];

        $results = [];
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            $r = [];
            foreach ($h as $j => $col) {
                $r[$col] = trim($this->getVal($row, $j));
            }
            $r['_rowIdx'] = $i + 1;

            $monthStr = ltrim($r['月份'], "'");

            if ($year !== null && $month !== null) {
                $expected = sprintf('%d/%02d', $year, $month + 1);
                if ($monthStr !== $expected) continue;
            } elseif ($year !== null) {
                if (strpos($monthStr, (string)$year) !== 0) continue;
            }

            if ($salesName && $r['業務'] !== $salesName) continue;

            $amt = $this->optFloat($r['金額']);
            $cost = $this->optFloat($r['成本']);
            $qty = $this->optFloat($r['片數']);
            $totalCost = $cost * $qty;
            $margin = $amt > 0 ? round(($amt - $totalCost) / $amt * 10000) / 100 : 0;
            $mult = $this->optFloat($r['倍數']);
            if ($mult == 0) $mult = 1;

            $r['_amt'] = $amt;
            $r['_cost'] = $cost;
            $r['_qty'] = $qty;
            $r['_totalCost'] = $totalCost;
            $r['_margin'] = $margin;
            $r['_multiplier'] = $mult;
            $r['_bonus'] = round($amt * $mult);

            $results[] = $r;
        }

        return ['success' => true, 'data' => $results];
    }

    public function getBonusSummary($year, $month)
    {
        $res = $this->readTrialSheet($year, $month);
        if (!$res['success']) return $res;

        $rows = $res['data'];
        $groups = [];

        foreach ($rows as $r) {
            $name = isset(self::$salesMerge[$r['業務']]) ? self::$salesMerge[$r['業務']] : ($r['業務'] ?: '未指定');
            if (!isset($groups[$name])) {
                $groups[$name] = [
                    'salesName' => $name, 'count' => 0,
                    'totalAmt' => 0, 'totalCost' => 0,
                    'totalBonus' => 0, 'details' => []
                ];
            }
            $groups[$name]['count']++;
            $groups[$name]['totalAmt'] += $r['_amt'];
            $groups[$name]['totalCost'] += $r['_totalCost'];
            $groups[$name]['totalBonus'] += $r['_bonus'];
            $groups[$name]['details'][] = $r;
        }

        $people = [];
        foreach ($groups as $g) {
            $margin = $g['totalAmt'] > 0
                ? round(($g['totalAmt'] - $g['totalCost']) / $g['totalAmt'] * 10000) / 100
                : 0;
            $people[] = [
                'salesName' => $g['salesName'],
                'count'     => $g['count'],
                'totalAmt'  => round($g['totalAmt']),
                'totalCost' => round($g['totalCost']),
                'margin'    => $margin,
                'totalBonus'=> $g['totalBonus'],
                'details'   => $g['details']
            ];
        }
        usort($people, function($a, $b) { return $b['totalBonus'] - $a['totalBonus']; });

        $grand = [
            'count'      => array_sum(array_map(function($p) { return $p['count']; }, $people)),
            'totalAmt'   => array_sum(array_map(function($p) { return $p['totalAmt']; }, $people)),
            'totalCost'  => array_sum(array_map(function($p) { return $p['totalCost']; }, $people)),
            'totalBonus' => array_sum(array_map(function($p) { return $p['totalBonus']; }, $people))
        ];
        $grand['margin'] = $grand['totalAmt'] > 0
            ? round(($grand['totalAmt'] - $grand['totalCost']) / $grand['totalAmt'] * 10000) / 100
            : 0;

        return ['success' => true, 'data' => ['people' => $people, 'grand' => $grand]];
    }

    public function getYearSummary($year)
    {
        $data = $this->gs->readSheet(TRIAL_SHEET);
        $currentMonth = (int)date('n');
        $yearStr = (string)$year;

        $months = [];
        $grandTotal = 0;
        $peopleTotal = [];
        $custData = [];
        $prodTotals = [];
        $totalAmt = 0;

        if (count($data) > 1) {
            $h = $data[0];
            $monthIdx = array_search('月份', $h);
            $salesIdx = array_search('業務', $h);
            $custIdx  = array_search('客戶', $h);
            $skuIdx   = array_search('編號', $h);
            $seriesIdx= array_search('系列', $h);
            $amtIdx   = array_search('金額', $h);
            $multIdx  = array_search('倍數', $h);

            $monthMap = [];
            for ($i = 1; $i < count($data); $i++) {
                $r = $data[$i];
                $monthVal = ltrim(trim($this->getVal($r, $monthIdx)), "'");
                if (strpos($monthVal, $yearStr) !== 0) continue;

                $mNum = (int)substr($monthVal, 5, 2);
                $salesRaw = trim($this->getVal($r, $salesIdx));
                $sales = isset(self::$salesMerge[$salesRaw]) ? self::$salesMerge[$salesRaw] : $salesRaw;
                $cust  = trim($this->getVal($r, $custIdx));
                $sku   = trim($this->getVal($r, $skuIdx));
                $series= trim($this->getVal($r, $seriesIdx));
                $amt   = $this->optFloat($this->getVal($r, $amtIdx));
                $mult  = $this->optFloat($this->getVal($r, $multIdx)) ?: 1;
                $bonus = round($amt * $mult);

                if (!isset($monthMap[$mNum])) {
                    $monthMap[$mNum] = ['total' => 0, 'people' => []];
                }
                $monthMap[$mNum]['total'] += $bonus;
                if ($sales) {
                    if (!isset($monthMap[$mNum]['people'][$sales])) $monthMap[$mNum]['people'][$sales] = 0;
                    $monthMap[$mNum]['people'][$sales] += $bonus;
                    if (!isset($peopleTotal[$sales])) $peopleTotal[$sales] = 0;
                    $peopleTotal[$sales] += $bonus;
                }
                $grandTotal += $bonus;

                if ($cust && $amt) {
                    if (!isset($custData[$cust])) $custData[$cust] = ['total' => 0, 'series' => []];
                    $custData[$cust]['total'] += abs($amt);
                    if (!isset($custData[$cust]['series'][$series])) $custData[$cust]['series'][$series] = 0;
                    $custData[$cust]['series'][$series] += abs($amt);
                    $totalAmt += abs($amt);
                }
                if ($sku && $amt) {
                    $key = $sku . '|' . $series;
                    if (!isset($prodTotals[$key])) $prodTotals[$key] = ['sku' => $sku, 'series' => $series, 'amt' => 0];
                    $prodTotals[$key]['amt'] += abs($amt);
                }
            }

            for ($m = 1; $m <= $currentMonth; $m++) {
                $md = isset($monthMap[$m]) ? $monthMap[$m] : ['total' => 0, 'people' => []];
                $months[] = ['month' => $m, 'total' => $md['total'], 'people' => $md['people']];
            }
        } else {
            for ($m = 1; $m <= $currentMonth; $m++) {
                $months[] = ['month' => $m, 'total' => 0, 'people' => []];
            }
        }

        $maxMonthTotal = 0;
        foreach ($months as $m) {
            if ($m['total'] > $maxMonthTotal) $maxMonthTotal = $m['total'];
        }

        $top10cust = [];
        uasort($custData, function($a, $b) { return $b['total'] - $a['total']; });
        $ci = 0;
        foreach ($custData as $name => $data) {
            if ($ci++ >= 10) break;
            arsort($data['series']);
            $topSeries = key($data['series']);
            $top10cust[] = [
                'name'     => mb_substr($name, 0, 2, 'UTF-8'),
                'fullName' => $name,
                'series'   => $topSeries ?: '',
                'totalWan' => round($data['total'] / 10000, 1),
                'pct'      => $totalAmt > 0 ? round($data['total'] / $totalAmt * 100, 1) : 0
            ];
        }

        $top10prod = [];
        usort($prodTotals, function($a, $b) { return $b['amt'] - $a['amt']; });
        $top10prod = array_slice($prodTotals, 0, 10);

        $discontStats = ['discCount' => 0, 'missingSleeper' => []];
        $pData = $this->gs->readSheet(PRICE_SHEET);
        if (count($pData) > 1) {
            $pH = $pData[0];
            $pCode  = $this->findHeader($pH, ['編號','產品編號']);
            $pDisc  = $this->findHeader($pH, ['不續辦']);
            $pSlp   = $this->findHeader($pH, ['睡美人']);
            $pSer   = $this->findHeader($pH, ['中文系列','系列']);

            $discCount = 0;
            $sleeperFromPrice = [];
            for ($i = 1; $i < count($pData); $i++) {
                $row = $pData[$i];
                $sku = $this->cleanSku($this->getVal($row, $pCode));
                if (!$sku) continue;
                if ($pDisc !== -1 && trim($this->getVal($row, $pDisc)) !== '') $discCount++;
                if ($pSlp !== -1 && trim($this->getVal($row, $pSlp)) !== '') {
                    $sleeperFromPrice[$sku] = $pSer !== -1 ? trim($this->getVal($row, $pSer)) : '';
                }
            }
            $discontStats['discCount'] = $discCount;

            $sleeperConfig = $this->getSleeperConfig();
            $existing = $sleeperConfig['success'] ? $sleeperConfig['data'] : [];
            foreach ($sleeperFromPrice as $sku => $series) {
                if (!isset($existing[$sku])) {
                    $discontStats['missingSleeper'][] = ['sku' => $sku, 'series' => $series];
                }
            }
        }

        return [
            'success' => true,
            'data' => [
                'year' => $year,
                'currentMonth' => $currentMonth,
                'months' => $months,
                'grandTotal' => $grandTotal,
                'peopleTotal' => $peopleTotal,
                'target' => 3000000,
                'maxMonthTotal' => $maxMonthTotal,
                'topCustomers' => $top10cust,
                'topProducts' => $top10prod,
                'discontStats' => $discontStats
            ]
        ];
    }

    private function ensureTrialSheet()
    {
        $data = $this->gs->readSheet(TRIAL_SHEET);
        if (count($data) === 0) {
            $this->gs->writeRows(TRIAL_SHEET, 1, [['識別碼','業務','月份','客戶','編號','系列','片數','單價','金額','成本','毛利率','等級','倍數','獎金','全出清','備註','資料來源']]);
        }
    }
}
