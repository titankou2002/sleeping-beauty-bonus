<?php
// ====== ProjectTrait ======
trait ProjectTrait
{

    private function classifyTaskLabel($text)
    {
        $text = trim((string)$text);
        if ($text === '') return '其他';
        $rules = [
            '送樣' => ['送樣'],
            '送貨' => ['送貨', '配送完工', '出貨'],
            '版面' => ['版面上架', '樣品結案', '版面巡視'],
            '追蹤' => ['案件追蹤', '業務更新', '聊天', '拜訪', '收款', '開會'],
            '回公司' => ['回公司', '公司()'],
            '退貨' => ['退貨']
        ];
        foreach ($rules as $label => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($text, $kw) !== false) return $label;
            }
        }
        return '其他';
    }

    private function parseWorkLogCustomers($customerText)
    {
        $text = trim((string)$customerText);
        if ($text === '') return [];

        $parts = preg_split('/\|\|/u', $text);
        $results = [];
        foreach ($parts as $part) {
            $part = preg_replace('/\[[^\]]*\]/u', '', trim((string)$part));
            $part = trim((string)$part);
            if ($part === '') continue;
            $part = preg_split('/[()（）]/u', $part)[0] ?? $part;
            $part = preg_split('/[-－—]/u', $part)[0] ?? $part;
            $part = trim((string)$part);
            if ($part === '') continue;
            if (preg_match('/^(公司|回公司|測試|內湖|台中|台南|高雄)$/u', $part)) continue;
            $norm = $this->displayCustomerName($part);
            if ($norm === '' || $norm === '未知客戶') continue;
            $results[$norm] = $norm;
        }
        return array_values($results);
    }

    private function categorizeVisitTask($text)
    {
        $text = (string)$text;
        if (trim($text) === '') return '其他';
        foreach (self::$taskCategoryRules as $cat => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($text, $kw) !== false) return $cat;
            }
        }
        return '其他';
    }

    public function scanProjectFlags()
    {
        $rows = $this->gs->readSheet(SALES_SHEET);
        if (count($rows) < 2) return ['success' => false, 'msg' => '經銷銷售報表無資料'];

        $headers = $rows[0];
        $idx = [
            'date' => $this->findHeader($headers, ['單據日期', '銷貨日期', '日期']),
            'customer' => $this->findHeader($headers, ['客戶名稱', '客戶']),
            'custCode' => $this->findHeader($headers, ['客戶編號', '客戶代碼', '代碼']),
            'code' => $this->findHeader($headers, ['產品編號', '編號', '品號']),
            'qty' => $this->findHeader($headers, ['數量', '銷貨數量', '片數']),
            'amount' => $this->findHeader($headers, ['金額', '銷貨金額', '銷額']),
            'address' => $this->findHeader($headers, ['送貨地址', '地址', '送貨地點']),
            'project' => $this->findHeader($headers, ['案名', '專案', '工地名稱', '工地']),
            'note' => $this->findHeader($headers, ['備註', '說明']),
            'suspect' => $this->findHeader($headers, ['疑似專案']),
            'projectName' => $this->findHeader($headers, ['專案名稱']),
            'projectType' => $this->findHeader($headers, ['專案類型']),
            'exclude' => $this->findHeader($headers, ['是否排除常態', '是否排除常態分析']),
            'reason' => $this->findHeader($headers, ['專案判定說明']),
            'confirm' => $this->findHeader($headers, ['人工確認']),
            'updatedAt' => $this->findHeader($headers, ['最後更新時間'])
        ];

        foreach (['date', 'customer', 'code', 'qty', 'amount', 'suspect', 'projectName', 'projectType', 'exclude', 'reason', 'confirm', 'updatedAt'] as $key) {
            if ($idx[$key] === -1) {
                return ['success' => false, 'msg' => '經銷銷售報表缺少欄位：' . $key];
            }
        }

        $metaMap = $this->getMetaMap();
        $groups = [];
        $customerMonthlyTotals = [];
        $rowMeta = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $d = $this->parseDate($this->getVal($row, $idx['date']));
            if (!$d) continue;

            $custName = trim($this->getVal($row, $idx['customer']));
            $custCode = $idx['custCode'] !== -1 ? trim($this->getVal($row, $idx['custCode'])) : '';
            $projectRaw = $idx['project'] !== -1 ? trim($this->getVal($row, $idx['project'])) : '';
            $note = $idx['note'] !== -1 ? trim($this->getVal($row, $idx['note'])) : '';
            $amount = $this->optFloat($this->getVal($row, $idx['amount']));
            if ($this->isSampleRow($custCode, $custName, $projectRaw . ' ' . $note, $amount)) continue;

            $qty = $this->optFloat($this->getVal($row, $idx['qty']));
            if ($qty <= 0 || $amount <= 0) continue;

            $sku = $this->cleanSku($this->getVal($row, $idx['code']));
            if ($sku === '') continue;

            $customer = $this->displayCustomerName($custName);
            $yearMonth = $d->format('Y-m');
            if (!isset($customerMonthlyTotals[$customer])) $customerMonthlyTotals[$customer] = [];
            if (!isset($customerMonthlyTotals[$customer][$yearMonth])) $customerMonthlyTotals[$customer][$yearMonth] = 0;
            $customerMonthlyTotals[$customer][$yearMonth] += $amount;

            $address = $idx['address'] !== -1 ? trim($this->getVal($row, $idx['address'])) : '';
            $isLogisticsAddress = $this->isLogisticsAddress($address);
            $addressKey = $this->normalizeAddress($address);
            $projectKey = $this->normalizeCustomerName($projectRaw);
            $entityKey = $projectKey !== '' ? $projectKey : ($isLogisticsAddress ? '' : $addressKey);
            if ($entityKey === '') continue;

            $perPing = isset($metaMap[$sku]) ? ($metaMap[$sku]['perPing'] ?: 36) : 36;
            $pings = abs($qty) / $perPing;
            $groupKey = $yearMonth . '|' . $customer . '|' . $entityKey;

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'customer' => $customer,
                    'yearMonth' => $yearMonth,
                    'address' => $address,
                    'isLogisticsAddress' => $isLogisticsAddress,
                    'projectRaw' => $projectRaw,
                    'rows' => [],
                    'amount' => 0,
                    'pings' => 0,
                    'skuMap' => []
                ];
            }
            $groups[$groupKey]['rows'][] = $i;
            $groups[$groupKey]['amount'] += $amount;
            $groups[$groupKey]['pings'] += $pings;
            $groups[$groupKey]['skuMap'][$sku] = true;

            $rowMeta[$i] = ['customer' => $customer, 'address' => $address, 'projectRaw' => $projectRaw];
        }

        $customerBaselines = [];
        foreach ($customerMonthlyTotals as $customer => $monthMap) {
            $customerBaselines[$customer] = $this->peakShavedAverage(array_values($monthMap));
        }

        $flaggedRows = [];
        $flaggedGroups = 0;
        foreach ($groups as $group) {
            $rowCount = count($group['rows']);
            $skuCount = count($group['skuMap']);
            $baseline = isset($customerBaselines[$group['customer']]) ? $customerBaselines[$group['customer']] : 0;
            $reasons = [];
            $score = 0;

            if (!$group['isLogisticsAddress'] && $group['address'] !== '' && $rowCount >= 2) {
                $reasons[] = '同地址集中出貨';
                $score += 2;
            }
            if ($group['projectRaw'] !== '') {
                $reasons[] = '含案名/工地欄位';
                $score += 1;
            }
            if ($group['isLogisticsAddress']) {
                $reasons[] = '貨運/物流/加工地址降權';
                $score -= 3;
            }
            if ($group['amount'] >= 1000000) {
                $reasons[] = '單月金額破 100 萬';
                $score += 3;
            } elseif ($group['amount'] >= 500000) {
                $reasons[] = '單月金額破 50 萬';
                $score += 2;
            }
            if ($group['pings'] >= 50) {
                $reasons[] = '單月坪數破 50 坪';
                $score += 3;
            } elseif ($group['pings'] >= 20) {
                $reasons[] = '單月坪數破 20 坪';
                $score += 2;
            }
            if ($skuCount >= 3 && $group['amount'] >= 300000) {
                $reasons[] = '同址多 SKU 集中';
                $score += 1;
            }
            if ($baseline > 0 && $group['amount'] >= max($baseline * 2.2, $baseline + 300000)) {
                $reasons[] = '高於客戶去峰值常態月銷';
                $score += 2;
            }

            if ($score < 3) continue;
            $flaggedGroups++;
            $suggestedType = (!$group['isLogisticsAddress'] && $group['address'] !== '' && $group['amount'] >= 800000 && $rowCount <= 4) ? '直送工地' : '現貨出庫';
            $suggestedExclude = $suggestedType === '直送工地' ? '是' : '否';
            $suggestedName = $this->shortProjectName($group['customer'], $group['projectRaw'], $group['address']);
            $cleanReasons = array_values(array_filter(array_unique($reasons), function ($reason) {
                return $reason !== '貨運/物流/加工地址降權';
            }));
            $reasonText = implode('、', $cleanReasons) . '｜' . $group['yearMonth'] . '｜' . (int)floor($group['amount'] / 10000) . '萬';

            foreach ($group['rows'] as $rowIndex) {
                $flaggedRows[$rowIndex] = [
                    'suspect' => '是',
                    'projectName' => $suggestedName,
                    'projectType' => $suggestedType,
                    'exclude' => $suggestedExclude,
                    'reason' => $reasonText
                ];
            }
        }

        $updatedAt = date('Y/m/d H:i:s');
        $startCol = $idx['suspect'];
        $writeRows = [];
        $chunkStartRow = 2;
        $written = 0;

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $existingProjectName = trim($this->getVal($row, $idx['projectName']));
            $existingProjectType = trim($this->getVal($row, $idx['projectType']));
            $existingExclude = trim($this->getVal($row, $idx['exclude']));
            $existingConfirm = trim($this->getVal($row, $idx['confirm']));
            $existingReason = trim($this->getVal($row, $idx['reason']));
            $hasManual = $existingConfirm !== '' || $existingProjectName !== '' || $existingProjectType !== '' || $existingExclude !== '';

            $flag = isset($flaggedRows[$i]) ? $flaggedRows[$i] : null;
            if ($flag) {
                $values = [
                    '是',
                    $existingProjectName !== '' ? $existingProjectName : $flag['projectName'],
                    $existingProjectType !== '' ? $existingProjectType : $flag['projectType'],
                    $existingExclude !== '' ? $existingExclude : $flag['exclude'],
                    $flag['reason'],
                    $existingConfirm !== '' ? $existingConfirm : '待確認',
                    $updatedAt
                ];
            } elseif ($hasManual) {
                $values = [
                    trim($this->getVal($row, $idx['suspect'])) ?: '否',
                    $existingProjectName,
                    $existingProjectType,
                    $existingExclude,
                    $existingReason,
                    $existingConfirm,
                    trim($this->getVal($row, $idx['updatedAt']))
                ];
            } else {
                $values = ['否', '', '', '', '', '', ''];
            }

            $writeRows[] = $values;
            if (count($writeRows) >= 500) {
                $this->gs->writeBlock(SALES_SHEET, $chunkStartRow, $startCol, $writeRows);
                $written += count($writeRows);
                $chunkStartRow += count($writeRows);
                $writeRows = [];
            }
        }
        if (count($writeRows)) {
            $this->gs->writeBlock(SALES_SHEET, $chunkStartRow, $startCol, $writeRows);
            $written += count($writeRows);
        }

        return [
            'success' => true,
            'scannedRows' => max(0, count($rows) - 1),
            'suspectedGroups' => $flaggedGroups,
            'suspectedRows' => count($flaggedRows),
            'writtenRows' => $written,
            'updatedAt' => $updatedAt
        ];
    }
}
