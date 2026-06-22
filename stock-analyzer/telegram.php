<?php
/**
 * Telegram Bot webhook endpoint.
 * Set webhook URL: https://api.telegram.org/bot{TOKEN}/setWebhook?url=https://yourdomain.com/stock-analyzer/telegram.php
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json');

set_error_handler(function ($severity, $msg, $file, $line) {
    throw new ErrorException($msg, 0, $severity, $file, $line);
});

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/TwseClient.php';
require_once __DIR__ . '/classes/UsStockClient.php';
require_once __DIR__ . '/classes/TechnicalAnalysis.php';
require_once __DIR__ . '/classes/SignalEngine.php';
require_once __DIR__ . '/classes/TelegramBot.php';
require_once __DIR__ . '/classes/PortfolioManager.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['ok' => true]);
    exit;
}

$bot = new TelegramBot();
$result = $bot->handleWebhook($input);
if (!$result) {
    echo json_encode(['ok' => true]);
    exit;
}

$twse = new TwseClient();
$us = new UsStockClient();
$engine = new SignalEngine();
$portfolio = new PortfolioManager();

$parts = explode(':', $result);
$command = $parts[0];

try {
    switch ($command) {
        case 'check':
            $stockId = strtoupper($parts[1] ?? '');
            $chatId = $parts[2] ?? '';
            if (!$stockId || !$chatId) break;

            $market = preg_match('/^[A-Z]+$/i', $stockId) ? 'us' : 'tw';

            if ($market === 'us') {
                $data = $us->getQuote($stockId);
                if (empty($data['quotes'])) {
                    $bot->sendMessage("❌ 查無美股 {$stockId} 資料", $chatId);
                    break;
                }
                $r = $engine->analyzeStock($stockId, $data['quotes']);
                $r['name'] = $data['name'] ?? $stockId;
                $r['market'] = 'us';
            } else {
                $quotes = $twse->getMultiMonthQuotes($stockId, 6);
                if (empty($quotes)) {
                    $bot->sendMessage("❌ 查無台股 {$stockId} 資料", $chatId);
                    break;
                }
                $peData = $twse->getPERatio($stockId);
                $instData = $twse->getInstitutional30Days($stockId);
                $marginData = $twse->getMarginTrading($stockId);
                $r = $engine->analyzeStock($stockId, $quotes, $peData, $instData, $marginData);
                $info = $twse->getStockInfo($stockId);
                $r['name'] = $info['name'] ?? $stockId;
                $r['market'] = 'tw';
            }

            $bot->sendStockAlert($r);
            break;

        case 'watch':
            $stockId = strtoupper($parts[1] ?? '');
            $chatId = $parts[2] ?? '';
            if (!$stockId || !$chatId) break;

            $market = preg_match('/^[A-Z]+$/i', $stockId) ? 'us' : 'tw';
            $portfolio->addToWatchlist($stockId, $market);
            $bot->sendMessage("✅ 已將 {$stockId} 加入監控清單", $chatId);
            break;

        case 'list':
            $chatId = $parts[1] ?? '';
            if (!$chatId) break;

            $list = $portfolio->getWatchlist();
            if (empty($list)) {
                $bot->sendMessage("📋 監控清單為空\n使用 /watch 代碼 加入股票", $chatId);
                break;
            }

            $text = "📋 <b>監控清單</b>\n\n";
            foreach ($list as $item) {
                $flag = ($item['market'] ?? 'tw') === 'us' ? '🇺🇸' : '🇹🇼';
                $text .= "{$flag} <b>{$item['stockId']}</b>";
                if ($item['entryPrice'] > 0) $text .= " (進場: \${$item['entryPrice']})";
                $text .= "\n";
            }
            $text .= "\n共 " . count($list) . " 檔";
            $bot->sendMessage($text, $chatId);
            break;

        case 'daily':
            $chatId = $parts[1] ?? '';
            if (!$chatId) break;

            $bot->sendMessage("⏳ 正在掃描所有監控股票，請稍候...", $chatId);

            $list = $portfolio->getWatchlist();
            if (empty($list)) {
                $bot->sendMessage("📋 監控清單為空", $chatId);
                break;
            }

            $results = [];
            foreach ($list as $item) {
                try {
                    $sid = $item['stockId'];
                    $mkt = $item['market'] ?? 'tw';
                    if ($mkt === 'us') {
                        $data = $us->getQuote($sid);
                        if (empty($data['quotes'])) continue;
                        $r = $engine->analyzeStock($sid, $data['quotes']);
                        $r['name'] = $data['name'] ?? $sid;
                        $r['market'] = 'us';
                    } else {
                        $quotes = $twse->getMultiMonthQuotes($sid, 3);
                        if (empty($quotes)) continue;
                        $r = $engine->analyzeStock($sid, $quotes);
                        $info = $twse->getStockInfo($sid);
                        $r['name'] = $info['name'] ?? $sid;
                        $r['market'] = 'tw';
                    }
                    $results[] = $r;
                    usleep(500000);
                } catch (Exception $e) {
                    continue;
                }
            }

            if (!empty($results)) {
                $bot->sendDailySummary($results);
            } else {
                $bot->sendMessage("❌ 無法取得任何股票資料", $chatId);
            }
            break;
    }
} catch (Exception $e) {
    $chatId = end($parts) ?: '';
    if ($chatId) {
        $bot->sendMessage("❌ 發生錯誤：{$e->getMessage()}", $chatId);
    }
}

echo json_encode(['ok' => true]);
