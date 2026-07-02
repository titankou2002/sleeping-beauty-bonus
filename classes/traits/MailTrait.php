<?php
// ====== MailTrait ======
trait MailTrait
{

    public function sendDailyPerformanceReport($recipients, $fromEmail = '')
    {
        $today = new DateTime('today');
        $todayStr = $today->format('Y/m/d');
        $monthStart = new DateTime($today->format('Y-m-01'));

        $salesRows = $this->gs->readSheet(SALES_SHEET);
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

                $item = [
                    'cust' => $custName,
                    'salesName' => $salesName,
                    'amt' => $amt,
                    'code' => $code,
                    'seriesCn' => $seriesCn,
                    'qty' => $qty,
                    'd' => $d
                ];

                if ($d >= $today) $todayItems[] = $item;
                if ($d >= $monthStart) $monthItems[] = $item;
            }
        }

        $todayTotal = array_sum(array_column($todayItems, 'amt'));
        $monthTotal = array_sum(array_column($monthItems, 'amt'));

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

        // 客戶排行
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
        $custTotal = max(1, array_sum($custMap));

        // 業務統計
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
        $salesTodayTotal = max(1, array_sum($salesTodayMap));

        // 系列銷售 Top 5
        $seriesMap = [];
        foreach ($displayItems as $item) {
            $s = $item['seriesCn'] ?: '其他';
            $seriesMap[$s] = ($seriesMap[$s] ?? 0) + $item['amt'];
        }
        arsort($seriesMap);
        $sortedSeries = array_slice($seriesMap, 0, 5);

        // 組 HTML
        $todayColor = $todayTotal === 0 ? '#94a3b8' : '#059669';
        $lastTxNote = ($todayTotal === 0 && $lastTxDate) ? '（最後交易日：' . $lastTxDate->format('Y/m/d') . '）' : '';

        $html = '<div style="font-family:-apple-system,\'PingFang TC\',sans-serif;max-width:700px;margin:0 auto;color:#333;">';
        $html .= '<h2 style="color:#c29d66;border-bottom:3px solid #c29d66;padding-bottom:8px;">高雅瓷每日戰報 ' . $todayStr . '</h2>';
        $html .= '<h3 style="color:' . $todayColor . ';">今日銷貨：' . $this->fmtW($todayTotal) . $lastTxNote . '</h3>';
        $html .= '<h3 style="color:#2563eb;">當月累計：' . $this->fmtW($monthTotal) . '</h3>';

        if (count($sortedCust) > 0) {
            $html .= '<h3>客戶排行（' . $displayLabel . '）</h3>';
            $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px;"><tr style="background:#1e293b;color:white;"><th>客戶</th><th style="text-align:right;">金額</th><th style="text-align:right;">佔比</th><th>明細</th></tr>';
            foreach ($sortedCust as $name => $amt) {
                $items = $custItems[$name] ?? [];
                $detailParts = [];
                foreach ($items as $item) {
                    $detailParts[] = ($item['seriesCn'] ?: '') . ' ' . $item['code'] . ' ' . round($item['qty']) . '片';
                }
                $detail = implode('<br>', $detailParts);
                $html .= '<tr style="border-bottom:1px solid #e2e8f0;"><td>' . $name . '</td><td style="text-align:right;">' . $this->fmtW($amt) . '</td><td style="text-align:right;">' . round($amt / $custTotal * 100) . '%</td><td style="font-size:11px;color:#64748b;">' . $detail . '</td></tr>';
            }
            $html .= '</table>';
        }

        if (count($sortedSales) > 0) {
            $html .= '<h3>業務統計</h3>';
            $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px;"><tr style="background:#1e293b;color:white;"><th>業務</th><th style="text-align:right;">當日</th><th style="text-align:right;">佔比</th><th style="text-align:right;">當月</th><th style="text-align:right;">佔比</th></tr>';
            foreach ($sortedSales as $r) {
                $html .= '<tr style="border-bottom:1px solid #e2e8f0;"><td>' . $r['name'] . '</td><td style="text-align:right;">' . $this->fmtW($r['today']) . '</td><td style="text-align:right;">' . round($r['today'] / $salesTodayTotal * 100) . '%</td><td style="text-align:right;">' . $this->fmtW($r['month']) . '</td><td style="text-align:right;">' . round($monthTotal > 0 ? $r['month'] / $monthTotal * 100 : 0) . '%</td></tr>';
            }
            $html .= '</table>';
        }

        if (count($sortedSeries) > 0) {
            $html .= '<h3>系列銷售 Top 5（' . $displayLabel . '）</h3>';
            $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px;"><tr style="background:#1e293b;color:white;"><th>系列</th><th style="text-align:right;">金額</th></tr>';
            foreach ($sortedSeries as $name => $amt) {
                $html .= '<tr style="border-bottom:1px solid #e2e8f0;"><td>' . $name . '</td><td style="text-align:right;">' . $this->fmtW($amt) . '</td></tr>';
            }
            $html .= '</table>';
        }

        $html .= '<p style="font-size:12px;color:#94a3b8;margin-top:20px;">高雅瓷戰情室 AI 系統自動發送</p></div>';

        $subject = '高雅瓷每日戰報 ' . $todayStr . ' | 銷貨' . $this->fmtW($todayTotal) . ' 月累' . $this->fmtW($monthTotal);

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . ($fromEmail ?: DAILY_EMAIL_FROM),
            'To: ' . $recipients
        ];

        $ok = mail($recipients, $subject, $html, implode("\r\n", $headers));
        return ['success' => $ok, 'todayTotal' => $todayTotal, 'monthTotal' => $monthTotal, 'subject' => $subject];
    }
}
