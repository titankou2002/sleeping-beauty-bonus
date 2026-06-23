<?php
class TelegramBot
{
    private $token;
    private $chatId;

    public function __construct()
    {
        $this->token = TELEGRAM_BOT_TOKEN;
        $this->chatId = TELEGRAM_CHAT_ID;
    }

    public function sendMessage(string $text, string $chatId = '', string $parseMode = 'HTML'): bool
    {
        if (!$this->token) return false;
        $chatId = $chatId ?: $this->chatId;
        if (!$chatId) return false;

        return $this->call('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true,
        ]);
    }

    public function sendStockAlert(array $analysis): bool
    {
        $a = $analysis;
        $rec = $a['recommendation'];
        $risk = $a['risk'];
        $tech = $a['technical'];
        $chip = $a['chip'] ?? [];
        $val = $a['valuation'] ?? [];
        $pos = $a['position'] ?? [];

        $changePct = $a['changePct'] ?? 0;
        $changeIcon = $changePct >= 0 ? '🔺' : '🔻';

        $dots = $this->renderDots($risk['dots'] ?? [2,1,2]);

        $text = "<b>{$a['stockId']}</b> {$changeIcon} \${$a['price']} / {$changePct}%\n";
        $text .= "{$rec['actionIcon']} <b>{$rec['action']}</b>\n";
        $text .= "{$dots}\n\n";

        if (!empty($rec['reasons'])) {
            $text .= $rec['summary'] . "\n\n";
        }

        // Technical summary
        $text .= "📊 <b>技術指標</b>\n";
        $text .= "MA5: {$tech['ma5']} | MA20: {$tech['ma20']} | MA60: {$tech['ma60']}\n";
        $text .= "RSI: {$tech['rsi14']} | K: {$tech['k']} D: {$tech['d']}\n";

        // Signals
        if (!empty($tech['signals'])) {
            $text .= "\n⚡ <b>信號</b>\n";
            foreach (array_slice($tech['signals'], 0, 3) as $sig) {
                $icon = $sig['type'] === 'bullish' ? '🟢' : '🔴';
                $text .= "{$icon} {$sig['name']}：{$sig['desc']}\n";
            }
        }

        // Chip
        if ($chip && $chip['trend'] !== 'unknown') {
            $text .= "\n💰 <b>籌碼</b>：{$chip['label']}\n";
            if ($chip['foreignNet'] ?? false) $text .= "外資: " . number_format($chip['foreignNet']) . "\n";
            if ($chip['warning']) $text .= "⚠️ {$chip['warning']}\n";
        }

        // Valuation
        if ($val && $val['zone'] !== 'unknown') {
            $text .= "\n📐 <b>估值</b>：{$val['label']} (PER {$val['per']})\n";
        }

        // Position
        if ($pos && ($pos['suggestedShares'] ?? 0) > 0) {
            $text .= "\n🎯 <b>建議</b>\n";
            $text .= "停損: \${$a['stopLoss']['stopLoss']}\n";
            $text .= "建議張數: {$pos['suggestedLots']} 張 (\${$pos['investAmount']})\n";
        }

        return $this->sendMessage($text);
    }

    public function sendDailySummary(array $results): bool
    {
        $text = "📈 <b>每日股票摘要</b> " . date('Y/m/d') . "\n\n";

        foreach ($results as $r) {
            if (isset($r['error'])) continue;
            $rec = $r['recommendation'];
            $changePct = $r['changePct'] ?? 0;
            $icon = $changePct >= 0 ? '🔺' : '🔻';
            $dots = $this->renderDots($r['risk']['dots'] ?? [2,1,2]);

            $text .= "<b>{$r['stockId']}</b> {$icon}\${$r['price']} ({$changePct}%)\n";
            $text .= "{$rec['actionIcon']} {$rec['action']} {$dots}\n";

            $topSignal = $r['technical']['signals'][0] ?? null;
            if ($topSignal) {
                $sIcon = $topSignal['type'] === 'bullish' ? '🟢' : '🔴';
                $text .= "{$sIcon} {$topSignal['name']}\n";
            }
            $text .= "\n";
        }

        $text .= "💡 點擊查看詳細分析";
        return $this->sendMessage($text);
    }

    public function handleWebhook(array $update): ?string
    {
        $message = $update['message'] ?? $update['callback_query']['message'] ?? null;
        if (!$message) return null;

        $chatId = (string)($message['chat']['id'] ?? '');
        $text = trim($message['text'] ?? '');

        if (!$text || !$chatId) return null;

        if ($text === '/start') {
            $this->sendMessage("👋 歡迎使用股票戰情室！\n\n指令：\n/check 2330 - 查詢個股\n/watch 2330 - 加入監控\n/list - 查看監控清單\n/daily - 每日摘要", $chatId);
            return 'start';
        }

        if (preg_match('/^\/check\s+(\S+)/i', $text, $m)) {
            return 'check:' . $m[1] . ':' . $chatId;
        }

        if (preg_match('/^\/watch\s+(\S+)/i', $text, $m)) {
            return 'watch:' . $m[1] . ':' . $chatId;
        }

        if ($text === '/list') {
            return 'list:' . $chatId;
        }

        if ($text === '/daily') {
            return 'daily:' . $chatId;
        }

        $this->sendMessage("❓ 未知指令。輸入 /start 查看可用指令。", $chatId);
        return null;
    }

    private function renderDots(array $dots): string
    {
        $red = $dots[0] ?? 0;
        $yellow = $dots[1] ?? 0;
        $green = $dots[2] ?? 0;
        return str_repeat('🔴', $red) . str_repeat('🟡', $yellow) . str_repeat('🟢', $green);
    }

    private function call(string $method, array $params): bool
    {
        $url = "https://api.telegram.org/bot{$this->token}/{$method}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_TIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 200;
    }
}
