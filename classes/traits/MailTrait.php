<?php
trait MailTrait
{
    public function getDailyReport($dateStr = null)
    {
        $today = $dateStr ? new DateTime($dateStr) : new DateTime('today');
        $todayStr = $today->format('Y/m/d');
        $monthStart = new DateTime($today->format('Y-m-01'));
        $yearStart  = new DateTime($today->format('Y-01-01'));
        $lyToday     = (clone $today)->modify('-1 year');
        $lyMonthStart = (clone $monthStart)->modify('-1 year');
        $lyYearStart  = (clone $yearStart)->modify('-1 year');

        $allCompanies = [
            ['key' => 'main',   'name' => '高雅瓷', 'ssId' => SS_ID_MAIN,   'color' => '#c29d66', 'sheet' => SALES_SHEET],
            ['key' => 'andyga', 'name' => '安帝嘉', 'ssId' => SS_ID_ANDYGA, 'color' => '#10b981', 'sheet' => SALES_SHEET],
            ['key' => 'xiyena', 'name' => '喜悅納', 'ssId' => SS_ID_XIYENA, 'color' => '#38bdf8', 'sheet' => '月報表'],
        ];

        $allData = [];
        $grandToday = 0;
        $grandMonth = 0;
        $grandYtd   = 0;

        foreach ($allCompanies as $co) {
            $d = $this->_collectSalesData($co, $today, $monthStart, $yearStart, $lyToday, $lyMonthStart, $lyYearStart);
            $grandToday += $d['todayTotal'];
            $grandMonth += $d['monthTotal'];
            $grandYtd   += $d['ytdTotal'];

            // Serialize DateTime objects for JSON
            $d['lastTxDate'] = $d['lastTxDate'] ? $d['lastTxDate']->format('Y-m-d') : null;
            foreach ($d['todayItems'] as &$item) {
                $item['d'] = $item['d'] ? $item['d']->format('Y-m-d') : null;
            }
            foreach ($d['displayItems'] as &$item) {
                $item['d'] = $item['d'] ? $item['d']->format('Y-m-d') : null;
            }
            unset($item);
            foreach ($d['custRows'] as &$cr) {
                foreach ($cr['items'] as &$ci) {
                    $ci['d'] = ($ci['d'] instanceof DateTime) ? $ci['d']->format('Y-m-d') : $ci['d'];
                }
                unset($ci);
            }
            unset($cr);
            // 月度分解補齊 1..當月，並轉為有序陣列
            $curMonth = (int) $today->format('n');
            $mb = [];
            for ($m = 1; $m <= $curMonth; $m++) {
                $mb[] = ['m' => $m, 'amt' => $d['monthlyBreakdown'][$m] ?? 0];
            }
            $d['monthlyBreakdown'] = $mb;
            // 移除過大或已由 custRows 承載的欄位
            unset($d['monthItems'], $d['custItems'], $d['custMonthMap'],
                  $d['custYtdMap'], $d['custLyYtdMap']);
            $allData[] = $d;
        }

        return [
            'success'     => true,
            'date'        => $todayStr,
            'grandToday'  => $grandToday,
            'grandMonth'  => $grandMonth,
            'grandYtd'    => $grandYtd,
            'companies'   => $allData,
        ];
    }

    public function sendDailyPerformanceReport($recipients, $fromEmail = '', $type = 'group')
    {
        $today = new DateTime('today');
        $todayStr = $today->format('Y/m/d');
        $monthStart = new DateTime($today->format('Y-m-01'));
        $yearStart = new DateTime($today->format('Y-01-01'));
        $lyToday = (new DateTime('today'))->modify('-1 year');
        $lyMonthStart = (new DateTime($today->format('Y-m-01')))->modify('-1 year');
        $lyYearStart = (new DateTime($today->format('Y-01-01')))->modify('-1 year');

        $allCompanies = [
            ['key' => 'main',   'name' => '高雅瓷', 'ssId' => SS_ID_MAIN,   'color' => '#c29d66', 'sheet' => SALES_SHEET],
            ['key' => 'andyga', 'name' => '安帝嘉', 'ssId' => SS_ID_ANDYGA, 'color' => '#10b981', 'sheet' => SALES_SHEET],
            ['key' => 'xiyena', 'name' => '喜悅納', 'ssId' => SS_ID_XIYENA, 'color' => '#38bdf8', 'sheet' => '月報表'],
        ];
        $companies = $type === 'main' ? array_slice($allCompanies, 0, 1) : $allCompanies;

        $allData = [];
        $grandToday = 0;
        $grandMonth = 0;
        $grandYtd = 0;

        foreach ($companies as $co) {
            $data = $this->_collectSalesData($co, $today, $monthStart, $yearStart, $lyToday, $lyMonthStart, $lyYearStart);
            $allData[] = $data;
            $grandToday += $data['todayTotal'];
            $grandMonth += $data['monthTotal'];
            $grandYtd += $data['ytdTotal'];
        }

        $html = $this->_buildEmailHtml($allData, $todayStr, $grandToday, $grandMonth, $grandYtd, $type);

        $title = $type === 'main' ? '高雅瓷每日戰報' : '集團每日戰報';
        $subject = $title . ' ' . $todayStr . ' | 銷貨' . $this->fmtW($grandToday) . ' 月累' . $this->fmtW($grandMonth);
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . ($fromEmail ?: DAILY_EMAIL_FROM),
        ];

