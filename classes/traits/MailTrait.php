<?php
// ====== MailTrait ======
trait MailTrait
{

    public function sendDailyPerformanceReport($recipients, $fromEmail = '', $type = 'group')
    {
        $today = new DateTime('today');
        $todayStr = $today->format('Y/m/d');
        $monthStart = new DateTime($today->format('Y-m-01'));

        $allCompanies = [
            ['key' => 'main', 'name' => '高雅瓷', 'ssId' => SS_ID_MAIN, 'color' => '#c29d66'],
            ['key' => 'andyga', 'name' => '安帝嘉', 'ssId' => SS_ID_ANDYGA, 'color' => '#0284c7'],
            ['key' => 'xiyena', 'name' => '喜悅納', 'ssId' => SS_ID_XIYENA, 'color' => '#0284c7'],
        ];
        $companies = $type === 'main' ? array_slice($allCompanies, 0, 1) : $allCompanies;

        $allHtml = '';
        $grandTodayTotal = 0;
        $grandMonthTotal = 0;
        $sections = [];

        foreach ($companies as $co) {
            try {
                $gs = $this->getClient($co['ssId']);
                $salesRows = $gs->readSheet(SALES_SHEET);
            } catch (Exception $e) {
                $salesRows = [];
            }
            $todayItems = [];
            $monthItems = [];

            if (count($salesRows) > 1) {
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
                    $qty = $this->optFloat($this->getVal($row, $idx['qty']));
                    $item = ['cust' => $custName, 'salesName' => $salesName, 'amt' => $amt, 'code' => $code, 'seriesCn' => $seriesCn, 'qty' => $qty, 'd' => $d];
                    if ($d >= $today) $todayItems[] = $item;
                    if ($d >= $monthStart) $monthItems[] = $item;
                }
            }

            $todayTotal = array_sum(array_column($todayItems, 'amt'));
            $monthTotal = array_sum(array_column($monthItems, 'amt'));
            $grandTodayTotal += $todayTotal;
            $grandMonthTotal += $monthTotal;

            $lastTxDate = null;
            foreach ($monthItems as $item) {
                if (!$lastTxDate || $item['d'] > $lastTxDate) $lastTxDate = $item['d'];
            }
            $displayItems = count($todayItems) > 0 ? $todayItems : [];
            $displayLabel = '當日';
            if (count($todayItems) === 0 && $lastTxDate) {
                $displayItems = array_values(array_filter($monthItems, function ($item) use ($lastTxDate) {
                    return $item['d'] == $lastTxDate;
                }));
                $displayLabel = $lastTxDate->format('m/d') . ' 交易';
            }

            $custMap = [];
            $custItems = [];
            foreach ($displayItems as $item) {
                $shortName = $this->displayCustomerName($item['cust']);
                $custMap[$shortName] = ($custMap[$shortName] ?? 0) + $item['amt'];
                if (!isset($custItems[$shortName])) $custItems[$shortName] = [];
                $custItems[$shortName][] = $item;
            }
            arsort($custMap);
            $sortedCust = array_slice($custMap, 0, 10);
            $custTotalAmt = max(1, array_sum($custMap));

            $salesTodayMap = [];
            $salesMonthMap = [];
            foreach ($todayItems as $item) {
                $salesTodayMap[$item['salesName']] = ($salesTodayMap[$item['salesName']] ?? 0) + $item['amt'];
            }
            foreach ($monthItems as $item) {
                $salesMonthMap[$item['salesName']] = ($salesMonthMap[$item['salesName']] ?? 0) + $item['amt'];
            }
            $allSales = array_unique(array_merge(array_keys($salesTodayMap), array_keys($salesMonthMap)));
            $sortedSales = [];
            foreach ($allSales as $name) {
                $sortedSales[] = ['name' => $name, 'today' => $salesTodayMap[$name] ?? 0, 'month' => $salesMonthMap[$name] ?? 0];
            }
            usort($sortedSales, function ($a, $b) { return $b['today'] - $a['today']; });
            $salesTodayTotalAmt = max(1, array_sum($salesTodayMap));

            $seriesMap = [];
            foreach ($displayItems as $item) {
                $s = $item['seriesCn'] ?: '其他';
                $seriesMap[$s] = ($seriesMap[$s] ?? 0) + $item['amt'];
            }
            arsort($seriesMap);
            $sortedSeries = array_slice($seriesMap, 0, 5);

            $secHtml = '<div style="margin-bottom:24px">';
            $secHtml .= '<h3 style="color:' . $co['color'] . ';border-bottom:2px solid ' . $co['color'] . ';padding-bottom:6px;">' . $co['name'] . '</h3>';
            $todayColor = $todayTotal === 0 ? '#94a3b8' : '#059669';
            $lastTxNote = ($todayTotal === 0 && $lastTxDate) ? '（最後交易日：' . $lastTxDate->format('Y/m/d') . '）' : '';
            $secHtml .= '<div style="display:flex;gap:20px;margin-bottom:12px">';
            $secHtml .= '<div style="font-size:16px;font-weight:700;color:' . $todayColor . ';">今日銷貨：' . $this->fmtW($todayTotal) . $lastTxNote . '</div>';
            $secHtml .= '<div style="font-size:16px;font-weight:700;color:#2563eb;">當月累計：' . $this->fmtW($monthTotal) . '</div></div>';

            if (count($sortedCust) > 0) {
                $secHtml .= '<div style="font-size:13px;font-weight:600;margin:8px 0 4px">客戶排行（' . $displayLabel . '）</div>';
                $secHtml .= '<table style="width:100%;border-collapse:collapse;font-size:12px;"><tr style="background:#1e293b;color:white;"><th style="padding:4px 8px;text-align:left">客戶</th><th style="padding:4px 8px;text-align:right">金額</th><th style="padding:4px 8px;text-align:right">佔比</th><th style="padding:4px 8px">明細</th></tr>';
                foreach ($sortedCust as $name => $amt) {
                    $items = $custItems[$name] ?? [];
                    $detailParts = [];
                    foreach ($items as $item) {
                        $detailParts[] = ($item['seriesCn'] ?: '') . ' ' . $item['code'] . ' ' . round($item['qty']) . '片';
                    }
                    $detail = implode('<br>', $detailParts);
                    $secHtml .= '<tr style="border-bottom:1px solid #e2e8f0;"><td style="padding:4px 8px">' . $name . '</td><td style="padding:4px 8px;text-align:right">' . $this->fmtW($amt) . '</td><td style="padding:4px 8px;text-align:right">' . round($amt / $custTotalAmt * 100) . '%</td><td style="padding:4px 8px;font-size:11px;color:#64748b;">' . $detail . '</td></tr>';
                }
                $secHtml .= '</table>';
            }

            if (count($sortedSales) > 0) {
                $secHtml .= '<div style="font-size:13px;font-weight:600;margin:8px 0 4px">業務統計</div>';
                $secHtml .= '<table style="width:100%;border-collapse:collapse;font-size:12px;"><tr style="background:#1e293b;color:white;"><th style="padding:4px 8px;text-align:left">業務</th><th style="padding:4px 8px;text-align:right">當日</th><th style="padding:4px 8px;text-align:right">佔比</th><th style="padding:4px 8px;text-align:right">當月</th><th style="padding:4px 8px;text-align:right">佔比</th></tr>';
                foreach ($sortedSales as $r) {
                    $secHtml .= '<tr style="border-bottom:1px solid #e2e8f0;"><td style="padding:4px 8px">' . $r['name'] . '</td><td style="padding:4px 8px;text-align:right">' . $this->fmtW($r['today']) . '</td><td style="padding:4px 8px;text-align:right">' . round($r['today'] / $salesTodayTotalAmt * 100) . '%</td><td style="padding:4px 8px;text-align:right">' . $this->fmtW($r['month']) . '</td><td style="padding:4px 8px;text-align:right">' . round($monthTotal > 0 ? $r['month'] / $monthTotal * 100 : 0) . '%</td></tr>';
                }
                $secHtml .= '</table>';
            }

            if (count($sortedSeries) > 0) {
                $secHtml .= '<div style="font-size:13px;font-weight:600;margin:8px 0 4px">系列銷售 Top 5（' . $displayLabel . '）</div>';
                $secHtml .= '<table style="width:100%;border-collapse:collapse;font-size:12px;"><tr style="background:#1e293b;color:white;"><th style="padding:4px 8px;text-align:left">系列</th><th style="padding:4px 8px;text-align:right">金額</th></tr>';
                foreach ($sortedSeries as $name => $amt) {
                    $secHtml .= '<tr style="border-bottom:1px solid #e2e8f0;"><td style="padding:4px 8px">' . $name . '</td><td style="padding:4px 8px;text-align:right">' . $this->fmtW($amt) . '</td></tr>';
                }
                $secHtml .= '</table>';
            }

            $secHtml .= '</div>';
            $sections[] = $secHtml;
        }

