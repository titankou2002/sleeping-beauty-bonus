<?php
/**
 * Cron job entry point - run daily for stock scanning and alerts.
 * Usage: php cron.php [daily|scan]
 * Recommended crontab: 0 18 * * 1-5 php /path/to/cron.php daily
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

set_error_handler(function ($severity, $msg, $file, $line) {
    throw new ErrorException($msg, 0, $severity, $file, $line);
});

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/TwseClient.php';
require_once __DIR__ . '/classes/UsStockClient.php';
require_once __DIR__ . '/classes/TechnicalAnalysis.php';
require_once __DIR__ . '/classes/SignalEngine.php';
require_once __DIR__ . '/classes/TelegramBot.php';
require_once __DIR__ . '/classes/LineBot.php';
require_once __DIR__ . '/classes/PortfolioManager.php';

$mode = $argv[1] ?? 'daily';
$twse = new TwseClient();
$us = new UsStockClient();
$engine = new SignalEngine();
$portfolio = new PortfolioManager();
$bot = new TelegramBot();
$line = new LineBot();

$list = $portfolio->getWatchlist();
if (empty($list)) {
    $defaults = explode(',', DEFAULT_TW_WATCHLIST);
    foreach ($defaults as $s) {
        $portfolio->addToWatchlist(trim($s), 'tw');
    }
    $list = $portfolio->getWatchlist();
}

echo "[" . date('Y-m-d H:i:s') . "] Starting {$mode} scan for " . count($list) . " stocks\n";

$results = [];
$alerts = [];

foreach ($list as $item) {
    $stockId = $item['stockId'];
    $market = $item['market'] ?? 'tw';

    try {
        if ($market === 'us') {
            $data = $us->getQuote($stockId);
            if (empty($data['quotes'])) {
                echo "  SKIP {$stockId}: no US data\n";
                continue;
            }
            $r = $engine->analyzeStock($stockId, $data['quotes']);
            $r['name'] = $data['name'] ?? $stockId;
            $r['market'] = 'us';
        } else {
            $quotes = $twse->getMultiMonthQuotes($stockId, 6);
            if (empty($quotes)) {
                echo "  SKIP {$stockId}: no TW data\n";
                continue;
            }
            $peData = $twse->getPERatio($stockId);
            $instData = $twse->getInstitutional30Days($stockId);
            $marginData = $twse->getMarginTrading($stockId);
            $r = $engine->analyzeStock($stockId, $quotes, $peData, $instData, $marginData);
            $info = $twse->getStockInfo($stockId);
            $r['name'] = $info['name'] ?? $stockId;
            $r['market'] = 'tw';
        }

        $r['entryPrice'] = $item['entryPrice'] ?? 0;
        $results[] = $r;

        $rec = $r['recommendation'] ?? [];
        $score = $rec['score'] ?? 50;

        if ($score >= 70 || $score <= 30) {
            $alerts[] = $r;
            $type = $score >= 70 ? 'bullish' : 'bearish';
            $portfolio->logAlert($stockId, $type, "{$rec['action']} (score: {$score})");
            echo "  ALERT {$stockId}: {$rec['action']} score={$score}\n";
        }

        // Stop-loss check
        if ($r['entryPrice'] > 0) {
            $entryPrice = $r['entryPrice'];
            $currentPrice = $r['price'];
            $drawdown = ($currentPrice - $entryPrice) / $entryPrice * 100;
            if ($drawdown <= STOP_LOSS_PCT) {
                $alerts[] = array_merge($r, ['stopLossTriggered' => true]);
                $portfolio->logAlert($stockId, 'stop_loss', "Stop-loss triggered: {$drawdown}%");
                echo "  STOP-LOSS {$stockId}: entry={$entryPrice} current={$currentPrice} drawdown={$drawdown}%\n";
            }
        }

        echo "  OK {$stockId}: score={$score} price={$r['price']}\n";
        usleep(600000);
    } catch (Exception $e) {
        echo "  ERROR {$stockId}: {$e->getMessage()}\n";
        continue;
    }
}

// Send alerts via Telegram + LINE
if (!empty($alerts)) {
    echo "\nSending " . count($alerts) . " individual alerts...\n";
    foreach ($alerts as $a) {
        try {
            if (!empty($a['stopLossTriggered'])) {
                $msg = "🚨 停損警報\n";
                $msg .= "{$a['stockId']} {$a['name']}\n";
                $msg .= "進場價: \${$a['entryPrice']} → 現價: \${$a['price']}\n";
                $drawdown = round(($a['price'] - $a['entryPrice']) / $a['entryPrice'] * 100, 2);
                $msg .= "跌幅: {$drawdown}%\n";
                $msg .= "建議立即檢視是否停損出場";

                $bot->sendMessage("🚨 <b>停損警報</b>\n<b>{$a['stockId']}</b> {$a['name']}\n進場價: \${$a['entryPrice']} → 現價: \${$a['price']}\n跌幅: {$drawdown}%\n建議立即檢視是否停損出場");
                $line->sendMessage($msg);
            } else {
                $bot->sendStockAlert($a);
                $line->sendStockAlert($a);
            }
            usleep(500000);
        } catch (Exception $e) {
            echo "  Alert send error: {$e->getMessage()}\n";
        }
    }
}

// Send daily summary
if ($mode === 'daily' && !empty($results)) {
    echo "\nSending daily summary...\n";
    try {
        $bot->sendDailySummary($results);
        echo "  Telegram summary sent.\n";
    } catch (Exception $e) {
        echo "  Telegram error: {$e->getMessage()}\n";
    }
    try {
        $line->sendDailySummary($results);
        echo "  LINE summary sent.\n";
    } catch (Exception $e) {
        echo "  LINE error: {$e->getMessage()}\n";
    }

    // Send email summary if configured
    if (ALERT_EMAIL) {
        echo "Sending email summary to " . ALERT_EMAIL . "...\n";
        try {
            sendEmailSummary($results, $alerts);
            echo "  Email sent.\n";
        } catch (Exception $e) {
            echo "  Email error: {$e->getMessage()}\n";
        }
    }
}

echo "\n[" . date('Y-m-d H:i:s') . "] Done. {$mode} scan complete.\n";

function sendEmailSummary(array $results, array $alerts): void
{
    $subject = '股票戰情室 - 每日摘要 ' . date('Y/m/d');

    $body = "<html><body style='font-family:sans-serif;background:#1a1a2e;color:#e0e0e0;padding:20px'>";
    $body .= "<h2 style='color:#d4a843'>📈 每日股票摘要</h2>";
    $body .= "<p>" . date('Y/m/d H:i') . "</p>";

    if (!empty($alerts)) {
        $body .= "<h3 style='color:#ff6b6b'>⚠️ 警報 (" . count($alerts) . ")</h3>";
        foreach ($alerts as $a) {
            $rec = $a['recommendation'] ?? [];
            $body .= "<p><b>{$a['stockId']}</b> {$a['name'] ?? ''} - {$rec['action'] ?? ''} (Score: {$rec['score'] ?? 0})</p>";
        }
    }

    $body .= "<h3>全部監控</h3><table style='border-collapse:collapse;width:100%'>";
    $body .= "<tr style='color:#d4a843'><th>代號</th><th>價格</th><th>漲跌%</th><th>建議</th><th>分數</th></tr>";
    foreach ($results as $r) {
        $rec = $r['recommendation'] ?? [];
        $color = ($r['changePct'] ?? 0) >= 0 ? '#4ecdc4' : '#ff6b6b';
        $body .= "<tr>";
        $body .= "<td>{$r['stockId']}</td>";
        $body .= "<td>{$r['price']}</td>";
        $body .= "<td style='color:{$color}'>{$r['changePct']}%</td>";
        $body .= "<td>{$rec['action'] ?? ''}</td>";
        $body .= "<td>{$rec['score'] ?? 0}</td>";
        $body .= "</tr>";
    }
    $body .= "</table></body></html>";

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: Stock Analyzer <noreply@stock-analyzer.local>',
        'To: ' . ALERT_EMAIL,
    ];

    mail(ALERT_EMAIL, $subject, $body, implode("\r\n", $headers));
}
