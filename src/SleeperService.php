<?php
namespace App;

class SleeperService
{
    const TRIAL_HEADERS = [
        '識別碼','業務','月份','客戶','編號','系列',
        '片數','單價','金額','成本','毛利率','等級','倍數','獎金',
        '全出清','備註','資料來源'
    ];

    private GoogleSheetsClient $gs;

    public function __construct(GoogleSheetsClient $gs)
    {
        $this->gs = $gs;
    }

    // ─── 讀取睡美人設定（等級 + 成本 per SKU） ───
    public function getSleeperConfig(): array
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
            $sku = $this->cleanSku($row[$idx['sku']] ?? '');
            if (!$sku) continue;
            $map[$sku] = [
                'grade' => strtoupper(trim($row[$idx['grade']] ?? '')),
                'cost'  => $this->optFloat($idx['cost'] !== -1 ? ($row[$idx['cost']] ?? 0) : 0)
            ];
        }
        return ['success' => true, 'data' => $map];
    }

    // ─── 等級倍數計算 ───
    public function calcMultiplier(string $grade, float $margin, float $priceCostRatio, bool $isFullClearance): int|float
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

    // ─── 讀編號價目表（系列名稱） ───
    public function getSeriesMap(): array
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
            $sku = $this->cleanSku($data[$i][$idxSku] ?? '');
            if (!$sku) continue;
            $map[$sku] = trim($data[$i][$idxSeriesCn !== -1 ? $idxSeriesCn : $idxSeriesEn] ?? '') ?: '一般';
        }
        return $map;
    }

    // ─── 讀庫存表 ───
    public function getStockMap(): array
    {
        $data = $this->gs->readSheet(STOCK_SHEET);
        if (count($data) < 2) return [];

        $h = $data[0];
        $idxCode = $this->findHeader($h, ['編號','產品編號']);
        $idxStock = $this->findHeader($h, ['庫存坪數','坪數','庫存']);
        if ($idxCode === -1) return [];

        $map = [];
        for ($i = 1; $i < count($data); $i++) {
            $sku = $this->cleanSku($data[$i][$idxCode] ?? '');
            if (!$sku) continue;
            $ping = $idxStock !== -1 ? $this->optFloat($data[$i][$idxStock] ?? 0) : 0;
            $map[$sku] = ($map[$sku] ?? 0) + $ping;
        }
        return $map;
    }

    // ─── 讀編號價目表（含片/坪） ───
    public function getMetaMap(): array
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
            $sku = $this->cleanSku($data[$i][$idxCode] ?? '');
            if (!$sku) continue;
            $map[$sku] = [
                'series'  => $idxSeries !== -1 ? trim($data[$i][$idxSeries] ?? '') : '',
                'perPing' => $idxPerPing !== -1 ? ($this->optFloat($data[$i][$idxPerPing] ?? 0) ?: 36) : 36
            ];
        }
        return $map;
    }

    // ─── 從經銷銷售報表讀取指定月份的睡美人銷貨 ───
    public function getSleeperSalesByMonth(int $year, int $month): array
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
            $d = $this->parseDate($row[$idx['date']] ?? null);
            if (!$d) continue;
            if ($d->format('Y') != $year || $d->format('n') != $month + 1) continue;

            $custName = trim($row[$idx['cust']] ?? '');
            $note = trim($row[$idx['note']] ?? '');
            if (str_contains($custName, '樣品') || str_contains($note, '樣品') || str_contains($note, '扣帶')) continue;

            $code = $this->cleanSku($row[$idx['code']] ?? '');
            if (!$code || !isset($sleeperMap[$code])) continue;

            $sleeper = $sleeperMap[$code];
            $qty = $this->optFloat($row[$idx['qty']] ?? 0);
            $amt = $this->optFloat($row[$idx['amt']] ?? 0);
            if ($qty == 0 || $amt == 0) continue;

            $unitPrice = $qty > 0 ? $amt / $qty : 0;
            $totalCost = $sleeper['cost'] * $qty;
            $margin = $amt > 0 ? ($amt - $totalCost) / $amt : 0;
            $priceCostRatio = $sleeper['cost'] > 0 ? $unitPrice / $sleeper['cost'] : 1;
            $multiplier = $this->calcMultiplier($sleeper['grade'], $margin, $priceCostRatio, false);

            $results[] = [
                'salesName'  => trim($row[$idx['sales']] ?? ''),
                'month'      => sprintf('%d/%02d', $year, $month + 1),
                'cust'       => $custName,
                'sku'        => $code,
                'series'     => $seriesMap[$code] ?? '一般',
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

    // ─── 寫入「睡美人獎金試算」 ───
    public function syncTrialSheet(int $year, int $month): array
    {
        $res = $this->getSleeperSalesByMonth($year, $month);
        if (!$res['success']) return $res;
        $newData = $res['data'];

        // 確保試算表存在並有表頭
        $this->ensureTrialSheet();

        // 讀取現有資料
        $existing = $this->gs->readSheet(TRIAL_SHEET);
        $existingIds = [];
        if (count($existing) > 1) {
            $idCol = array_search('識別碼', $existing[0] ?? []);
            if ($idCol !== false) {
                for ($i = 1; $i < count($existing); $i++) {
                    $id = trim($existing[$i][$idCol] ?? '');
                    if ($id) $existingIds[$id] = true;
                }
            }
        }

        $rowsToAdd = [];
        $seqCount = [];
        foreach ($newData as $d) {
            $key = $d['salesName'] . '|' . $d['month'] . '|' . $d['cust'] . '|' . $d['sku'];
            $seqCount[$key] = ($seqCount[$key] ?? 0) + 1;
            $rowId = $d['salesName'] . '|' . $d['month'] . '|' . $d['cust'] . '|' . $d['sku'] . '|' . $seqCount[$key];
            if (isset($existingIds[$rowId])) continue;

            $rowsToAdd[] = [
                $rowId,
                $d['salesName'],
                sprintf("'%s", $d['month']),  // 強制純文字
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

    // ─── 重新計算試算表 ───
    public function recalcTrialSheet(): array
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

        // 檢查必要欄位
        if (in_array(false, $cols, true) || in_array(-1, $cols, true)) {
            return ['success' => false, 'msg' => '試算表欄位不完整'];
        }

        $marginVals = $multVals = $bonusVals = [];
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            $qty = $this->optFloat($row[$cols['qty']] ?? 0);
            $unitPrice = $this->optFloat($row[$cols['price']] ?? 0);
            $amt = $this->optFloat($row[$cols['amt']] ?? 0) ?: ($qty * $unitPrice);
            $cost = $this->optFloat($row[$cols['cost']] ?? 0);
            $totalCost = $cost * $qty;
            $margin = $amt > 0 ? round(($amt - $totalCost) / $amt * 10000) / 100 : 0;
            $grade = strtoupper(trim($row[$cols['grade']] ?? ''));
            $priceCostRatio = $cost > 0 ? $unitPrice / $cost : 1;
            $isFullClearance = (trim($row[$cols['clearance']] ?? '') === '是');
            $autoMult = $this->calcMultiplier($grade, $margin / 100, $priceCostRatio, $isFullClearance);

            $marginVals[] = [$margin . '%'];
            $multVals[] = [$autoMult];
            $bonusVals[] = [round($amt * $autoMult)];
        }

        $count = count($marginVals);
        if ($count > 0) {
            $startRow = 2; // 第 1 行是表頭
            $this->gs->writeRows(TRIAL_SHEET, $startRow, $this->padToColumn($marginVals, $cols['margin'] + 1));
            $this->gs->writeRows(TRIAL_SHEET, $startRow, $this->padToColumn($multVals, $cols['multiplier'] + 1));
            $this->gs->writeRows(TRIAL_SHEET, $startRow, $this->padToColumn($bonusVals, $cols['bonus'] + 1));
        }

        return ['success' => true, 'count' => $count];
    }

    // ─── 前端用：讀取獎金試算 ───
    public function readTrialSheet(?int $year, ?int $month, string $salesName = ''): array
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
                $r[$col] = trim($row[$j] ?? '');
            }
            $r['_rowIdx'] = $i + 1;

            // 月份處理：可能是 "'2026/06" 純文字
            $monthStr = $r['月份'];
            $monthStr = ltrim($monthStr, "'");

            if ($year !== null && $month !== null) {
                $expectedMonth = sprintf('%d/%02d', $year, $month + 1);
                if ($monthStr !== $expectedMonth) continue;
            } elseif ($year !== null) {
                if (!str_starts_with($monthStr, (string)$year)) continue;
            }

            if ($salesName && $r['業務'] !== $salesName) continue;

            $amt = $this->optFloat($r['金額']);
            $cost = $this->optFloat($r['成本']);
            $qty = $this->optFloat($r['片數']);
            $totalCost = $cost * $qty;
            $margin = $amt > 0 ? round(($amt - $totalCost) / $amt * 10000) / 100 : 0;
            $multiplier = $this->optFloat($r['倍數']) ?: 1;

            $r['_amt'] = $amt;
            $r['_cost'] = $cost;
            $r['_qty'] = $qty;
            $r['_totalCost'] = $totalCost;
            $r['_margin'] = $margin;
            $r['_multiplier'] = $multiplier;
            $r['_bonus'] = round($amt * $multiplier);

            $results[] = $r;
        }

        return ['success' => true, 'data' => $results];
    }

    // ─── 各業務獎金統計 ───
    public function getBonusSummary(?int $year, ?int $month): array
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
            $g = &$groups[$name];
            $g['count']++;
            $g['totalAmt'] += $r['_amt'];
            $g['totalCost'] += $r['_totalCost'];
            $g['totalBonus'] += $r['_bonus'];
            $g['details'][] = $r;
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
        usort($people, fn($a, $b) => $b['totalBonus'] - $a['totalBonus']);

        $grand = [
            'count'      => array_sum(array_column($people, 'count')),
            'totalAmt'   => array_sum(array_column($people, 'totalAmt')),
            'totalCost'  => array_sum(array_column($people, 'totalCost')),
            'totalBonus' => array_sum(array_column($people, 'totalBonus'))
        ];
        $grand['margin'] = $grand['totalAmt'] > 0
            ? round(($grand['totalAmt'] - $grand['totalCost']) / $grand['totalAmt'] * 10000) / 100
            : 0;

        return ['success' => true, 'data' => ['people' => $people, 'grand' => $grand]];
    }

    // ─── 產品總覽 ───
    public function getSleeperProductOverview(): array
    {
        $configRes = $this->getSleeperConfig();
        if (!$configRes['success']) return $configRes;
        $sleeperMap = $configRes['data'];

        $stockMap = $this->getStockMap();
        $metaMap = $this->getMetaMap();

        // 從銷售報表統計
        $raw = $this->gs->readSheet(SALES_SHEET);
        $h = $raw[0] ?? [];
        $idxCode = $this->findHeader($h, ['產品編號','編號']);
        $idxDate = $this->findHeader($h, ['日期','單據日期']);
        $idxQty  = $this->findHeader($h, ['數量','片數']);
        $idxCust = $this->findHeader($h, ['客戶名稱','客戶']);
        $idxNote = $this->findHeader($h, ['備註']);

        $salesStats = [];
        if ($idxCode !== -1 && $idxDate !== -1) {
            for ($i = 1; $i < count($raw); $i++) {
                $sku = $this->cleanSku($raw[$i][$idxCode] ?? '');
                if (!$sku || !isset($sleeperMap[$sku])) continue;

                $note = trim($raw[$i][$idxNote] ?? '');
                if (str_contains($note, '樣品') || str_contains($note, '扣帶')) continue;

                $qty = $this->optFloat($raw[$i][$idxQty] ?? 0);
                if ($qty <= 0) continue;

                $d = $this->parseDate($raw[$i][$idxDate] ?? null);
                if (!$d) continue;

                $meta = $metaMap[$sku] ?? ['series' => '', 'perPing' => 36];
                $perPing = $meta['perPing'] ?: 36;
                $pings = $qty / $perPing;
                $cust = trim($raw[$i][$idxCust] ?? '');

                if (!isset($salesStats[$sku])) {
                    $salesStats[$sku] = ['lastDate' => null, 'totalPings' => 0, 'buyerMap' => []];
                }
                $s = &$salesStats[$sku];
                if (!$s['lastDate'] || $d > $s['lastDate']) $s['lastDate'] = $d;
                $s['totalPings'] += $pings;
                $s['buyerMap'][$cust] = ($s['buyerMap'][$cust] ?? 0) + $pings;
            }
        }

        $now = new \DateTime();
        $products = [];
        foreach ($sleeperMap as $sku => $slp) {
            $meta = $metaMap[$sku] ?? ['series' => '', 'perPing' => 36];
            $stockPing = $stockMap[$sku] ?? 0;
            $costPerPing = $slp['cost'] * ($meta['perPing'] ?: 36);
            $inventoryCost = round($stockPing * $costPerPing);
            $stats = $salesStats[$sku] ?? null;

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

        usort($products, fn($a, $b) => $b['totalPings'] <=> $a['totalPings']);

        return ['success' => true, 'data' => $products];
    }

    // ─── 確保試算表存在 ───
    private function ensureTrialSheet(): void
    {
        $data = $this->gs->readSheet(TRIAL_SHEET);
        if (count($data) === 0) {
            $this->gs->writeRows(TRIAL_SHEET, 1, [self::TRIAL_HEADERS]);
        }
    }

    // ─── 工具方法 ───
    private function cleanSku($v): string
    {
        return strtoupper(preg_replace('/[\s\-]/', '', trim($v ?? '')));
    }

    private function optFloat($v): float
    {
        if ($v instanceof \DateTime) return 0;
        $s = preg_replace('/[^0-9.\-]/', '', (string)$v);
        $n = (float)$s;
        return is_finite($n) ? $n : 0;
    }

    private function parseDate($v): ?\DateTime
    {
        if (!$v) return null;
        if ($v instanceof \DateTime) return $v;
        if (is_numeric($v) && $v > 40000) {
            // Google Sheets serial date number
            $dt = new \DateTime();
            $dt->setDate(1899, 12, 30);
            $dt->modify('+' . (int)$v . ' days');
            return $dt;
        }
        $str = trim((string)$v);

        // YYYY/MM/DD or YY/MM/DD
        if (preg_match('/^0*(\d{2,4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/', $str, $m)) {
            $y = (int)$m[1];
            if ($y < 1000) $y += 1911;
            return new \DateTime(sprintf('%04d-%02d-%02d', $y, (int)$m[2], (int)$m[3]));
        }
        // YYYY/MM
        if (preg_match('/^0*(\d{2,4})[\/\-\.](\d{1,2})$/', $str, $m)) {
            $y = (int)$m[1];
            if ($y < 1000) $y += 1911;
            return new \DateTime(sprintf('%04d-%02d-01', $y, (int)$m[2]));
        }
        return null;
    }

    private function findHeader(array $headers, array $candidates): int
    {
        $clean = array_map(fn($h) => preg_replace('/[\s\x{FEFF}"()（）]/u', '', trim($h)), $headers);

        foreach ($candidates as $cand) {
            $target = preg_replace('/[\s"()（）]/u', '', $cand);
            $idx = array_search($target, $clean);
            if ($idx !== false) return $idx;
        }
        foreach ($candidates as $cand) {
            $target = preg_replace('/[\s"()（）]/u', '', $cand);
            foreach ($clean as $i => $c) {
                if (str_contains($c, $target)) return $i;
            }
        }
        return -1;
    }

    private function padToColumn(array $data, int $colIndex): array
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