        $headers[] = 'Content-Transfer-Encoding: quoted-printable';
        $ok = mail($recipients, $subject, quoted_printable_encode($html), implode("\r\n", $headers));

        if ($ok && $type !== 'main') {
            $telegramText = $this->_buildTelegramSummary($allData, $grandToday, $grandMonth, $grandYtd, $todayStr);
            $this->_sendTelegramMessage(TG_CHAT_PRIVATE, $telegramText);
        }

        return ['success' => $ok, 'todayTotal' => $grandToday, 'monthTotal' => $grandMonth, 'subject' => $subject];
    }

    private function _collectSalesData($co, $today, $monthStart, $yearStart, $lyToday, $lyMonthStart, $lyYearStart)
    {
        $result = [
            'co' => $co,
            'todayTotal' => 0, 'monthTotal' => 0, 'ytdTotal' => 0,
            'lyMonthTotal' => 0, 'lyYtdTotal' => 0,
            'todayItems' => [], 'monthItems' => [],
            'lastTxDate' => null,
            'salesTodayMap' => [], 'salesMonthMap' => [],
            'seriesRanking' => [], 'custRanking' => [], 'custItems' => [],
            'custTotalAmt' => 1, 'salesRanking' => [], 'salesTodayTotalAmt' => 1,
            'displayItems' => [], 'displayLabel' => '',
            'custMonthMap' => [], 'custRows' => [], 'custCount' => 0,
            'monthlyBreakdown' => [], 'custMonthRows' => [],
            'custYtdMap' => [], 'custLyYtdMap' => [],
        ];

        try {
            $gs = $this->getClient($co['ssId']);
            $salesRows = $gs->readSheet($co['sheet'] ?? SALES_SHEET);
        } catch (Exception $e) {
            $salesRows = [];
        }
        if (count($salesRows) <= 1) return $result;

        $h = $salesRows[0];
        $idx = [
            'date' => $this->findHeader($h, ['單據日期','銷貨日期','日期']),
            'cust' => $this->findHeader($h, ['客戶名稱','客戶']),
            'custCode' => $this->findHeader($h, ['客戶編號','客戶代碼','代碼']),
            'sales' => $this->findHeader($h, ['負責業務','業務','業務員','負責人']),
            'amt' => $this->findHeader($h, ['金額','銷額','銷售金額','成交金額','小計','總計']),
            'code' => $this->findHeader($h, ['產品編號','編號','品號','品碼','序號']),
            'qty' => $this->findHeader($h, ['數量','銷貨數量','片數']),
            'note' => $this->findHeader($h, ['產品備註','備註','說明']),
            'type' => $this->findHeader($h, ['類別','性質','單據類別']),
            'productName' => $this->findHeader($h, ['產品名稱','品名'])
        ];
        $profileMap = $this->getProductProfileMap($gs);

        for ($i = 1; $i < count($salesRows); $i++) {
            $row = $salesRows[$i];
            $d = $this->parseDate($this->getVal($row, $idx['date']));
            if (!$d) continue;
            $amt = $this->optFloat($this->getVal($row, $idx['amt']));
            $qty = $this->optFloat($this->getVal($row, $idx['qty']));
            if ($qty == 0 && $amt == 0) continue;
            $custName = trim($this->getVal($row, $idx['cust']));
            $custCode = trim($this->getVal($row, $idx['custCode']));
            $note = trim($this->getVal($row, $idx['note']));
            $productName = $idx['productName'] !== -1 ? trim($this->getVal($row, $idx['productName'])) : '';
            if ($this->isSampleRow($custCode, $custName, $productName . ' ' . $note, $amt)) continue;
            $code = $this->cleanSku($this->getVal($row, $idx['code']));
            if ($code === '') continue;
            $salesName = $this->normalizeSalesRep($this->getVal($row, $idx['sales']));
            $profile = $profileMap[$code] ?? null;
            $seriesCn = $profile ? (trim($profile['seriesCn'] ?: $profile['series']) ?: '') : '';
            $perPing = $profile ? ($profile['perPing'] ?: 36) : 36;

            // 退貨判定與扣抵（對齊 DataTrait::rebuildSalesCache 的邏輯）
            $typeText = $idx['type'] !== -1 ? trim($this->getVal($row, $idx['type'])) : '';
            $isReturn = (strpos($typeText, '退') !== false) || ($qty < 0);
            if ($isReturn) {
                $absQty = abs($qty);
                $qty = -$absQty;
                $amt = ($amt < 0 ? $amt : -abs($amt));
            } else {
                $qty = abs($qty);
            }
            $pings = $perPing ? $qty / $perPing : 0;

            $item = [
                'cust' => $custName, 'custShort' => $this->displayCustomerName($custName),
                'salesName' => $salesName, 'salesShort' => mb_substr($salesName, -2, 2, 'UTF-8'),
                'amt' => $amt,
                'code' => $code, 'seriesCn' => $seriesCn, 'qty' => $qty,
                'pings' => $pings, 'isReturn' => $isReturn, 'd' => $d
            ];
            if ($d >= $today) {
                $result['todayItems'][] = $item;
                $result['todayTotal'] += $amt;
                $result['salesTodayMap'][$salesName] = ($result['salesTodayMap'][$salesName] ?? 0) + $amt;
            }
            if ($d >= $monthStart) {
                $result['monthItems'][] = $item;
                $result['monthTotal'] += $amt;
                $result['salesMonthMap'][$salesName] = ($result['salesMonthMap'][$salesName] ?? 0) + $amt;
                $cs = $item['custShort'];
                $result['custMonthMap'][$cs] = ($result['custMonthMap'][$cs] ?? 0) + $amt;
            }
            if ($d >= $yearStart) {
                $result['ytdTotal'] += $amt;
                $mn = (int) $d->format('n');
                $result['monthlyBreakdown'][$mn] = ($result['monthlyBreakdown'][$mn] ?? 0) + $amt;
                $cy = $item['custShort'];
                $result['custYtdMap'][$cy] = ($result['custYtdMap'][$cy] ?? 0) + $amt;
            }
            if ($d >= $lyMonthStart && $d <= $lyToday) {
                $result['lyMonthTotal'] += $amt;
            }
            if ($d >= $lyYearStart && $d <= $lyToday) {
                $result['lyYtdTotal'] += $amt;
                $cl = $item['custShort'];
                $result['custLyYtdMap'][$cl] = ($result['custLyYtdMap'][$cl] ?? 0) + $amt;
            }
            if (!$result['lastTxDate'] || $d > $result['lastTxDate']) {
                $result['lastTxDate'] = $d;
            }
        }

        // Build series ranking from display period
        $result['displayItems'] = count($result['todayItems']) > 0 ? $result['todayItems'] : [];
        $result['displayLabel'] = '當日';
        if (count($result['todayItems']) === 0 && $result['lastTxDate']) {
            $result['displayItems'] = array_values(array_filter($result['monthItems'], function ($item) use ($result) {
                return $item['d'] == $result['lastTxDate'];
            }));
            $result['displayLabel'] = $result['lastTxDate']->format('m/d') . ' 交易';
        }
        $result['seriesRanking'] = $this->_buildSeriesRanking($result['displayItems']);

        // Customer ranking
        $custMap = [];
        $custItems = [];
        foreach ($result['displayItems'] as $item) {
            $sn = $this->displayCustomerName($item['cust']);
            $custMap[$sn] = ($custMap[$sn] ?? 0) + $item['amt'];
            if (!isset($custItems[$sn])) $custItems[$sn] = [];
            $custItems[$sn][] = $item;
        }
        arsort($custMap);
        $result['custRanking'] = array_slice($custMap, 0, 10);
        $result['custItems'] = $custItems;
        $result['custTotalAmt'] = max(1, array_sum($custMap));
        $result['custCount'] = count($custMap);

        // 客戶聚合列：當日金額 + 當月累積 + 主要業務 + 明細品項
        $custRows = [];
        foreach ($custMap as $sn => $amt) {
            $its = $custItems[$sn] ?? [];
            $bySales = [];
            foreach ($its as $it) {
                $rep = $it['salesShort'] ?: $it['salesName'];
                if ($rep === '') continue;
                $bySales[$rep] = ($bySales[$rep] ?? 0) + $it['amt'];
            }
            arsort($bySales);
            $custRows[] = [
                'name'     => $sn,
                'amt'      => $amt,
                'monthAmt' => $result['custMonthMap'][$sn] ?? 0,
                'sales'    => count($bySales) ? array_key_first($bySales) : '',
                'items'    => $its,
            ];
        }
        $result['custRows'] = $custRows;

        // 本月客戶排行 Top 15（含每日出貨明細與業績佔比）
        $custMonthDays = [];
        foreach ($result['monthItems'] as $it) {
            $cs = $it['custShort'];
            $dk = $it['d']->format('Y-m-d');
            if (!isset($custMonthDays[$cs][$dk])) {
                $custMonthDays[$cs][$dk] = ['amt' => 0, 'items' => []];
            }
            $custMonthDays[$cs][$dk]['amt'] += $it['amt'];
            $custMonthDays[$cs][$dk]['items'][] = [
                'seriesCn' => $it['seriesCn'], 'code' => $it['code'],
                'qty' => $it['qty'], 'amt' => $it['amt'],
                'isReturn' => $it['isReturn'], 'salesShort' => $it['salesShort'],
            ];
        }
        $mm = $result['custMonthMap'];
        arsort($mm);
        $mTot = $result['monthTotal'] != 0 ? abs($result['monthTotal']) : 1;
        $custMonthRows = [];
        foreach (array_slice($mm, 0, 15, true) as $sn => $amt) {
            $dayMap = $custMonthDays[$sn] ?? [];
            krsort($dayMap);
            $days = [];
            foreach ($dayMap as $dk => $dv) {
                $days[] = ['date' => $dk, 'amt' => $dv['amt'], 'items' => $dv['items']];
            }
            $custMonthRows[] = [
                'name'  => $sn,
                'amt'   => $amt,
                'pct'   => round($amt / $mTot * 100, 1),
                'ytd'   => $result['custYtdMap'][$sn] ?? 0,
                'lyYtd' => $result['custLyYtdMap'][$sn] ?? 0,
                'days'  => $days,
            ];
        }
        $result['custMonthRows'] = $custMonthRows;

        // Sales rep ranking
        $allSales = array_unique(array_merge(
            array_keys($result['salesTodayMap']),
            array_keys($result['salesMonthMap'])
        ));
        $sorted = [];
        foreach ($allSales as $name) {
            $sorted[] = ['name' => $name, 'today' => $result['salesTodayMap'][$name] ?? 0, 'month' => $result['salesMonthMap'][$name] ?? 0];
        }
        usort($sorted, function ($a, $b) { return $b['today'] <=> $a['today']; });
        $result['salesRanking'] = $sorted;
        $result['salesTodayTotalAmt'] = max(1, array_sum($result['salesTodayMap']));

        return $result;
    }

    private function _buildSeriesRanking($items)
    {
        $seriesMap = [];
        $totalPings = 0;
        foreach ($items as $item) {
            $s = $item['seriesCn'] ?: '其他';
            if (!isset($seriesMap[$s])) $seriesMap[$s] = ['pings' => 0, 'amount' => 0, 'skus' => []];
            $seriesMap[$s]['pings'] += $item['pings'];
            $seriesMap[$s]['amount'] += $item['amt'];
            if (!isset($seriesMap[$s]['skus'][$item['code']])) {
                $seriesMap[$s]['skus'][$item['code']] = ['pings' => 0, 'amount' => 0];
            }
            $seriesMap[$s]['skus'][$item['code']]['pings'] += $item['pings'];
            $seriesMap[$s]['skus'][$item['code']]['amount'] += $item['amt'];
            $totalPings += $item['pings'];
        }
        uasort($seriesMap, fn($a, $b) => $b['pings'] <=> $a['pings']);
        $ranking = [];
        foreach (array_slice($seriesMap, 0, 10) as $name => $s) {
            $skuList = $s['skus'];
            uasort($skuList, fn($a, $b) => $b['pings'] <=> $a['pings']);
            $topSkus = [];
            foreach (array_slice($skuList, 0, 2) as $sku => $sk) {
                $topSkus[] = ['sku' => $sku, 'pings' => round($sk['pings'], 1)];
            }
            $ranking[] = [
                'series' => $name,
                'pings' => round($s['pings'], 1),
                'amount' => round($s['amount']),
                'pct' => $totalPings > 0 ? round($s['pings'] / $totalPings * 100, 1) : 0,
                'skus' => $topSkus
            ];
        }
        return $ranking;
    }

    private function _fmtYoy($cur, $prev)
    {
        if ($prev <= 0) return '<span style="color:#94a3b8">—</span>';
        $pct = ($cur - $prev) / $prev * 100;
        if ($pct > 0) return '<span style="color:#059669;font-weight:700">▲ +' . number_format($pct, 1) . '%</span>';
        if ($pct < 0) return '<span style="color:#ef4444;font-weight:700">▼ ' . number_format($pct, 1) . '%</span>';
        return '<span style="color:#94a3b8">0.0%</span>';
    }

    private function _buildEmailHtml($allData, $todayStr, $grandToday, $grandMonth, $grandYtd, $type)
    {
        $title = $type === 'main' ? '高雅瓷每日戰報' : '集團業績彙報';
        $td = 'style="padding:0 8px;font-family:-apple-system,\'PingFang TC\',sans-serif"';

        $h  = '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1f5f9">';
        $h .= '<tr><td align="center" style="padding:20px 12px">';
        $h .= '<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;font-family:-apple-system,\'PingFang TC\',sans-serif;font-size:13px;color:#1e293b">';

        // ── Header ──
        $h .= '<tr><td style="background:#b8935a;border-radius:10px 10px 0 0;padding:18px 20px">';
        $h .= '<table width="100%" cellpadding="0" cellspacing="0" border="0">';
        $h .= '<tr>';
        $h .= '<td style="color:#fff;font-size:18px;font-weight:700">' . $title . '</td>';
        $h .= '<td align="right" style="color:rgba(255,255,255,.8);font-size:13px">' . $todayStr . '</td>';
        $h .= '</tr></table>';
        // Grand KPIs
        $h .= '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:14px">';
        $h .= '<tr>';
        foreach ([['今日', $grandToday], ['本月', $grandMonth], ['年累計', $grandYtd]] as [$lbl, $val]) {
            $h .= '<td align="center" style="background:rgba(255,255,255,.15);border-radius:6px;padding:10px 4px;width:33%">';
            $h .= '<div style="color:rgba(255,255,255,.75);font-size:11px;margin-bottom:4px">' . $lbl . '</div>';
            $h .= '<div style="color:#fff;font-size:20px;font-weight:800">' . $this->fmtW($val) . '</div>';
            $h .= '</td>';
            if ($lbl !== '年累計') $h .= '<td width="8"></td>';
        }
        $h .= '</tr></table>';
        $h .= '</td></tr>';

        // ── Compare table ──
        if ($type !== 'main') {
            $h .= '<tr><td style="background:#fff;padding:0">';
            $h .= '<table width="100%" cellpadding="0" cellspacing="0" border="0">';
            // Header row
            $h .= '<tr style="background:#1e293b">';
            $h .= '<td style="padding:9px 12px;color:#94a3b8;font-size:11px;font-weight:600;width:22%">項目</td>';
            foreach ($allData as $d) {
                $h .= '<td align="center" style="padding:9px 8px;color:' . $d['co']['color'] . ';font-size:13px;font-weight:700">' . $d['co']['name'] . '</td>';
            }
            $h .= '</tr>';
            $rows = [
                ['今日',     fn($d) => $this->fmtW($d['todayTotal']), false],
                ['本月',     fn($d) => $this->fmtW($d['monthTotal']), false],
                ['年累計',   fn($d) => $this->fmtW($d['ytdTotal']), false],
                ['月同比',   fn($d) => $d['lyMonthTotal'] > 0 ? $this->_fmtYoyPctPlain(($d['monthTotal']-$d['lyMonthTotal'])/$d['lyMonthTotal']*100) : '<span style="color:#94a3b8">—</span>', true],
                ['年同比',   fn($d) => $d['lyYtdTotal'] > 0 ? $this->_fmtYoyPctPlain(($d['ytdTotal']-$d['lyYtdTotal'])/$d['lyYtdTotal']*100) : '<span style="color:#94a3b8">—</span>', true],
                ['去年同月', fn($d) => '<span style="color:#94a3b8">' . $this->fmtW($d['lyMonthTotal']) . '</span>', false],
                ['去年同期', fn($d) => '<span style="color:#94a3b8">' . $this->fmtW($d['lyYtdTotal']) . '</span>', false],
            ];
            $odd = true;
            foreach ($rows as [$lbl, $fn, $center]) {
                $bg = $odd ? '#f8fafc' : '#fff';
                $h .= '<tr style="background:' . $bg . ';border-top:1px solid #e2e8f0">';
                $h .= '<td style="padding:7px 12px;color:#64748b;font-size:12px;font-weight:600">' . $lbl . '</td>';
                foreach ($allData as $d) {
                    $h .= '<td align="center" style="padding:7px 8px;font-size:13px">' . $fn($d) . '</td>';
                }
                $h .= '</tr>';
                $odd = !$odd;
            }
            $h .= '</table></td></tr>';
        }

        // ── Per-company sections ──
        foreach ($allData as $d) {
            $h .= $this->_buildCompanySection($d, $type === 'main');
        }

        // ── Footer ──
        $h .= '<tr><td style="background:#fff;border-radius:0 0 10px 10px;padding:14px 20px;text-align:center;border-top:1px solid #e2e8f0">';
        $h .= '<a href="https://bigt.cc/sleeping-beauty/daily.php" style="color:#b8935a;text-decoration:none;font-weight:600;font-size:13px">🔗 每日戰報</a>';
        $h .= ' &nbsp; <a href="https://bigt.cc/sleeping-beauty/group_meeting.php" style="color:#94a3b8;text-decoration:none;font-size:12px">集團月報</a>';
        $h .= '<div style="color:#94a3b8;font-size:11px;margin-top:6px">睡美人戰情室 自動發送</div>';
        $h .= '</td></tr>';

        $h .= '</table></td></tr></table>';
        return $h;
    }

    private function _fmtYoyPctPlain($pct)
    {
        if ($pct > 0) return '<span style="color:#059669;font-weight:700">▲ +' . number_format($pct, 1) . '%</span>';
        if ($pct < 0) return '<span style="color:#ef4444;font-weight:700">▼ ' . number_format($pct, 1) . '%</span>';
        return '<span style="color:#94a3b8">0.0%</span>';
    }

    private function _buildCompanySection($d, $isMain)
    {
        $co = $d['co'];
        $c  = $co['color'];
        $lastTx = $d['lastTxDate'] ? $d['lastTxDate']->format('m/d') : null;
        $label  = $d['displayLabel'] ?: ($lastTx ? $lastTx . ' 交易' : '');

        $h  = '<tr><td style="padding:0 0 2px 0">';
        // Company header
        $h .= '<table width="100%" cellpadding="0" cellspacing="0" border="0">';
        $h .= '<tr><td style="background:' . $c . ';padding:9px 14px;color:#fff;font-size:14px;font-weight:700">';
        $h .= $co['name'];
        if ($lastTx && !$isMain) $h .= '<span style="font-size:11px;font-weight:400;opacity:.8;margin-left:8px">最後交易 ' . $lastTx . '</span>';
        $h .= '</td></tr>';

        // KPI row
        $myoy   = !$isMain ? $this->_fmtYoyPctPlain($d['lyMonthTotal'] > 0 ? ($d['monthTotal']-$d['lyMonthTotal'])/$d['lyMonthTotal']*100 : 0) : '';
        $yoyYtd = !$isMain ? $this->_fmtYoyPctPlain($d['lyYtdTotal'] > 0 ? ($d['ytdTotal']-$d['lyYtdTotal'])/$d['lyYtdTotal']*100 : 0) : '';
        $h .= '<tr style="background:#fff"><td style="padding:10px 14px">';
        $h .= '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>';
        $kpis = [
            ['本月', $this->fmtW($d['monthTotal']), '#2563eb'],
            ['年累計', $this->fmtW($d['ytdTotal']), '#7c3aed'],
            ['月同比', $myoy, ''],
            ['年同比', $yoyYtd, ''],
        ];
        foreach ($kpis as [$lbl, $val, $col]) {
            if (!$isMain || $lbl === '本月' || $lbl === '年累計') {
                $h .= '<td style="padding:6px 10px 6px 0;vertical-align:top;white-space:nowrap">';
                $h .= '<div style="color:#94a3b8;font-size:10px;margin-bottom:3px">' . $lbl . '</div>';
                $valStyle = $col ? 'color:' . $col . ';font-size:15px;font-weight:700' : 'font-size:13px';
                $h .= '<div style="' . $valStyle . '">' . $val . '</div>';
                $h .= '</td>';
            }
        }
        $h .= '</tr></table>';
        $h .= '</td></tr>';

        // Series ranking
        if (count($d['seriesRanking']) > 0) {
            $h .= '<tr><td style="background:#fff;padding:0 14px 10px">';
            $h .= '<div style="font-size:12px;font-weight:700;color:' . $c . ';margin-bottom:6px">🏆 熱銷系列' . ($label ? '（' . $label . '）' : '') . '</div>';
            $h .= '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:12px">';
            $h .= '<tr style="background:#f8fafc">';
            foreach (['系列', '品號', '坪數', '佔比', '金額'] as $col) {
                $align = in_array($col, ['坪數','佔比','金額']) ? 'right' : 'left';
                $h .= '<th style="padding:5px 6px;color:#64748b;font-size:10px;font-weight:600;text-align:' . $align . '">' . $col . '</th>';
            }
            $h .= '</tr>';
            foreach ($d['seriesRanking'] as $sr) {
                $skus = implode(' ', array_map(fn($sk) => $sk['sku'], $sr['skus']));
                $h .= '<tr style="border-top:1px solid #f1f5f9">';
                $h .= '<td style="padding:5px 6px;font-weight:600">' . $sr['series'] . '</td>';
                $h .= '<td style="padding:5px 6px;color:#64748b;font-size:11px">' . $skus . '</td>';
                $h .= '<td style="padding:5px 6px;text-align:right">' . number_format($sr['pings'], 1) . '</td>';
                $h .= '<td style="padding:5px 6px;text-align:right;color:#64748b">' . $sr['pct'] . '%</td>';
                $h .= '<td style="padding:5px 6px;text-align:right;font-weight:600">' . $this->fmtW($sr['amount']) . '</td>';
                $h .= '</tr>';
            }
            $h .= '</table></td></tr>';
        }

        // Customer ranking
        if (count($d['custRanking']) > 0) {
            $h .= '<tr><td style="background:#fff;padding:0 14px 14px">';
            $h .= '<div style="font-size:12px;font-weight:700;color:' . $c . ';margin-bottom:6px">👥 客戶排行' . ($label ? '（' . $label . '）' : '') . '</div>';
            $h .= '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:12px">';
            $h .= '<tr style="background:#f8fafc">';
            foreach (['客戶', '金額', '佔比', '明細'] as $col) {
                $align = in_array($col, ['金額','佔比']) ? 'right' : 'left';
                $h .= '<th style="padding:5px 6px;color:#64748b;font-size:10px;font-weight:600;text-align:' . $align . '">' . $col . '</th>';
            }
            $h .= '</tr>';
            foreach ($d['custRanking'] as $name => $amt) {
                $items = $d['custItems'][$name] ?? [];
                $parts = array_slice(array_map(fn($i) => ($i['seriesCn'] ?: '') . ' ' . $i['code'] . ' ' . round($i['qty']) . '片', $items), 0, 2);
                if (count($items) > 2) $parts[] = '…還有' . (count($items)-2) . '筆';
                $h .= '<tr style="border-top:1px solid #f1f5f9">';
                $h .= '<td style="padding:5px 6px;font-weight:600">' . $name . '</td>';
                $h .= '<td style="padding:5px 6px;text-align:right">' . $this->fmtW($amt) . '</td>';
                $h .= '<td style="padding:5px 6px;text-align:right;color:#64748b">' . round($amt / $d['custTotalAmt'] * 100) . '%</td>';
                $h .= '<td style="padding:5px 6px;font-size:11px;color:#64748b">' . implode('<br>', $parts) . '</td>';
                $h .= '</tr>';
            }
            $h .= '</table></td></tr>';
        }

        $h .= '</table></td></tr>';
        return $h;
    }

    private function _sendTelegramMessage($chatId, $text)
    {
        $url = 'https://api.telegram.org/bot' . TG_BOT_TOKEN . '/sendMessage';
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpCode === 200;
    }

    private function _buildTelegramSummary($allData, $grandToday, $grandMonth, $grandYtd, $todayStr)
    {
        $lines = [];
        $lines[] = '<b>📊 集團業績彙報 ─ ' . $todayStr . '</b>';
        $lines[] = '';
        $lines[] = '今日 <b>' . $this->fmtW($grandToday) . '</b>　本月 <b>' . $this->fmtW($grandMonth) . '</b>　年累計 <b>' . $this->fmtW($grandYtd) . '</b>';
        $lines[] = '─────────────────';

        $emoji = ['高雅瓷' => '🟤', '安帝嘉' => '🟢', '喜悅納' => '🔵'];
        foreach ($allData as $d) {
            $co = $d['co'];
            $e = $emoji[$co['name']] ?? '●';
            $lines[] = '';
            $lines[] = $e . ' <b>' . $co['name'] . '</b>　本月 ' . $this->fmtW($d['monthTotal']) . '　年累計 ' . $this->fmtW($d['ytdTotal']);
            if ($d['lyMonthTotal'] > 0) {
                $mYoy = ($d['monthTotal'] - $d['lyMonthTotal']) / $d['lyMonthTotal'] * 100;
                $yYoy = $d['lyYtdTotal'] > 0 ? ($d['ytdTotal'] - $d['lyYtdTotal']) / $d['lyYtdTotal'] * 100 : null;
                $yoyStr = $yYoy !== null ? '　年同比 ' . $this->_tgYoy($yYoy) : '';
                $lines[] = '月同比 ' . $this->_tgYoy($mYoy) . $yoyStr;
            }
            $topSeries = array_slice($d['seriesRanking'], 0, 3);
            if (count($topSeries) > 0) {
                $parts = [];
                foreach ($topSeries as $sr) {
                    $parts[] = $sr['series'] . ' ' . $sr['pct'] . '%';
                }
                $lines[] = '🏆 ' . implode('　', $parts);
            }
        }

        $lines[] = '';
        $lines[] = '<a href="https://bigt.cc/sleeping-beauty/daily.php">🔗 每日戰報</a>　<a href="https://bigt.cc/sleeping-beauty/group_meeting.php">集團月報</a>';
        return implode("\n", $lines);
    }

    private function _tgYoy($pct)
    {
        if ($pct > 0) return '🟢 +' . number_format($pct, 1) . '%';
        if ($pct < 0) return '🔴 ' . number_format($pct, 1) . '%';
        return '⚪ 0.0%';
    }

    public function sendDailyTelegramReport($chatId = null)
    {
        $today = new DateTime('today');
        $todayStr = $today->format('Y/m/d');
        $monthStart = new DateTime($today->format('Y-m-01'));
        $yearStart = new DateTime($today->format('Y-01-01'));
        $lyToday = (new DateTime('today'))->modify('-1 year');
        $lyMonthStart = (new DateTime($today->format('Y-m-01')))->modify('-1 year');
        $lyYearStart = (new DateTime($today->format('Y-01-01')))->modify('-1 year');

        $allCompanies = [
            ['key' => 'main',   'name' => '高雅瓷', 'ssId' => SS_ID_MAIN,   'color' => '#c29d66', 'sheet' => SALES_SHEET],
            ['key' => 'andyga', 'name' => '安帝嘉', 'ssId' => SS_ID_ANDYGA, 'color' => '#10b981', 'sheet' => SALES_SHEET],
            ['key' => 'xiyena', 'name' => '喜悅納', 'ssId' => SS_ID_XIYENA, 'color' => '#38bdf8', 'sheet' => '月報表'],
        ];

        $allData = [];
        $grandToday = 0;
        $grandMonth = 0;
        $grandYtd = 0;
        $mostRecentTxDate = null;

        foreach ($allCompanies as $co) {
            $data = $this->_collectSalesData($co, $today, $monthStart, $yearStart, $lyToday, $lyMonthStart, $lyYearStart);
            $allData[] = $data;
            $grandToday += $data['todayTotal'];
            $grandMonth += $data['monthTotal'];
            $grandYtd += $data['ytdTotal'];
            if ($data['lastTxDate'] && (!$mostRecentTxDate || $data['lastTxDate'] > $mostRecentTxDate)) {
                $mostRecentTxDate = $data['lastTxDate'];
            }
        }

        if (!$mostRecentTxDate) {
            return ['success' => false, 'skipped' => true, 'reason' => 'no data'];
        }

        // 5天內沒有新資料就不推（跨連假最多4天，第5天還沒資料代表真的沒更新）
        $daysSince = (int)(new DateTime('today'))->diff($mostRecentTxDate)->days;
        if ($daysSince >= 5) {
            return ['success' => false, 'skipped' => true, 'reason' => 'stale', 'lastTxDate' => $mostRecentTxDate->format('Y-m-d'), 'daysSince' => $daysSince];
        }

        $text = $this->_buildTelegramSummary($allData, $grandToday, $grandMonth, $grandYtd, $todayStr);
        $target = $chatId ?: TG_CHAT_BULLETIN;
        $ok = $this->_sendTelegramMessage($target, $text);

        return ['success' => $ok, 'todayTotal' => $grandToday, 'monthTotal' => $grandMonth, 'lastTxDate' => $mostRecentTxDate->format('Y-m-d'), 'daysSince' => $daysSince];
    }
}
