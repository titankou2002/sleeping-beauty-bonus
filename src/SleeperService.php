<?php
namespace App;

class SleeperService
{
    const TRIAL_HEADERS = [
        '識別碼','業務','月份','客戶','編號','系列',
        '片數','單價','金額','成本','毛利率','等級','倍數','獎金',
        '全出清','備註','資料來源'
    ];

    private $gs;

    public function __construct($gs)
    {
        $this->gs = $gs;
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
        return ['success' => true, 'data' => $map];
    }

    public function calcMultiplier($grade, $margin, $priceCostRatio, $isFullClearance)
    {
        $g = strtoupper(trim($grade));

        if ($g === 'XXX') {
            if ($priceCostRatio >= 0.7) return 3;
            if ($priceCostRatio >= 0.5) return 2;
            return 1;
        }
        if ($g === 'S') {
            if ($margin > 0.15) return 3;
            if ($priceCostRatio >= 0.85) return 2;
            if ($isFullClearance) return 2;
            return 1;
        }
        if ($g === 'A') {
            if ($margin > 0.15) return 2;
            if ($isFullClearance) return 2.5;
            if ($margin >= -0.05) return 1.5;
            return 1;
        }
        if ($g === 'B') {
            if ($margin > 0.30) return 2;
            return 1;
        }
        return 1;
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
            'date'  => $this->findHeader($h, ['日期','單據日期','銷貨日期']),
            'sales' => $this->findHeader($h, ['負責業務','業務','業務員','負責人']),
            'cust'  => $this->findHeader($h, ['客戶名稱','客戶']),
            'code'  => $this->findHeader($h, ['產品編號','編號','品碼','序號']),
            'amt'   => $this->findHeader($h, ['金額','銷額','銷售金額','成交金額','小計','總計']),
            'qty'   => $this->findHeader($h, ['數量','片數']),
            'note'  => $this->findHeader($h, ['備註','說明'])
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
            $note = trim($this->getVal($row, $idx['note']));
            if (strpos($custName, '樣品') !== false || strpos($note, '樣品') !== false || strpos($note, '扣帶') !== false) continue;

            $code = $this->cleanSku($this->getVal($row, $idx['code']));
            if (!$code || !isset($sleeperMap[$code])) continue;

            $sleeper = $sleeperMap[$code];
            $qty = $this->optFloat($this->getVal($row, $idx['qty']));
            $amt = $this->optFloat($this->getVal($row, $idx['amt']));
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
            $name = $r['業務'] ?: '未指定';
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

    public function getSleeperProductOverview()
    {
        $configRes = $this->getSleeperConfig();
        if (!$configRes['success']) return $configRes;
        $sleeperMap = $configRes['data'];

        $stockMap = $this->getStockMap();
        $metaMap = $this->getMetaMap();

        $raw = $this->gs->readSheet(SALES_SHEET);
        $h = isset($raw[0]) ? $raw[0] : [];
        $idxCode = $this->findHeader($h, ['產品編號','編號']);
        $idxDate = $this->findHeader($h, ['日期','單據日期']);
        $idxQty  = $this->findHeader($h, ['數量','片數']);
        $idxCust = $this->findHeader($h, ['客戶名稱','客戶']);
        $idxNote = $this->findHeader($h, ['備註']);

        $salesStats = [];
        if ($idxCode !== -1 && $idxDate !== -1) {
            for ($i = 1; $i < count($raw); $i++) {
                $sku = $this->cleanSku($this->getVal($raw[$i], $idxCode));
                if (!$sku || !isset($sleeperMap[$sku])) continue;

                $note = trim($this->getVal($raw[$i], $idxNote));
                if (strpos($note, '樣品') !== false || strpos($note, '扣帶') !== false) continue;

                $qty = $this->optFloat($this->getVal($raw[$i], $idxQty));
                if ($qty <= 0) continue;

                $d = $this->parseDate($this->getVal($raw[$i], $idxDate));
                if (!$d) continue;

                $meta = isset($metaMap[$sku]) ? $metaMap[$sku] : ['series' => '', 'perPing' => 36];
                $perPing = $meta['perPing'] ?: 36;
                $pings = $qty / $perPing;
                $cust = trim($this->getVal($raw[$i], $idxCust));

                if (!isset($salesStats[$sku])) {
                    $salesStats[$sku] = ['lastDate' => null, 'totalPings' => 0, 'buyerMap' => []];
                }
                $s = &$salesStats[$sku];
                if (!$s['lastDate'] || $d > $s['lastDate']) $s['lastDate'] = $d;
                $s['totalPings'] += $pings;
                if (!isset($s['buyerMap'][$cust])) $s['buyerMap'][$cust] = 0;
                $s['buyerMap'][$cust] += $pings;
            }
        }

        $now = new \DateTime();
        $products = [];
        foreach ($sleeperMap as $sku => $slp) {
            $meta = isset($metaMap[$sku]) ? $metaMap[$sku] : ['series' => '', 'perPing' => 36];
            $stockPing = isset($stockMap[$sku]) ? $stockMap[$sku] : 0;
            $costPerPing = $slp['cost'] * ($meta['perPing'] ?: 36);
            $inventoryCost = round($stockPing * $costPerPing);
            $stats = isset($salesStats[$sku]) ? $salesStats[$sku] : null;

            $daysSinceLastSale = null;
            $lastSaleStr = '從未銷售';
            if ($stats && $stats['lastDate']) {
                $daysSinceLastSale = (int)$now->diff($stats['lastDate'])->days;
                $lastSaleStr = $stats['lastDate']->format('Y/m');
            }

            $totalPings = $stats ? round($stats['totalPings'] * 10) / 10 : 0;
            $buyers = [];
            if ($stats) {
                $sorted = $stats['buyerMap'];
                arsort($sorted);
                $i = 0;
                foreach ($sorted as $name => $pings) {
                    if ($i++ >= 5) break;
                    $buyers[] = ['name' => $name, 'pings' => round($pings * 10) / 10];
                }
            }

            $products[] = [
                'sku' => $sku,
                'series' => $meta['series'],
                'grade' => $slp['grade'],
                'costPerPiece' => $slp['cost'],
                'perPing' => $meta['perPing'],
                'stockPing' => round($stockPing * 10) / 10,
                'inventoryCost' => $inventoryCost,
                'totalPings' => $totalPings,
                'daysSinceLastSale' => $daysSinceLastSale,
                'lastSaleStr' => $lastSaleStr,
                'buyers' => $buyers
            ];
        }

        usort($products, function($a, $b) { return $b['totalPings'] <=> $a['totalPings']; });

        return ['success' => true, 'data' => $products];
    }

    private function ensureTrialSheet()
    {
        $data = $this->gs->readSheet(TRIAL_SHEET);
        if (count($data) === 0) {
            $this->gs->writeRows(TRIAL_SHEET, 1, [self::TRIAL_HEADERS]);
        }
    }

    private function cleanSku($v)
    {
        return strtoupper(preg_replace('/[\s\-]/', '', trim($v)));
    }

    private function optFloat($v)
    {
        if ($v instanceof \DateTime) return 0;
        $s = preg_replace('/[^0-9.\-]/', '', (string)$v);
        $n = (float)$s;
        return is_finite($n) ? $n : 0;
    }

    private function parseDate($v)
    {
        if (!$v) return null;
        if ($v instanceof \DateTime) return $v;
        if (is_numeric($v) && $v > 40000) {
            $dt = new \DateTime();
            $dt->setDate(1899, 12, 30);
            $dt->modify('+' . (int)$v . ' days');
            return $dt;
        }
        $str = trim((string)$v);

        if (preg_match('/^0*(\d{2,4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/', $str, $m)) {
            $y = (int)$m[1];
            if ($y < 1000) $y += 1911;
            return new \DateTime(sprintf('%04d-%02d-%02d', $y, (int)$m[2], (int)$m[3]));
        }
        if (preg_match('/^0*(\d{2,4})[\/\-\.](\d{1,2})$/', $str, $m)) {
            $y = (int)$m[1];
            if ($y < 1000) $y += 1911;
            return new \DateTime(sprintf('%04d-%02d-01', $y, (int)$m[2]));
        }
        return null;
    }

    private function findHeader($headers, $candidates)
    {
        if (!$headers || count($headers) === 0) return -1;
        $clean = [];
        foreach ($headers as $h) {
            $clean[] = preg_replace('/[\s\x{FEFF}"()（）]/u', '', trim($h));
        }
        foreach ($candidates as $cand) {
            $target = preg_replace('/[\s"()（）]/u', '', $cand);
            $idx = array_search($target, $clean);
            if ($idx !== false) return $idx;
        }
        foreach ($candidates as $cand) {
            $target = preg_replace('/[\s"()（）]/u', '', $cand);
            foreach ($clean as $i => $c) {
                if (strpos($c, $target) !== false) return $i;
            }
        }
        return -1;
    }

    private function getVal($row, $idx)
    {
        return isset($row[$idx]) ? $row[$idx] : '';
    }

    private function padToColumn($data, $colIndex)
    {
        $result = [];
        foreach ($data as $row) {
            $padded = array_fill(0, $colIndex, '');
            $padded[$colIndex - 1] = $row[0];
            $result[] = $padded;
        }
        return $result;
    }
}
