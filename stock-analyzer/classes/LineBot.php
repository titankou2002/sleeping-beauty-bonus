<?php
class LineBot
{
    private string $token;
    private string $userId;

    public function __construct()
    {
        $this->token = LINE_CHANNEL_TOKEN;
        $this->userId = LINE_USER_ID;
    }

    public function sendMessage(string $text, string $userId = ''): bool
    {
        if (!$this->token) return false;
        $userId = $userId ?: $this->userId;
        if (!$userId) return false;

        $body = [
            'to' => $userId,
            'messages' => [
                ['type' => 'text', 'text' => $text],
            ],
        ];

        return $this->call('https://api.line.me/v2/bot/message/push', $body);
    }

    public function sendStockAlert(array $analysis): bool
    {
        $a = $analysis;
        $rec = $a['recommendation'];
        $risk = $a['risk'];
        $tech = $a['technical'];
        $chip = $a['chip'] ?? [];
        $val = $a['valuation'] ?? [];

        $changePct = $a['changePct'] ?? 0;
        $changeIcon = $changePct >= 0 ? '📈' : '📉';
        $dots = $this->renderDots($risk['dots'] ?? [2, 1, 2]);

        $text = "{$a['stockId']} {$a['name'] ?? ''} {$changeIcon}\n";
        $text .= "\${$a['price']} ({$changePct}%)\n";
        $text .= "{$rec['actionIcon']} {$rec['action']}\n";
        $text .= "{$dots}\n";
        $text .= "━━━━━━━━━━━━\n";

        if (!empty($rec['reasons'])) {
            $text .= $rec['summary'] . "\n\n";
        }

        $text .= "📊 技術指標\n";
        $text .= "MA5: {$tech['ma5']} | MA20: {$tech['ma20']}\n";
        $text .= "RSI: {$tech['rsi14']} | K: {$tech['k']} D: {$tech['d']}\n";

        if (!empty($tech['signals'])) {
            $text .= "\n⚡ 信號\n";
            foreach (array_slice($tech['signals'], 0, 3) as $sig) {
                $icon = $sig['type'] === 'bullish' ? '🟢' : '🔴';
                $text .= "{$icon} {$sig['name']}：{$sig['desc']}\n";
            }
        }

        if ($chip && ($chip['trend'] ?? '') !== 'unknown') {
            $text .= "\n💰 籌碼：{$chip['label']}\n";
        }

        if ($val && ($val['zone'] ?? '') !== 'unknown') {
            $text .= "📐 估值：{$val['label']} (PER {$val['per']})\n";
        }

        if (($a['position']['suggestedShares'] ?? 0) > 0) {
            $text .= "\n🎯 建議\n";
            $text .= "停損: \${$a['stopLoss']['stopLoss']}\n";
            $text .= "建議張數: {$a['position']['suggestedLots']} 張\n";
        }

        return $this->sendMessage($text);
    }

    public function sendDailySummary(array $results): bool
    {
        $text = "📈 每日股票摘要 " . date('Y/m/d') . "\n";
        $text .= "━━━━━━━━━━━━\n\n";

        foreach ($results as $r) {
            if (isset($r['error'])) continue;
            $rec = $r['recommendation'];
            $changePct = $r['changePct'] ?? 0;
            $icon = $changePct >= 0 ? '📈' : '📉';
            $dots = $this->renderDots($r['risk']['dots'] ?? [2, 1, 2]);

            $text .= "{$r['stockId']} {$icon} \${$r['price']} ({$changePct}%)\n";
            $text .= "{$rec['actionIcon']} {$rec['action']} {$dots}\n";

            $topSignal = $r['technical']['signals'][0] ?? null;
            if ($topSignal) {
                $sIcon = $topSignal['type'] === 'bullish' ? '🟢' : '🔴';
                $text .= "{$sIcon} {$topSignal['name']}\n";
            }
            $text .= "\n";
        }

        $text .= "💡 打開戰情室查看詳細分析";
        return $this->sendMessage($text);
    }

    public function sendTradingPlan(array $plan): bool
    {
        $text = "📋 明日交易計畫 - {$plan['stockId']} {$plan['name']}\n";
        $text .= "━━━━━━━━━━━━\n";
        $text .= "現價: \${$plan['price']}\n";
        $text .= "MA5: \${$plan['ma5']}\n\n";

        if ($plan['trend'] === 'bullish') {
            $text .= "✅ 多方排列\n";
            $text .= "📍 站穩 MA5 → 買進價 \${$plan['buyPrice']} 以上\n";
            $text .= "📍 跌破 MA5 → 賣出價 \${$plan['sellPrice']}\n";
        } else {
            $text .= "⚠️ 空方排列\n";
            $text .= "📍 反彈 MA5 → 壓力價 \${$plan['resistPrice']}\n";
            $text .= "📍 持續破底 → 觀望\n";
        }

        $text .= "📍 停損價: \${$plan['stopLoss']}\n";

        return $this->sendMessage($text);
    }

    private function renderDots(array $dots): string
    {
        $red = $dots[0] ?? 0;
        $yellow = $dots[1] ?? 0;
        $green = $dots[2] ?? 0;
        return str_repeat('🔴', $red) . str_repeat('🟡', $yellow) . str_repeat('🟢', $green);
    }

    private function call(string $url, array $body): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->token,
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 200;
    }
}
