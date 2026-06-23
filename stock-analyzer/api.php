<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

set_error_handler(function ($severity, $msg, $file, $line) {
    throw new ErrorException($msg, 0, $severity, $file, $line);
});

try {

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/TwseClient.php';
require_once __DIR__ . '/classes/UsStockClient.php';
require_once __DIR__ . '/classes/TechnicalAnalysis.php';
require_once __DIR__ . '/classes/SignalEngine.php';
require_once __DIR__ . '/classes/TelegramBot.php';
require_once __DIR__ . '/classes/PortfolioManager.php';

$twse = new TwseClient();
$us = new UsStockClient();
$engine = new SignalEngine();
$portfolio = new PortfolioManager();

$action = $_GET['action'] ?? '';

switch ($action) {

        case 'analyze':
            $stockId = trim($_GET['stock'] ?? '');
            $market = trim($_GET['market'] ?? 'tw');
            if (!$stockId) throw new RuntimeException('缺少 stock 參數');

            if ($market === 'us') {
                $data = $us->getQuote($stockId);
                if (empty($data['quotes'])) throw new RuntimeException("查無美股 {$stockId} 資料");
                $quotes = $data['quotes'];
                $result = $engine->analyzeStock($stockId, $quotes);
                $result['name'] = $data['name'] ?? $stockId;
                $result['market'] = 'us';
                $result['quotes'] = array_values(array_slice($quotes, -60));
            } else {
                $quotes = $twse->getMultiMonthQuotes($stockId, 6);
                if (empty($quotes)) throw new RuntimeException("查無台股 {$stockId} 資料");
                $peData = $twse->getMultiMonthPE($stockId, 6);
                $instData = [];
                $marginData = $twse->getMarginTrading($stockId);
                $result = $engine->analyzeStock($stockId, $quotes, $peData, $instData, $marginData);

                $info = $twse->getStockInfo($stockId);
                $result['name'] = $info['name'] ?? $stockId;
                $result['market'] = 'tw';
                $result['quotes'] = array_values(array_map(function($q) {
                    return [
                        'date' => $q['date'],
                        'open' => $q['open'],
                        'high' => $q['high'],
                        'low' => $q['low'],
                        'close' => $q['close'],
                        'volume' => $q['volume'],
                    ];
                }, array_slice($quotes, -60)));
                $result['peHistory'] = $peData;
            }

            echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
            break;

        case 'analyze-full':
            $stockId = trim($_GET['stock'] ?? '');
            if (!$stockId) throw new RuntimeException('缺少 stock 參數');

            $quotes = $twse->getMultiMonthQuotes($stockId, 6);
            if (empty($quotes)) throw new RuntimeException("查無台股 {$stockId} 資料");

            $peData = $twse->getPERatio($stockId);
            $instData = $twse->getInstitutional30Days($stockId);
            $marginData = $twse->getMarginTrading($stockId);
            $result = $engine->analyzeStock($stockId, $quotes, $peData, $instData, $marginData);

            $info = $twse->getStockInfo($stockId);
            $result['name'] = $info['name'] ?? $stockId;
            $result['market'] = 'tw';

            echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
            break;

        case 'quotes':
            $stockId = trim($_GET['stock'] ?? '');
            $market = trim($_GET['market'] ?? 'tw');
            $months = (int)($_GET['months'] ?? 6);
            if (!$stockId) throw new RuntimeException('缺少 stock 參數');

            if ($market === 'us') {
                $data = $us->getQuote($stockId);
                echo json_encode(['success' => true, 'data' => $data['quotes'] ?? []], JSON_UNESCAPED_UNICODE);
            } else {
                $quotes = $twse->getMultiMonthQuotes($stockId, $months);
                echo json_encode(['success' => true, 'data' => $quotes], JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'institutional':
            $stockId = trim($_GET['stock'] ?? '');
            if (!$stockId) throw new RuntimeException('缺少 stock 參數');
            $data = $twse->getInstitutional30Days($stockId);
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            break;

        case 'pe':
            $stockId = trim($_GET['stock'] ?? '');
            if (!$stockId) throw new RuntimeException('缺少 stock 參數');
            $data = $twse->getPERatio($stockId);
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            break;

        case 'search':
            $keyword = trim($_GET['q'] ?? '');
            if (mb_strlen($keyword) < 1) throw new RuntimeException('搜尋關鍵字太短');
            $results = $twse->searchStocks($keyword, 10);
            echo json_encode(['success' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);
            break;

        case 'watchlist':
            $list = $portfolio->getWatchlist();
            echo json_encode(['success' => true, 'data' => array_values($list)], JSON_UNESCAPED_UNICODE);
            break;

        case 'watchlist-add':
            $stockId = trim($_POST['stock'] ?? $_GET['stock'] ?? '');
            $market = trim($_POST['market'] ?? $_GET['market'] ?? 'tw');
            $entry = (float)($_POST['entryPrice'] ?? $_GET['entryPrice'] ?? 0);
            if (!$stockId) throw new RuntimeException('缺少 stock 參數');
            $item = $portfolio->addToWatchlist($stockId, $market, $entry);
            echo json_encode(['success' => true, 'data' => $item], JSON_UNESCAPED_UNICODE);
            break;

        case 'watchlist-remove':
            $stockId = trim($_POST['stock'] ?? $_GET['stock'] ?? '');
            if (!$stockId) throw new RuntimeException('缺少 stock 參數');
            $portfolio->removeFromWatchlist($stockId);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        case 'scan-all':
            $list = $portfolio->getWatchlist();
            if (empty($list)) {
                $defaults = explode(',', DEFAULT_TW_WATCHLIST);
                foreach ($defaults as $s) $portfolio->addToWatchlist(trim($s), 'tw');
                $list = $portfolio->getWatchlist();
            }

            $results = [];
            foreach ($list as $item) {
                try {
                    $stockId = $item['stockId'];
                    $market = $item['market'] ?? 'tw';
                    if ($market === 'us') {
                        $data = $us->getQuote($stockId);
                        if (empty($data['quotes'])) continue;
                        $r = $engine->analyzeStock($stockId, $data['quotes']);
                        $r['name'] = $data['name'] ?? $stockId;
                        $r['market'] = 'us';
                    } else {
                        $quotes = $twse->getMultiMonthQuotes($stockId, 3);
                        if (empty($quotes)) continue;
                        $r = $engine->analyzeStock($stockId, $quotes);
                        $info = $twse->getStockInfo($stockId);
                        $r['name'] = $info['name'] ?? $stockId;
                        $r['market'] = 'tw';
                    }
                    $r['entryPrice'] = $item['entryPrice'] ?? 0;
                    $results[] = $r;
                    usleep(500000);
                } catch (Exception $e) {
                    continue;
                }
            }

            echo json_encode(['success' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);
            break;

        case 'settings':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $body = json_decode(file_get_contents('php://input'), true) ?: [];
                $portfolio->updateSettings($body);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => true, 'data' => $portfolio->getSettings()]);
            }
            break;

        case 'alert-history':
            echo json_encode(['success' => true, 'data' => $portfolio->getAlertHistory()], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'msg' => '未知 action: ' . $action]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
