<?php
// ====== AiTrait ======
trait AiTrait
{

    public function getAiAdvisor($year, $month, $forceRefresh = false)
    {
        $year = (int)$year;
        $month = (int)$month;
        if (!is_dir(AI_ADVISOR_CACHE_DIR)) {
            @mkdir(AI_ADVISOR_CACHE_DIR, 0775, true);
        }
        $cacheFile = AI_ADVISOR_CACHE_DIR . "/advisor_{$year}_{$month}.json";

        if (!$forceRefresh && is_file($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached) {
                return ['success' => true, 'cached' => true, 'data' => $cached];
            }
        }

        if (GEMINI_API_KEY === '') {
            return ['success' => false, 'msg' => '尚未設定 GEMINI_API_KEY'];
        }

        $report = $this->getMeetingReport($year, $month);
        if (!($report['success'] ?? false)) {
            return ['success' => false, 'msg' => '無法取得月報資料: ' . ($report['msg'] ?? '')];
        }

        $inventoryHistory = $this->recordInventorySnapshot($year, $month);
        $summary = $this->buildAdvisorSummary($report['data']);
        $summary['inventoryHistory'] = $inventoryHistory;
        $this->recordReportSnapshot($year, $month, array_merge($summary, [
            'inventoryHistory' => array_combine(
                array_map(function ($r) { return sprintf('%d-%02d', $r['year'], $r['month']); }, $inventoryHistory),
                $inventoryHistory
            )
        ]));
        $sections = $this->callGeminiAdvisor($summary);

        $payload = [
            'generatedAt' => date('Y-m-d H:i:s'),
            'year' => $year,
            'month' => $month,
            'sections' => $sections
        ];
        file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE));
        return ['success' => true, 'cached' => false, 'data' => $payload];
    }

    private function buildAdvisorSummary($d)
    {
        $s = $d['summary'] ?? [];
        $monthCompare = array_filter($d['monthCompare'] ?? [], function ($r) use ($d) {
            return ($r['month'] ?? 0) <= ($d['month'] ?? 12);
        });
        $threeYear = $d['threeYearCompare'] ?? [];
        $brandSales = array_slice($d['brandSales'] ?? [], 0, 6);
        $countryBrand = [];
        foreach (($d['countryBrandRanking'] ?? []) as $country => $brands) {
            $countryBrand[$country] = array_map(function ($b) {
                return ['name' => $b['name'] ?? '', 'amount' => round($b['amount'] ?? 0), 'sharePct' => round($b['sharePct'] ?? 0, 1)];
            }, array_slice($brands, 0, 5));
        }
        $buckets = array_map(function ($row) {
            return [
                'name' => $row['name'] ?? '',
                'count' => $row['count'] ?? 0,
                'amount' => round($row['amount'] ?? 0),
                'customerSharePct' => round($row['customerSharePct'] ?? 0, 1),
                'salesSharePct' => round($row['salesSharePct'] ?? 0, 1),
                'avgVisitsPerCustomer' => round($row['avgVisitsPerCustomer'] ?? 0, 1),
                'salesPerVisit' => round($row['salesPerVisit'] ?? 0)
            ];
        }, $d['shipmentBuckets'] ?? []);
        $contracts = $d['contracts'] ?? [];
        $contractSummary = $contracts['summary'] ?? [];
        $healthCounts = $contracts['healthCounts'] ?? [];
        $topCustomers = array_map(function ($row) {
            return ['name' => $row['name'] ?? '', 'amount' => round($row['amount'] ?? 0), 'pings' => $row['pings'] ?? 0];
        }, array_slice($d['topCustomers'] ?? [], 0, 10));
        $topSales = array_map(function ($row) {
            return [
                'name' => $row['name'] ?? '',
                'amount' => round($row['amount'] ?? 0),
                'count' => $row['count'] ?? 0,
                'top80CustomerCount' => $row['top80CustomerCount'] ?? 0
            ];
        }, array_slice($d['topSales'] ?? [], 0, 10));
        $field = $d['fieldActivity'] ?? [];
        $fieldSummary = $field['summary'] ?? [];

        return [
            'year' => $d['year'],
            'month' => $d['month'],
            'kpi' => $s,
            'monthlyCompare' => array_values($monthCompare),
            'threeYearCompare' => array_map(function ($row) use ($d) {
                $months = array_filter($row['months'] ?? [], function ($m) use ($d) {
                    return ($m['month'] ?? 0) <= ($d['month'] ?? 12);
                });
                return [
                    'year' => $row['year'],
                    'total' => round(array_sum(array_map(function ($m) { return $m['amount'] ?? 0; }, $months)))
                ];
            }, $threeYear),
            'brandSales' => array_map(function ($r) {
                return ['name' => $r['name'] ?? '', 'amount' => round($r['amount'] ?? 0), 'sharePct' => round($r['sharePct'] ?? 0, 1)];
            }, $brandSales),
            'countryBrandRanking' => $countryBrand,
            'shipmentBuckets' => $buckets,
            'contractSummary' => [
                'active' => $contractSummary['active'] ?? 0,
                'expiringSoon' => $contractSummary['expiringSoon'] ?? 0,
                'balance' => round($contractSummary['balance'] ?? 0)
            ],
            'contractHealthCounts' => $healthCounts,
            'topCustomers' => $topCustomers,
            'topSales' => $topSales,
            'fieldSummary' => [
                'totalVisits' => $fieldSummary['totalVisits'] ?? 0,
                'visitedCustomers' => $fieldSummary['visitedCustomers'] ?? 0,
                'totalKm' => $fieldSummary['totalKm'] ?? 0,
                'salesPerVisit' => round($fieldSummary['salesPerVisit'] ?? 0)
            ],
            'topSeries' => array_map(function ($r) {
                return [
                    'name' => $r['seriesCn'] ?? $r['series'] ?? '未分類系列',
                    'brand' => $r['brand'] ?? '',
                    'amount' => round($r['totalAmount'] ?? 0),
                    'pings' => $r['totalPings'] ?? 0,
                    'sharePct' => round($r['sharePct'] ?? 0, 1)
                ];
            }, array_slice($d['seriesRanking'] ?? [], 0, 8)),
            'categoryRanking' => array_map(function ($r) {
                return ['name' => $r['name'] ?? '', 'amount' => round($r['amount'] ?? 0), 'sharePct' => round($r['sharePct'] ?? 0, 1)];
            }, $d['categoryRanking'] ?? []),
            'sizeRanking' => array_map(function ($r) {
                return ['name' => $r['name'] ?? '', 'amount' => round($r['amount'] ?? 0), 'sharePct' => round($r['sharePct'] ?? 0, 1)];
            }, $d['sizeRanking'] ?? [])
        ];
    }

    private function callGeminiAdvisor($summary)
    {
        $sectionSchema = [
            'type' => 'OBJECT',
            'properties' => [
                'level' => ['type' => 'STRING', 'enum' => ['good', 'warn', 'danger', 'info']],
                'title' => ['type' => 'STRING'],
                'points' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']]
            ],
            'required' => ['level', 'title', 'points']
        ];
        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'kpi' => $sectionSchema,
                'monthlyCompare' => $sectionSchema,
                'threeYear' => $sectionSchema,
                'brandCountry' => $sectionSchema,
                'health' => $sectionSchema,
                'contract' => $sectionSchema,
                'topCustomers' => $sectionSchema,
                'topSales' => $sectionSchema,
                'field' => $sectionSchema,
                'inventory' => $sectionSchema,
                'series' => $sectionSchema,
                'category' => $sectionSchema
            ],
            'required' => ['kpi', 'monthlyCompare', 'threeYear', 'brandCountry', 'health', 'contract', 'topCustomers', 'topSales', 'field', 'inventory', 'series', 'category']
        ];

        $context = "公司背景與口徑說明（分析時務必納入考量，不要把這些當成問題提出）：\n"
            . "1. 我們是 REFIN 總代理，主要進口歐洲（義大利、西班牙）磁磚，所以品牌與產地本來就會集中在少數幾個品牌/國家，這是正常且預期的結構，不是風險。\n"
            . "2. 「簽約店健康度」是指簽約店家本月實際銷售 / 簽約總額，因為簽約是先收合約票儲值，中南部很多客戶不簽約但仍會出貨，所以同行水準是「超過75%就算不錯」。signedHealthPct 在 70~85% 區間屬於正常偏好的範圍，不要當成警訊；只有明顯低於 60% 才需要留意。\n\n";

        $prompt = "你是一位資深的零售/經銷產業商業顧問，正在幫磁磚經銷商「高雅瓷」看月報數據。\n"
            . $context
            . "請用白話、口語化但專業的語氣，針對下面 JSON 數據的每個區塊，給出簡短的經營解讀與建議。\n"
            . "規則：\n"
            . "1. level 代表整體評價：good=表現良好、info=中性觀察、warn=需留意、danger=有明顯風險。\n"
            . "2. title 是一句結論標題（15字內），points 是 2~4 條具體分析，每條用一句話講清楚「數字代表什麼」+「該怎麼做」。\n"
            . "3. 重點數字可以用 **粗體** 標出（例如 **421萬**）。\n"
            . "4. 不要寫客套話或免責聲明，直接給結論。\n"
            . "5. inventory 區塊請根據 inventoryHistory（每月存貨總成本與坪數）分析存貨金額的月度變化趨勢，是否有異常增減。\n"
            . "6. series 區塊請針對 topSeries（熱銷系列）給出該主推或觀察哪些系列的建議；category 區塊請針對 categoryRanking / sizeRanking 分析產品大類與尺寸結構是否健康。\n"
            . "7. health 區塊請用 80/20 法則分析 shipmentBuckets：例如「20萬以上」家數佔比 vs 業績佔比的落差，代表業績集中在少數大客戶，要分析這代表的風險與機會，並建議該往哪個級距的客戶加強拜訪或開發。\n"
            . "8. 嚴格依照給定的 JSON schema 輸出，不要多餘文字。\n\n"
            . "資料如下：\n" . json_encode($summary, JSON_UNESCAPED_UNICODE);

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;
        $body = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
                'temperature' => 0.4
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE)
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new RuntimeException("Gemini API 錯誤 (HTTP {$httpCode}): {$resp}");
        }

        $result = json_decode($resp, true);
        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $sections = json_decode($text, true);
        if (!is_array($sections)) {
            throw new RuntimeException("Gemini 回應格式錯誤: {$text}");
        }
        return $sections;
    }

    public function customerAiChat($customer, $message, $history, $context)
    {
        if (GEMINI_API_KEY === '') return ['success'=>false,'msg'=>'尚未設定 GEMINI_API_KEY'];
        if (trim($message) === '') return ['success'=>false,'msg'=>'訊息不得為空'];

        // 從 context 建立客戶情境摘要（context 由前端從已載入的資料直接傳入）
        $c = $context;
        $name = $c['name'] ?? $customer;
        $now = new DateTime();
        $year = $now->format('Y');
        $lastYear = $year - 1;

        $ctxLines = [
            "客戶名稱：{$name}",
            "今年累積業績：" . number_format(round(($c['thisYearAmount']??0)/10000,1),1) . " 萬元",
            "去年同期業績：" . number_format(round(($c['lastYearAmount']??0)/10000,1),1) . " 萬元",
            "YOY：" . (isset($c['yoyPct']) ? $c['yoyPct'].'%' : '—'),
            "最後下單：" . ($c['lastOrderDate'] ?? '—') . ($c['daysSinceLastOrder']!==null ? "（{$c['daysSinceLastOrder']} 天前）" : ''),
            "健康狀態：" . ($c['health'] ?? '—'),
            "拜訪次數：" . ($c['visits'] ?? 0) . " 次",
            "平均毛利率：" . (isset($c['avgMarginPct']) ? $c['avgMarginPct'].'%' : '—'),
            "合約狀態：" . ($c['contractHealth'] ?? '無合約') . (isset($c['contractBalance']) ? "，餘額 ".number_format($c['contractBalance'])."元" : '') . ($c['contractExpiry'] ? "，到期 ".$c['contractExpiry'] : ''),
            "現正陳列 SKU 數：" . ($c['activeDisplayCount'] ?? 0),
            "負責業務：" . ($c['salesRep'] ?? '未分配'),
            "最新備註：" . ($c['lastNote'] ? "（{$c['lastNoteDate']}）{$c['lastNote']}" : '無'),
        ];

        // 互動類型
        if (!empty($c['catCounts'])) {
            $catTotal = array_sum($c['catCounts']);
            $catSummary = [];
            foreach ($c['catCounts'] as $cat => $n) {
                $catSummary[] = $cat . ' ' . $n . '次(' . round($n/$catTotal*100) . '%)';
            }
            arsort($c['catCounts']);
            $ctxLines[] = "互動類型分布：" . implode('、', $catSummary);
        }

        // 低毛利案件
        if (!empty($c['lowMarginDeals'])) {
            $deals = array_map(function($d){ return "{$d['date']} {$d['sku']} 毛利率{$d['marginPct']}%"; }, $c['lowMarginDeals']);
            $ctxLines[] = "低毛利案件（最近5筆）：" . implode('；', $deals);
        }

        $systemPrompt = "你是高雅瓷磁磚公司的業務分析顧問，熟悉瓷磚經銷業務。你的任務是根據以下客戶資料，用繁體中文（台灣用語）回答業務主管的問題，提供具體可操作的分析與建議。回答要精準、務實，不要廢話。\n\n" .
            "【客戶資料】\n" . implode("\n", $ctxLines);

        // 建立對話歷史（Gemini 格式）
        $contents = [];
        foreach ($history as $h) {
            if (isset($h['role']) && isset($h['content'])) {
                $contents[] = ['role' => $h['role'], 'parts' => [['text' => $h['content']]]];
            }
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;
        $payload = [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => $contents,
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 4096]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE)
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) return ['success'=>false,'msg'=>"連線失敗：{$err}"];
        $result = json_decode($resp, true);
        $reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ($reply === '') {
            $blockReason = $result['candidates'][0]['finishReason'] ?? ($result['promptFeedback']['blockReason'] ?? 'UNKNOWN');
            return ['success'=>false,'msg'=>"Gemini 無回應（{$blockReason}）"];
        }
        return ['success'=>true,'reply'=>trim($reply)];
    }

    public function newProductAiChat($sku, $product, $message)
    {
        if (GEMINI_API_KEY === '') return ['success' => false, 'msg' => '尚未設定 GEMINI_API_KEY'];

        $monthlySales = $product['monthlySales'] ?? [];
        $monthlyMax = !empty($monthlySales) ? max($monthlySales) : 0;
        $peakMonth = 0;
        foreach ($monthlySales as $i => $v) { if ($v >= $monthlyMax) { $peakMonth = $i + 1; break; } }

        $reps = !empty($product['salesReps']) ? implode('、', $product['salesReps']) : '無資料';
        $custs = !empty($product['customers']) ? implode('、', array_slice($product['customers'], 0, 10)) : '無';
        $areaSales = $product['areaSales'] ?? [];
        $areaStr = '';
        foreach ($areaSales as $a) {
            $areaStr .= "• {$a['area']}：" . number_format($a['amount']) . " 元\n";
        }
        $monthTrend = [];
        foreach ($monthlySales as $i => $v) {
            if ($i % 3 === 0) {
                $monthTrend[] = "第" . ($i + 1) . "月：" . number_format($v) . " 元";
            }
        }

        $series = $product['series'] ?? '未分類';
        $gradeLabel = $product['gradeLabel'] ?? '';
        $gradeDesc = $product['gradeDesc'] ?? '';
        $suggestion = $product['suggestion'] ?? '';
        $firstInDate = $product['firstInDate'] ?? '';
        $ttfsDesc = $product['ttfsDesc'] ?? '';
        $totalAmount = $product['totalAmount'] ?? 0;
        $customerCount = $product['customerCount'] ?? 0;
        $displayCount = $product['displayCount'] ?? 0;
        $score = $product['score'] ?? 0;

        $ctxLines = [
            "【新品銷售分析資料】",
            "SKU：{$sku}",
            "系列：{$series}",
            "首次進貨日：{$firstInDate}",
            "分級：{$gradeLabel}（{$gradeDesc}）",
            "評分：{$score}/12",
            "TTFS（上市到首次交易）：{$ttfsDesc}",
            "總銷售額：" . number_format($totalAmount) . " 元",
            "銷售客戶數：{$customerCount} 家",
            "上架陳列家數：{$displayCount} 家",
            "負責業務：{$reps}",
            "銷售客戶（前10）：{$custs}",
            "銷量高峰：第{$peakMonth}月（{$monthlyMax}元）",
            "月銷售趨勢：\n" . implode("\n", $monthTrend),
            "區域銷售分布：\n{$areaStr}",
            "系統建議：{$suggestion}",
            "分析期間（月）：" . count($monthlySales),
        ];

        $systemPrompt = "你是高雅瓷磁磚公司的資深行銷顧問，擁有 20 年以上瓷磚產業經驗。請根據以下新品銷售資料，用繁體中文（台灣用語）進行專業分析。\n\n" .
            "【分析重點】\n" .
            "1. 上架覆蓋評估：上架家數是否足夠？與銷售家數相比是否有明顯差距？\n" .
            "2. 缺貨與流失風險：從月銷售趨勢判斷是否有不穩定或驟降情形，可能原因為何？\n" .
            "3. 銷售動能：整體銷售表現是否符合預期？成長力道如何？\n" .
            "4. 區域分布：哪些區域表現較好？哪些區域有待拓展？\n" .
            "5. 具體行動建議：應該繼續加碼、調整策略，還是考慮汰換？\n\n" .
            "請用以下格式回覆：\n" .
            "【上架覆蓋】...\n" .
            "【銷售動能】...\n" .
            "【缺貨與風險】...\n" .
            "【區域表現】...\n" .
            "【行動建議】...\n\n" .
            "回答要具體、有數據佐證、務實可操作。\n\n" .
            implode("\n", $ctxLines) . "\n\n" .
            "提問：" . $message;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;
        $payload = [
            'contents' => [['role' => 'user', 'parts' => [['text' => $systemPrompt]]]],
            'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 4096]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE)
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) return ['success' => false, 'msg' => "連線失敗：{$err}"];
        $result = json_decode($resp, true);
        $reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ($reply === '') {
            $blockReason = $result['candidates'][0]['finishReason'] ?? ($result['promptFeedback']['blockReason'] ?? 'UNKNOWN');
            return ['success' => false, 'msg' => "Gemini 無回應（{$blockReason}）"];
        }
        return ['success' => true, 'reply' => trim($reply)];
    }

    public function globalAiChat($message, $history, $tab, $context)
    {
        if (GEMINI_API_KEY === '') return ['success'=>false,'msg'=>'尚未設定 GEMINI_API_KEY'];
        if (trim($message) === '') return ['success'=>false,'msg'=>'訊息不得為空'];

        $tabLabels = ['products'=>'產品總覽', 'reports'=>'銷售報表', 'bonus'=>'睡美人銷售', 'customers'=>'客戶分析'];
        $tabLabel = $tabLabels[$tab] ?? '業務分析';

        // 根據分頁建立情境摘要
        $ctxText = '';
        if ($tab === 'products' && !empty($context['products'])) {
            $lines = ["【產品庫存概覽（共 ".count($context['products'])." 項）】"];
            foreach (array_slice($context['products'], 0, 30) as $p) {
                $lines[] = "• {$p['sku']} {$p['seriesCn']} 庫存 {$p['stockPing']}坪 月銷速 {$p['monthlySpeedPings']}坪/月 健康度 {$p['grade']} 毛利率 {$p['marginPct']}%";
            }
            $ctxText = implode("\n", $lines);
        } elseif ($tab === 'reports' && !empty($context)) {
            $lines = ["【銷售報表摘要】"];
            if (isset($context['periodTotal'])) $lines[] = "本期業績：" . number_format(round($context['periodTotal']/10000,1),1) . "萬";
            if (isset($context['yoyPct'])) $lines[] = "YOY：{$context['yoyPct']}%";
            if (!empty($context['topProducts'])) {
                $lines[] = "暢銷產品 Top10：";
                foreach (array_slice($context['topProducts'],0,10) as $p) {
                    $lines[] = "  {$p['sku']} {$p['seriesCn']} {$p['totalPings']}坪 {$p['totalAmt']}元";
                }
            }
            if (!empty($context['trend'])) {
                $lines[] = "近12月趨勢：";
                foreach (array_slice($context['trend'],0,12) as $m) {
                    $lines[] = "  {$m['month']} 業績 ".number_format(round(($m['amt']??0)/10000,1),1)."萬 去年同期 ".number_format(round(($m['lastYearAmt']??0)/10000,1),1)."萬";
                }
            }
            $ctxText = implode("\n", $lines);
        } elseif ($tab === 'bonus' && !empty($context)) {
            $lines = ["【睡美人銷售摘要】"];
            if (isset($context['thisYearAmt'])) $lines[] = "今年累積業績：".number_format(round($context['thisYearAmt']/10000,1),1)."萬";
            if (isset($context['yoyPct'])) $lines[] = "YOY：{$context['yoyPct']}%";
            if (!empty($context['topSkus'])) {
                $lines[] = "睡美人暢銷前15：";
                foreach (array_slice($context['topSkus'],0,15) as $p) {
                    $lines[] = "  {$p['sku']} {$p['seriesCn']} 庫存{$p['stockPing']}坪 月銷{$p['monthlySpeedPings']}坪 等級{$p['grade']}";
                }
            }
            $ctxText = implode("\n", $lines);
        } elseif ($tab === 'customers' && !empty($context)) {
            $lines = ["【客戶整體概覽】"];
            if (isset($context['customerCount'])) $lines[] = "有效客戶數：{$context['customerCount']}";
            if (isset($context['thisYearAmount'])) $lines[] = "今年整體業績：".number_format(round($context['thisYearAmount']/10000,1),1)."萬";
            if (isset($context['yoyPct'])) $lines[] = "整體YOY：{$context['yoyPct']}%";
            if (!empty($context['healthCounts'])) {
                $lines[] = "客戶健康度：成長中{$context['healthCounts']['growth']} 正常{$context['healthCounts']['normal']} 警示{$context['healthCounts']['warning']} 衰退{$context['healthCounts']['decline']} 沉睡{$context['healthCounts']['dormant']}";
            }
            if (!empty($context['topCustomers'])) {
                $lines[] = "業績 Top 客戶：";
                foreach (array_slice($context['topCustomers'],0,10) as $c) {
                    $lines[] = "  {$c['name']} ".number_format(round($c['totalAmount']/10000,1),1)."萬 YOY".($c['yoyPct']!==null?$c['yoyPct'].'%':'—');
                }
            }
            $ctxText = implode("\n", $lines);
        }

        $systemPrompt = "你是高雅瓷磁磚公司的業務分析 AI 顧問，熟悉瓷磚經銷業務（品牌：elitile，主打睡美人系列磁磚）。你的任務是根據提供的即時資料，用繁體中文（台灣用語）回答業務主管問題，提供具體可操作的分析與建議。回答要精準、務實、有條理，避免廢話。目前使用者在「{$tabLabel}」分頁。\n\n{$ctxText}";

        $contents = [];
        foreach ($history as $h) {
            if (isset($h['role'], $h['content'])) {
                $contents[] = ['role'=>$h['role'], 'parts'=>[['text'=>$h['content']]]];
            }
        }
        $contents[] = ['role'=>'user', 'parts'=>[['text'=>$message]]];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;
        $payload = [
            'system_instruction' => ['parts'=>[['text'=>$systemPrompt]]],
            'contents' => $contents,
            'generationConfig' => ['temperature'=>0.7, 'maxOutputTokens'=>4096]
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_TIMEOUT=>30, CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_POSTFIELDS=>json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        $resp = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
        if ($err) return ['success'=>false,'msg'=>"連線失敗：{$err}"];
        $result = json_decode($resp, true);
        $reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ($reply === '') return ['success'=>false,'msg'=>'Gemini 無回應（'.($result['candidates'][0]['finishReason']??'UNKNOWN').'）'];
        return ['success'=>true,'reply'=>trim($reply)];
    }

    public function explainContractHealth($year, $month)
    {
        if (GEMINI_API_KEY === '') return ['success'=>false,'msg'=>'尚未設定 GEMINI_API_KEY'];
        $report = $this->getGroupDetailedReport($year, $month);
        if (!($report['success'] ?? false)) {
            return ['success'=>false,'msg'=>'無法取得集團資料'];
        }
        $data = $report['data'];
        $ct = $data['contractTotal'] ?? [];
        $hc = $ct['healthCounts'] ?? [];
        $total = $ct['active'] ?? 0;
        $monthlyTarget = $ct['monthlyTarget'] ?? 0;

        $lines = ["集團合約健康度概況（{$year}年{$month}月）"];
        $lines[] = "合約客戶總數：{$total} 家";
        $lines[] = "月目標：{$monthlyTarget} 元";
        $lines[] = "正常：{$hc['正常']} 家，觀察：{$hc['觀察']} 家，警示：{$hc['警示']} 家，危險：{$hc['危險']} 家，黑死：{$hc['黑死']} 家";

        if (!empty($data['companies'])) {
            foreach ($data['companies'] as $ck => $cv) {
                $ch = $cv['contract'] ?? [];
                $chc = $ch['healthCounts'] ?? [];
                $lines[] = "{$ck}：合約客戶 {$ch['active']} 家，正常 {$chc['正常']} 觀察 {$chc['觀察']} 警示 {$chc['警示']} 危險 {$chc['危險']} 黑死 {$chc['黑死']}";
            }
        }

        $detail = [];
        if (!empty($data['companies'])) {
            foreach ($data['companies'] as $ck => $cv) {
                $dd = $cv['contract']['detail'] ?? [];
                foreach ($dd as $d) {
                    $detail[] = $d;
                }
            }
        }

        $balHigh = [];
        $balOld = [];
        foreach ($detail as $d) {
            if ($d['health'] === '危險' || $d['health'] === '黑死' || $d['health'] === '警示') {
                $balHigh[] = "{$d['name']}({$d['health']},餘額{$d['balance']}元,餘額比{$d['balRatio']}%)";
            }
        }
        if (!empty($balHigh)) {
            $lines[] = "需關注的合約：" . implode('；', array_slice($balHigh, 0, 15));
        }

        $ctx = implode("\n", $lines);

        $systemPrompt = "你是高雅瓷磁磚公司（elitile，睡美人磁磚）的業務分析顧問。請根據以下合約健康度數據，用繁體中文（台灣用語）寫一段白話文解說。

解說要包含：
1. 整體健康狀況摘要（健康比例多少，最主要問題在哪）
2. 各公司比較（哪家最需要關注）
3. 重點客戶警示（哪些客戶餘額比過高或逾期過久）
4. 具體建議（應該做什麼行動）

語氣直接、務實，像在跟老闆報告，不要條列式，用一段通順的白話文即可。避免格式化、避免項目符號。

【數據】\n{$ctx}";

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;
        $payload = [
            'system_instruction' => ['parts'=>[['text'=>$systemPrompt]]],
            'contents' => [['role'=>'user', 'parts'=>[['text'=>'請分析上述合約健康狀況，用白話文說明。']]]],
            'generationConfig' => ['temperature'=>0.7, 'maxOutputTokens'=>2048]
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_TIMEOUT=>30, CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_POSTFIELDS=>json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        $resp = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
        if ($err) return ['success'=>false,'msg'=>"連線失敗：{$err}"];
        $result = json_decode($resp, true);
        $reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ($reply === '') return ['success'=>false,'msg'=>'Gemini 無回應'];
        return ['success'=>true, 'reply'=>trim($reply)];
    }
}
