<?php
trait MailTrait
{
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
            ['key' => 'main', 'name' => '高雅瓷', 'ssId' => SS_ID_MAIN, 'color' => '#c29d66'],
            ['key' => 'andyga', 'name' => '安帝嘉', 'ssId' => SS_ID_ANDYGA, 'color' => '#10b981'],
            ['key' => 'xiyena', 'name' => '喜悅納', 'ssId' => SS_ID_XIYENA, 'color' => '#38bdf8'],
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
            'To: ' . $recipients
        ];

        $ok = mail($recipients, $subject, chunk_split($html, 990, "\n"), implode("\r\n", $headers));

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
        ];

        try {
            $gs = $this->getClient($co['ssId']);
            $salesRows = $gs->readSheet(SALES_SHEET);
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
            'qty' => $this->findHeader($h, ['數量','片數']),
            'note' => $this->findHeader($h, ['備註','說明'])
        ];
        $profileMap = $this->getProductProfileMap();

        for ($i = 1; $i < count($salesRows); $i++) {
            $row = $salesRows[$i];
            $d = $this->parseDate($this->getVal($row, $idx['date']));
            if (!$d) continue;
            $amt = $this->optFloat($this->getVal($row, $idx['amt']));
            if ($amt <= 0) continue;
            $custName = trim($this->getVal($row, $idx['cust']));
            $custCode = trim($this->getVal($row, $idx['custCode']));
            $note = trim($this->getVal($row, $idx['note']));
            if ($this->isSampleRow($custCode, $custName, $note, $amt)) continue;
            $code = $this->cleanSku($this->getVal($row, $idx['code']));
            if ($code === '') continue;
            $salesName = $this->normalizeSalesRep($this->getVal($row, $idx['sales']));
            $profile = $profileMap[$code] ?? null;
            $seriesCn = $profile ? (trim($profile['seriesCn'] ?: $profile['series']) ?: '') : '';
            $perPing = $profile ? ($profile['perPing'] ?: 36) : 36;
            $qty = $this->optFloat($this->getVal($row, $idx['qty']));
            $pings = $qty > 0 ? $qty / $perPing : 0;
            $item = [
                'cust' => $custName, 'salesName' => $salesName, 'amt' => $amt,
                'code' => $code, 'seriesCn' => $seriesCn, 'qty' => $qty,
                'pings' => $pings, 'd' => $d
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
            }
            if ($d >= $yearStart) {
                $result['ytdTotal'] += $amt;
            }
            if ($d >= $lyMonthStart && $d <= $lyToday) {
                $result['lyMonthTotal'] += $amt;
            }
            if ($d >= $lyYearStart && $d <= $lyToday) {
                $result['lyYtdTotal'] += $amt;
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

        // Sales rep ranking
        $allSales = array_unique(array_merge(
            array_keys($result['salesTodayMap']),
            array_keys($result['salesMonthMap'])
        ));
        $sorted = [];
        foreach ($allSales as $name) {
            $sorted[] = ['name' => $name, 'today' => $result['salesTodayMap'][$name] ?? 0, 'month' => $result['salesMonthMap'][$name] ?? 0];
        }
        usort($sorted, function ($a, $b) { return $b['today'] - $a['today']; });
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
        uasort($seriesMap, fn($a, $b) => $b['pings'] - $a['pings']);
        $ranking = [];
        foreach (array_slice($seriesMap, 0, 10) as $name => $s) {
            $skuList = $s['skus'];
            uasort($skuList, fn($a, $b) => $b['pings'] - $a['pings']);
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
        $h = '<div style="font-family:-apple-system,\'PingFang TC\',sans-serif;max-width:780px;margin:0 auto;color:#1e293b;background:#f8fafc;padding:20px">';
        $title = $type === 'main' ? '高雅瓷每日戰報' : '集團業績彙報';
        $h .= '<div style="background:linear-gradient(135deg,#c29d66,#a07a4a);color:#fff;padding:16px 20px;border-radius:10px;margin-bottom:16px">';
        $h .= '<h1 style="margin:0;font-size:20px">' . $title . ' — ' . $todayStr . '</h1>';
        $h .= '<div style="display:flex;gap:24px;margin-top:10px;font-size:13px">';
        $h .= '<div><span style="opacity:.7">集團今日</span><br><span style="font-size:22px;font-weight:800">' . $this->fmtW($grandToday) . '</span></div>';
        $h .= '<div><span style="opacity:.7">集團本月</span><br><span style="font-size:22px;font-weight:800">' . $this->fmtW($grandMonth) . '</span></div>';
        $h .= '<div><span style="opacity:.7">集團YTD</span><br><span style="font-size:22px;font-weight:800">' . $this->fmtW($grandYtd) . '</span></div>';
        $h .= '</div></div>';

        if ($type !== 'main') {
            $h .= $this->_buildCompareSection($allData);
        }

        foreach ($allData as $d) {
            $h .= $this->_buildCompanySection($d, $type === 'main');
        }

        $h .= '<div style="margin-top:20px;padding:12px 16px;background:#fff;border-radius:8px;border:1px solid #e2e8f0;text-align:center;font-size:12px;color:#64748b">';
        $h .= '<a href="https://bigt.cc/sleeping-beauty/group_meeting.php" style="color:#c29d66;text-decoration:none;font-weight:600">🔗 查看完整集團月報（含互動式圖表）</a>';
        $h .= '<br><span style="margin-top:6px;display:inline-block">睡美人戰情室 AI 自動發送</span></div>';
        $h .= '</div>';
        return $h;
    }

    private function _buildCompareSection($allData)
    {
        $h = '<table style="width:100%;border-collapse:collapse;margin-bottom:16px;background:#fff;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0">';
        $h .= '<tr><th style="padding:10px 12px;background:#1e293b;color:#fff;font-size:12px;text-align:left">公司</th>';
        foreach ($allData as $d) {
            $h .= '<th style="padding:10px 12px;background:#1e293b;color:' . $d['co']['color'] . ';font-size:13px;text-align:center">' . $d['co']['name'] . '</th>';
        }
        $h .= '</tr>';

        $rows = [
            ['label' => '今日', 'fn' => fn($d) => $d['todayTotal'], 'fmt' => 'w'],
            ['label' => '本月', 'fn' => fn($d) => $d['monthTotal'], 'fmt' => 'w'],
            ['label' => 'YTD', 'fn' => fn($d) => $d['ytdTotal'], 'fmt' => 'w'],
            ['label' => '月YOY', 'fn' => fn($d) => $d['lyMonthTotal'] > 0 ? ($d['monthTotal'] - $d['lyMonthTotal']) / $d['lyMonthTotal'] * 100 : null, 'fmt' => 'yoy'],
            ['label' => 'YTD YOY', 'fn' => fn($d) => $d['lyYtdTotal'] > 0 ? ($d['ytdTotal'] - $d['lyYtdTotal']) / $d['lyYtdTotal'] * 100 : null, 'fmt' => 'yoy'],
        ];
        foreach ($rows as $rr) {
            $h .= '<tr style="border-top:1px solid #e2e8f0">';
            $h .= '<td style="padding:8px 12px;font-size:12px;color:#64748b;font-weight:600">' . $rr['label'] . '</td>';
            foreach ($allData as $d) {
                $val = $rr['fn']($d);
                $cell = '';
                if ($rr['fmt'] === 'yoy') {
                    $cell = $val !== null ? $this->_fmtYoyPctPlain($val) : '<span style="color:#94a3b8">—</span>';
                } else {
                    $cell = '<span style="font-size:14px;font-weight:700">' . $this->fmtW($val) . '</span>';
                }
                $h .= '<td style="padding:8px 12px;text-align:center">' . $cell . '</td>';
            }
            $h .= '</tr>';
        }

        // 月份 YOY 的數值列（去年同期）
        $h .= '<tr style="border-top:1px solid #e2e8f0">';
        $h .= '<td style="padding:8px 12px;font-size:11px;color:#94a3b8;font-weight:400">去同月</td>';
        foreach ($allData as $d) {
            $h .= '<td style="padding:8px 12px;text-align:center;font-size:12px;color:#64748b">' . $this->fmtW($d['lyMonthTotal']) . '</td>';
        }
        $h .= '</tr>';
        $h .= '<tr style="border-top:1px solid #e2e8f0">';
        $h .= '<td style="padding:8px 12px;font-size:11px;color:#94a3b8;font-weight:400">去年YTD</td>';
        foreach ($allData as $d) {
            $h .= '<td style="padding:8px 12px;text-align:center;font-size:12px;color:#64748b">' . $this->fmtW($d['lyYtdTotal']) . '</td>';
        }
        $h .= '</tr>';
        $h .= '</table>';
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
        $c = $co['color'];
        $h = '<div style="background:#fff;border-radius:8px;border:1px solid #e2e8f0;margin-bottom:16px;overflow:hidden">';
        $h .= '<div style="background:' . $c . ';color:#fff;padding:10px 14px;font-size:15px;font-weight:700">' . $co['name'] . '</div>';
        $h .= '<div style="padding:12px 14px">';

        // KPI summary
        $todayColor = $d['todayTotal'] > 0 ? '#059669' : '#94a3b8';
        $lastTxNote = $d['todayTotal'] === 0 && $d['lastTxDate'] ? '（最後交易日：' . $d['lastTxDate']->format('Y/m/d') . '）' : '';
        $h .= '<div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:12px;font-size:13px">';
        $h .= '<div><span style="color:' . $todayColor . ';font-weight:700;font-size:16px">今日 ' . $this->fmtW($d['todayTotal']) . '</span>' . ($lastTxNote ? '<span style="color:#94a3b8;font-size:11px;margin-left:4px">' . $lastTxNote . '</span>' : '') . '</div>';
        $h .= '<div><span style="color:#2563eb;font-weight:700;font-size:16px">本月 ' . $this->fmtW($d['monthTotal']) . '</span></div>';
        $h .= '<div><span style="color:#7c3aed;font-weight:700;font-size:16px">YTD ' . $this->fmtW($d['ytdTotal']) . '</span></div>';
        if (!$isMain) {
            $myoy = $this->_fmtYoy($d['monthTotal'], $d['lyMonthTotal']);
            $yoyYtd = $this->_fmtYoy($d['ytdTotal'], $d['lyYtdTotal']);
            $h .= '<div style="font-size:12px;color:#64748b">月YOY ' . $myoy . ' &nbsp; YTD YOY ' . $yoyYtd . '</div>';
        }
        $h .= '</div>';

        // Series ranking
        if (count($d['seriesRanking']) > 0) {
            $h .= '<div style="font-size:13px;font-weight:600;margin:10px 0 6px;color:' . $c . '">🏆 熱銷系列 Top 10（' . $d['displayLabel'] . '）</div>';
            $h .= '<table style="width:100%;border-collapse:collapse;font-size:12px">';
            $h .= '<tr><th style="padding:3px 6px;text-align:left;color:#64748b;font-weight:500">#</th><th style="padding:3px 6px;text-align:left;color:#64748b;font-weight:500">系列</th><th style="padding:3px 6px;text-align:right;color:#64748b;font-weight:500">坪數</th><th style="padding:3px 6px;text-align:right;color:#64748b;font-weight:500">佔比</th><th style="padding:3px 6px;text-align:right;color:#64748b;font-weight:500">金額</th></tr>';
            $rank = 0;
            foreach ($d['seriesRanking'] as $sr) {
                $rank++;
                $h .= '<tr style="border-top:1px solid #f1f5f9">';
                $h .= '<td style="padding:3px 6px;color:#94a3b8;font-size:11px">' . $rank . '</td>';
                $h .= '<td style="padding:3px 6px;font-weight:600">' . $sr['series'] . '</td>';
                $h .= '<td style="padding:3px 6px;text-align:right">' . number_format($sr['pings'], 1) . ' 坪</td>';
                $h .= '<td style="padding:3px 6px;text-align:right;color:#64748b">' . $sr['pct'] . '%</td>';
                $h .= '<td style="padding:3px 6px;text-align:right">' . $this->fmtW($sr['amount']) . '</td>';
                $h .= '</tr>';
                foreach ($sr['skus'] as $sk) {
                    $h .= '<tr><td colspan="2" style="padding:1px 6px 1px 20px;font-size:11px;color:#94a3b8">├ ' . $sk['sku'] . '</td>';
                    $h .= '<td style="padding:1px 6px;text-align:right;font-size:11px;color:#94a3b8">' . number_format($sk['pings'], 1) . ' 坪</td>';
                    $h .= '<td colspan="2"></td></tr>';
                }
            }
            $h .= '</table>';
        }

        // Customer ranking
        if (count($d['custRanking']) > 0) {
            $h .= '<div style="font-size:13px;font-weight:600;margin:10px 0 6px;color:' . $c . '">👥 客戶排行（' . $d['displayLabel'] . '）</div>';
            $h .= '<table style="width:100%;border-collapse:collapse;font-size:12px">';
            $h .= '<tr><th style="padding:3px 6px;text-align:left;color:#64748b;font-weight:500">客戶</th><th style="padding:3px 6px;text-align:right;color:#64748b;font-weight:500">金額</th><th style="padding:3px 6px;text-align:right;color:#64748b;font-weight:500">佔比</th><th style="padding:3px 6px;color:#64748b;font-weight:500">明細</th></tr>';
            foreach ($d['custRanking'] as $name => $amt) {
                $items = $d['custItems'][$name] ?? [];
                $detailParts = [];
                foreach ($items as $item) {
                    $detailParts[] = ($item['seriesCn'] ?: '') . ' ' . $item['code'] . ' ' . round($item['qty']) . '片';
                }
                $detail = implode('<br>', array_slice($detailParts, 0, 3));
                if (count($detailParts) > 3) $detail .= '<br><span style="color:#94a3b8">…還有 ' . (count($detailParts) - 3) . ' 筆</span>';
                $h .= '<tr style="border-top:1px solid #f1f5f9"><td style="padding:3px 6px">' . $name . '</td>';
                $h .= '<td style="padding:3px 6px;text-align:right">' . $this->fmtW($amt) . '</td>';
                $h .= '<td style="padding:3px 6px;text-align:right;color:#64748b">' . round($amt / $d['custTotalAmt'] * 100) . '%</td>';
                $h .= '<td style="padding:3px 6px;font-size:11px;color:#64748b">' . $detail . '</td></tr>';
            }
            $h .= '</table>';
        }

        // Sales rep stats
        if (count($d['salesRanking']) > 0) {
            $h .= '<div style="font-size:13px;font-weight:600;margin:10px 0 6px;color:' . $c . '">📊 業務統計</div>';
            $h .= '<table style="width:100%;border-collapse:collapse;font-size:12px">';
            $h .= '<tr><th style="padding:3px 6px;text-align:left;color:#64748b;font-weight:500">業務</th><th style="padding:3px 6px;text-align:right;color:#64748b;font-weight:500">當日</th><th style="padding:3px 6px;text-align:right;color:#64748b;font-weight:500">佔比</th><th style="padding:3px 6px;text-align:right;color:#64748b;font-weight:500">當月</th><th style="padding:3px 6px;text-align:right;color:#64748b;font-weight:500">佔比</th></tr>';
            foreach ($d['salesRanking'] as $r) {
                $h .= '<tr style="border-top:1px solid #f1f5f9"><td style="padding:3px 6px">' . $r['name'] . '</td>';
                $h .= '<td style="padding:3px 6px;text-align:right">' . $this->fmtW($r['today']) . '</td>';
                $h .= '<td style="padding:3px 6px;text-align:right;color:#64748b">' . round($r['today'] / $d['salesTodayTotalAmt'] * 100) . '%</td>';
                $h .= '<td style="padding:3px 6px;text-align:right">' . $this->fmtW($r['month']) . '</td>';
                $h .= '<td style="padding:3px 6px;text-align:right;color:#64748b">' . round($d['monthTotal'] > 0 ? $r['month'] / $d['monthTotal'] * 100 : 0) . '%</td></tr>';
            }
            $h .= '</table>';
        }

        $h .= '</div></div>';
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
        $lines[] = '<b>📊 集團業績彙報 — ' . $todayStr . '</b>';
        $lines[] = '';
        $lines[] = '集團今日：<b>' . $this->fmtW($grandToday) . '</b>  本月：<b>' . $this->fmtW($grandMonth) . '</b>  YTD：<b>' . $this->fmtW($grandYtd) . '</b>';
        $lines[] = '';

        $emoji = ['高雅瓷' => '🟤', '安帝嘉' => '🟢', '喜悅納' => '🔵'];
        foreach ($allData as $d) {
            $co = $d['co'];
            $e = $emoji[$co['name']] ?? '●';
            $lines[] = $e . ' <b>' . $co['name'] . '</b>';
            $lines[] = '  今日 ' . $this->fmtW($d['todayTotal']) . '  本月 ' . $this->fmtW($d['monthTotal']) . '  YTD ' . $this->fmtW($d['ytdTotal']);
            if ($d['lyMonthTotal'] > 0) {
                $mYoy = ($d['monthTotal'] - $d['lyMonthTotal']) / $d['lyMonthTotal'] * 100;
                $yYoy = ($d['ytdTotal'] - $d['lyYtdTotal']) / $d['lyYtdTotal'] * 100;
                $lines[] = '  月YOY ' . $this->_tgYoy($mYoy) . '  YTD YOY ' . $this->_tgYoy($yYoy);
            }
            $topSeries = array_slice($d['seriesRanking'], 0, 5);
            if (count($topSeries) > 0) {
                $lines[] = '  🏆 熱銷 Top 5：';
                foreach ($topSeries as $sr) {
                    $lines[] = '    ' . $sr['series'] . ' ' . number_format($sr['pings'], 1) . '坪 ' . $sr['pct'] . '%';
                }
            }
            $lines[] = '';
        }

        $lines[] = '<a href="https://bigt.cc/sleeping-beauty/group_meeting.php">🔗 看完整集團月報</a>';
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
            ['key' => 'main',   'name' => '高雅瓷', 'ssId' => SS_ID_MAIN,   'color' => '#c29d66'],
            ['key' => 'andyga', 'name' => '安帝嘉', 'ssId' => SS_ID_ANDYGA, 'color' => '#10b981'],
            ['key' => 'xiyena', 'name' => '喜悅納', 'ssId' => SS_ID_XIYENA, 'color' => '#38bdf8'],
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