        $title = $type === 'main' ? '高雅瓷每日戰報' : '集團每日戰報';
        $allHtml = '<div style="font-family:-apple-system,\'PingFang TC\',sans-serif;max-width:720px;margin:0 auto;color:#333;">';
        $allHtml .= '<h2 style="color:#c29d66;border-bottom:3px solid #c29d66;padding-bottom:8px;">' . $title . ' ' . $todayStr . '</h2>';
        $allHtml .= '<div style="display:flex;gap:20px;margin-bottom:20px;padding:12px 16px;background:#f8fafc;border-radius:8px;">';
        $allHtml .= '<div><span style="font-size:12px;color:#64748b;">集團今日</span><br><span style="font-size:20px;font-weight:800;color:' . ($grandTodayTotal > 0 ? '#059669' : '#94a3b8') . ';">' . $this->fmtW($grandTodayTotal) . '</span></div>';
        $allHtml .= '<div><span style="font-size:12px;color:#64748b;">集團本月</span><br><span style="font-size:20px;font-weight:800;color:#2563eb;">' . $this->fmtW($grandMonthTotal) . '</span></div>';
        $allHtml .= '</div>';
        $allHtml .= implode('', $sections);
        $allHtml .= '<p style="font-size:12px;color:#94a3b8;margin-top:20px;">' . ($type === 'main' ? '高雅瓷' : '睡美人戰情室') . ' AI 系統自動發送</p></div>';

        $subject = $title . ' ' . $todayStr . ' | 銷貨' . $this->fmtW($grandTodayTotal) . ' 月累' . $this->fmtW($grandMonthTotal);
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . ($fromEmail ?: DAILY_EMAIL_FROM),
            'To: ' . $recipients
        ];

        $ok = mail($recipients, $subject, $allHtml, implode("\r\n", $headers));
        return ['success' => $ok, 'todayTotal' => $grandTodayTotal, 'monthTotal' => $grandMonthTotal, 'subject' => $subject];
    }
}
