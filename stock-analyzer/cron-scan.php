<?php
/**
 * Background market scanner.
 * Run via cron or HTTP to scan all stocks for patterns and signals.
 *
 * Usage:
 *   php cron-scan.php
 *   or via HTTP: curl http://yourserver/stock-analyzer/cron-scan.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

set_error_handler(function ($severity, $msg, $file, $line) {
    throw new ErrorException($msg, 0, $severity, $file, $line);
});

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/TwseClient.php';
require_once __DIR__ . '/classes/UsStockClient.php';
require_once __DIR__ . '/classes/TechnicalAnalysis.php';
require_once __DIR__ . '/classes/SignalEngine.php';
require_once __DIR__ . '/classes/PatternEngine.php';
require_once __DIR__ . '/classes/TelegramBot.php';
require_once __DIR__ . '/classes/PortfolioManager.php';

$progressFile = CACHE_DIR . '/scan_progress.json';
$resultFile = CACHE_DIR . '/market_scan.json';

function writeProgress($file, $total, $done, $running, $extra = [])
{
    $data = [
        'total' => $total,
        'done' => $done,
        'running' => $running,
        'lastUpdate' => date('Y-m-d H:i:s'),
    ];
    if (!empty($extra)) {
        foreach ($extra as $k => $v) {
            $data[$k] = $v;
        }
    }
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
}

try {
    $twse = new TwseClient();
    $engine = new SignalEngine();
    $pe = new PatternEngine();

    // Get all listed stocks
    $allStocks = $twse->getAllStocks();
    if (empty($allStocks)) {
        $output = ['success' => false, 'msg' => '無法取得股票清單'];
        echo json_encode($output, JSON_UNESCAPED_UNICODE);
        exit(1);
    }

    // Filter to 4-digit numeric codes only (skip ETFs with letters)
    $stocks = [];
    foreach ($allStocks as $stock) {
        $code = $stock['code'] ?? '';
        if (preg_match('/^\d{4}$/', $code)) {
            $stocks[] = $stock;
        }
    }

    $total = count($stocks);
    $done = 0;

    // Mark scan as running
    writeProgress($progressFile, $total, 0, true);

    $results = [];

    foreach ($stocks as $stock) {
        $stockId = $stock['code'] ?? '';
        $stockName = $stock['name'] ?? $stockId;

        try {
            // Fetch 3 months of quotes
            $quotes = $twse->getMultiMonthQuotes($stockId, 3);
            if (empty($quotes) || count($quotes) < 10) {
                $done++;
                writeProgress($progressFile, $total, $done, true);
                usleep(500000);
                continue;
            }

            // Run pattern detection
            $patternResults = $pe->detectPatterns($quotes);
            $patternCodes = [];
            foreach ($patternResults as $p) {
                $patternCodes[] = $p['code'];
            }
            $patternCodes = array_unique($patternCodes);

            // Run signal analysis
            $analysis = $engine->analyzeStock($stockId, $quotes);

            $lastQuote = end($quotes);
            $price = (float)($lastQuote['close'] ?? 0);
            $prevClose = (count($quotes) >= 2) ? (float)($quotes[count($quotes) - 2]['close'] ?? 0) : $price;
            $changePct = ($prevClose > 0) ? round(($price - $prevClose) / $prevClose * 100, 2) : 0;

            $recScore = 0;
            if (isset($analysis['recommendation']['score'])) {
                $recScore = $analysis['recommendation']['score'];
            } elseif (isset($analysis['recommendation'])) {
                $rec = $analysis['recommendation'];
                if (is_numeric($rec)) {
                    $recScore = (int)$rec;
                }
            }

            $signals = [];
            if (!empty($analysis['technical']['signals'])) {
                $signals = $analysis['technical']['signals'];
            }

            $results[] = [
                'stockId' => $stockId,
                'name' => $stockName,
                'patterns' => array_values($patternCodes),
                'patternDetails' => $patternResults,
                'recommendation' => $recScore,
                'price' => $price,
                'changePct' => $changePct,
                'signals' => $signals,
            ];

        } catch (Exception $e) {
            // Skip failed stocks silently
        }

        $done++;
        writeProgress($progressFile, $total, $done, true);

        // Rate limit: 500ms between stocks
        usleep(500000);
    }

    // Save results
    $scanData = [
        'scanTime' => date('Y-m-d H:i:s'),
        'total' => count($results),
        'stocks' => $results,
    ];
    file_put_contents($resultFile, json_encode($scanData, JSON_UNESCAPED_UNICODE));

    // Mark scan as finished
    writeProgress($progressFile, $total, $done, false);

    // Output status
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => true,
        'msg' => '掃描完成',
        'total' => count($results),
        'scanTime' => $scanData['scanTime'],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    // Mark scan as stopped on error
    writeProgress($progressFile, 0, 0, false, ['error' => $e->getMessage()]);

    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'msg' => '掃描錯誤: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit(1);
}
