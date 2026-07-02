<?php
require_once __DIR__ . '/auth.php';
error_reporting(E_ALL);
ini_set('display_errors', is_file(__DIR__ . '/config.local.php') ? '1' : '0');
header('Content-Type: application/json; charset=utf-8');

set_error_handler(function ($severity, $msg, $file, $line) {
    throw new ErrorException($msg, 0, $severity, $file, $line);
});
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'msg' => '重大錯誤: ' . $e['message'], 'file' => $e['file'], 'line' => $e['line']]);
    }
});

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/GoogleSheetsClient.php';
require_once __DIR__ . '/classes/SleeperService.php';
// ====== ROUTER ======
try {
    $gs = new GoogleSheetsClient();
    $svc = new SleeperService($gs);

    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($action) {


        case 'config':
            $res = $svc->getSleeperConfig();
            echo json_encode($res);
            break;

        case 'sales':
            $year  = (int)($_GET['year'] ?? 2026);
            $month = (int)($_GET['month'] ?? 5);
            $res = $svc->getSleeperSalesByMonth($year, $month);
            echo json_encode($res);
            break;

        case 'sync':
            $year  = (int)($_POST['year'] ?? $_GET['year'] ?? 2026);
            $month = (int)($_POST['month'] ?? $_GET['month'] ?? 5);
            $res = $svc->syncTrialSheet($year, $month);
            echo json_encode($res);
            break;

        case 'recalc':
            $res = $svc->recalcTrialSheet();
            echo json_encode($res);
            break;

        case 'summary':
            $year  = isset($_GET['year'])  ? (int)$_GET['year']  : null;
            $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
            $res = $svc->getBonusSummary($year, $month);
            echo json_encode($res);
            break;

        case 'year-summary':
            try {
                $year  = (int)($_GET['year'] ?? date('Y'));
                $res = $svc->getYearSummary($year);
                echo json_encode($res);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => 'year-summary 錯誤: ' . $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            }
            break;

        case 'strategy-report':
            try {
                $year = (int)($_GET['year'] ?? date('Y'));
                $mode = trim($_GET['mode'] ?? 'month');
                $period = isset($_GET['period']) ? (int)$_GET['period'] : (int)($_GET['month'] ?? date('n'));
                $res = $svc->getStrategyReport($year, $period, $mode);
                echo json_encode($res);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => 'strategy-report 錯誤: ' . $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            }
            break;

        case 'meeting-report':
            try {
                $year = (int)($_GET['year'] ?? date('Y'));
                $month = (int)($_GET['month'] ?? date('n'));
                $res = $svc->getMeetingReport($year, $month);
                echo json_encode($res);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => 'meeting-report 錯誤: ' . $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            }
            break;

        case 'group-meeting-report':
            try {
                $year = (int)($_GET['year'] ?? date('Y'));
                $month = (int)($_GET['month'] ?? date('n'));
                $res = $svc->getGroupMeetingReport($year, $month);
                echo json_encode($res);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => 'group-meeting-report 錯誤: ' . $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            }
            break;

        case 'group-detailed-report':
            try {
                $year = (int)($_GET['year'] ?? date('Y'));
                $month = (int)($_GET['month'] ?? date('n'));
                $refresh = isset($_GET['refresh']) && $_GET['refresh'] == 1;

                if (!is_dir(AI_ADVISOR_CACHE_DIR)) {
                    @mkdir(AI_ADVISOR_CACHE_DIR, 0775, true);
                }
                $cacheFile = AI_ADVISOR_CACHE_DIR . "/group_report_{$year}_{$month}.json";

                if (!$refresh && is_file($cacheFile)) {
                    $cachedData = file_get_contents($cacheFile);
                    if ($cachedData) {
                        echo $cachedData;
                        break;
                    }
                }

                $res = $svc->getGroupDetailedReport($year, $month);
                $jsonData = json_encode($res, JSON_UNESCAPED_UNICODE);
                @file_put_contents($cacheFile, $jsonData);
                echo $jsonData;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => 'group-detailed-report 錯誤: ' . $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            }
            break;

        case 'get-mgr-report':
            try {
                $year = (int)($_GET['year'] ?? date('Y'));
                $month = (int)($_GET['month'] ?? date('n'));
                $res = $svc->getManagerReports($year, $month);
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => 'get-mgr-report 錯誤: ' . $e->getMessage()]);
            }
            break;

        case 'save-mgr-report':
            try {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    throw new Exception('僅接受 POST 請求');
                }
                $year = (int)($_POST['year'] ?? date('Y'));
                $month = (int)($_POST['month'] ?? date('n'));
                $coKey = $_POST['coKey'] ?? '';
                $mkt = $_POST['marketingPlan'] ?? '';
                $comm = $_POST['communication'] ?? '';
                $other = $_POST['otherReport'] ?? '';

                if ($coKey === '') {
                    throw new Exception('公司別不能為空');
                }

                $res = $svc->saveManagerReport($year, $month, $coKey, $mkt, $comm, $other);
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => 'save-mgr-report 錯誤: ' . $e->getMessage()]);
            }
            break;

        case 'report-history':
            try {
                $res = $svc->getReportHistory();
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => 'report-history 錯誤: ' . $e->getMessage()]);
            }
            break;

        case 'ai-advisor':
            try {
                $year = (int)($_GET['year'] ?? date('Y'));
                $month = (int)($_GET['month'] ?? date('n'));
                $refresh = ($_GET['refresh'] ?? '') === '1';
                $res = $svc->getAiAdvisor($year, $month, $refresh);
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => 'ai-advisor 錯誤: ' . $e->getMessage()]);
            }
            break;

        case 'customer-detail':
            $customer = $_GET['customer'] ?? '';
            $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
            $res = $svc->getCustomerDetail($customer, $year);
            echo json_encode($res);
            break;

        case 'products':
            $res = $svc->getSleeperProductOverview();
            echo json_encode($res);
            break;

        case 'discontinued-products':
            $res = $svc->getDiscontinuedProductOverview();
            echo json_encode($res);
            break;

        case 'normal-products':
            $res = $svc->getNormalProductOverview();
            echo json_encode($res);
            break;

        case 'customer-analysis':
            $res = $svc->getCustomerAnalysis();
            echo json_encode($res);
            break;

        case 'customer-timeline':
            $customer = $_GET['customer'] ?? '';
            $res = $svc->getCustomerTimeline($customer);
            echo json_encode($res);
            break;

        case 'customer-warroom':
            $customer = $_GET['customer'] ?? '';
            $res = $svc->getCustomerWarRoom($customer);
            echo json_encode($res);
            break;

        case 'customer-sales-breakdown':
            $customer = $_GET['customer'] ?? '';
            $res = $svc->getCustomerSalesBreakdown($customer);
            echo json_encode($res);
            break;

        case 'customer-ai-chat':
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $res = $svc->customerAiChat(
                $body['customer'] ?? '',
                $body['message'] ?? '',
                $body['history'] ?? [],
                $body['context'] ?? []
            );
            echo json_encode($res);
            break;

        case 'global-ai-chat':
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $res = $svc->globalAiChat(
                $body['message'] ?? '',
                $body['history'] ?? [],
                $body['tab'] ?? '',
                $body['context'] ?? []
            );
            echo json_encode($res);
            break;

        case 'product-lifecycle':
            $sku = $_GET['sku'] ?? '';
            $res = $svc->getProductLifecycle($sku);
            echo json_encode($res);
            break;

        case 'product-advisor':
            $tab = $_GET['tab'] ?? 'sleeper';
            $refresh = !empty($_GET['refresh']);
            $res = $svc->getProductRestockAdvisor($tab, $refresh);
            echo json_encode($res);
            break;

        case 'product-history':
            $tab = $_GET['tab'] ?? 'sleeper';
            $sku = $_GET['sku'] ?? '';
            $res = $svc->getProductHistory($tab, $sku);
            echo json_encode($res);
            break;

        case 'debug-sales-compare':
            try {
                $year = (int)($_GET['year'] ?? date('Y'));
                $month = (int)($_GET['month'] ?? date('n'));
                $salesRows = $gs->readSheet(SALES_SHEET);
                $h = $salesRows[0];
                $idxDate = $svc->findHeader($h, ['單據日期', '銷貨日期', '日期']);
                $idxAmt = $svc->findHeader($h, ['金額', '銷貨金額']);
                $idxCode = $svc->findHeader($h, ['產品編號', '編號', '品號']);
                $idxCust = $svc->findHeader($h, ['客戶名稱', '客戶']);
                $idxCustCode = $svc->findHeader($h, ['客戶編號', '客戶代碼', '代碼']);
                $idxNote = $svc->findHeader($h, ['產品備註', '備註', '說明']);
                $idxProdName = $svc->findHeader($h, ['產品名稱', '品名']);
                $rawTotal = 0; $rawCount = 0;
                $filteredTotal = 0; $filteredCount = 0;
                $sampleSkipped = 0; $sampleAmt = 0;
                $noDateCount = 0; $noCodeCount = 0; $zeroCount = 0;
                for ($i = 1; $i < count($salesRows); $i++) {
                    $row = $salesRows[$i];
                    $d = $svc->parseDate($row[$idxDate] ?? '');
                    if (!$d) { $noDateCount++; continue; }
                    $ry = (int)$d->format('Y'); $rm = (int)$d->format('n');
                    if ($ry !== $year || $rm !== $month) continue;
                    $amt = (float)str_replace(',', '', $row[$idxAmt] ?? '0');
                    $code = trim($row[$idxCode] ?? '');
                    $rawTotal += $amt; $rawCount++;
                    if ($code === '') { $noCodeCount++; continue; }
                    $qty = (float)str_replace(',', '', $row[$svc->findHeader($h, ['數量', '銷貨數量', '片數'])] ?? '0');
                    if ($qty == 0 && $amt == 0) { $zeroCount++; continue; }
                    $custName = trim($row[$idxCust] ?? '');
                    $custCode = trim($row[$idxCustCode] ?? '');
                    $prodName = trim($row[$idxProdName] ?? '');
                    $note = trim($row[$idxNote] ?? '');
                    if ($svc->isSampleRow($custCode, $custName, $prodName . ' ' . $note, $amt)) {
                        $sampleSkipped++; $sampleAmt += $amt; continue;
                    }
                    $filteredTotal += $amt; $filteredCount++;
                }
                $cacheRows = $gs->readSheet(CACHE_SHEET);
                $cH = $cacheRows[0];
                $cYear = $svc->findHeader($cH, ['年度']);
                $cMonth = $svc->findHeader($cH, ['月份']);
                $cAmt = $svc->findHeader($cH, ['銷售金額']);
                $cacheTotal = 0; $cacheCount = 0;
                for ($ci = 1; $ci < count($cacheRows); $ci++) {
                    $cr = $cacheRows[$ci];
                    if ((int)($cr[$cYear]??0) === $year && (int)($cr[$cMonth]??0) === $month) {
                        $cacheTotal += (float)str_replace(',', '', $cr[$cAmt] ?? '0');
                        $cacheCount++;
                    }
                }
                echo json_encode([
                    'year' => $year, 'month' => $month,
                    'rawSalesSheet' => ['total' => round($rawTotal), 'rows' => $rawCount],
                    'afterFilters' => ['total' => round($filteredTotal), 'rows' => $filteredCount],
                    'skipped' => ['noDate' => $noDateCount, 'noCode' => $noCodeCount, 'zero' => $zeroCount, 'sample' => $sampleSkipped, 'sampleAmt' => round($sampleAmt)],
                    'cache' => ['total' => round($cacheTotal), 'rows' => $cacheCount],
                    'diff' => round($filteredTotal - $cacheTotal),
                    'totalSalesRows' => count($salesRows) - 1
                ], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;

        case 'cache-info':
            try {
                $cacheRows = $gs->readSheet(CACHE_SHEET);
                $lastUpdate = '未知';
                if (count($cacheRows) > 1) {
                    $h = $cacheRows[0];
                    $updIdx = $svc->findHeader($h, ['更新時間']);
                    if ($updIdx !== -1) {
                        for ($ci = count($cacheRows) - 1; $ci >= 1; $ci--) {
                            $val = trim($cacheRows[$ci][$updIdx] ?? '');
                            if ($val !== '') { $lastUpdate = $val; break; }
                        }
                    }
                }
                echo json_encode(['success' => true, 'lastUpdate' => $lastUpdate, 'rows' => count($cacheRows) - 1]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
            }
            break;

        case 'rep-analysis':
            $res = $svc->getRepAnalysis();
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        case 'new-product-ai-chat':
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $sku = $body['sku'] ?? '';
            $product = $body['product'] ?? [];
            $message = $body['message'] ?? '請分析這款新品的銷售表現、上架覆蓋是否足夠、有無缺貨或流失風險，並給出具體建議。';
            if (empty($product)) {
                echo json_encode(['success' => false, 'msg' => '缺少產品資料']);
                break;
            }
            $res = $svc->newProductAiChat($sku, $product, $message);
            echo json_encode($res);
            break;

        case 'rebuild-cache':
            $years = $_POST['years'] ?? $_GET['years'] ?? null;
            if ($years && is_string($years)) {
                $years = array_map('intval', preg_split('/[,，\s]+/', $years));
            }
            $res = $svc->rebuildSalesYearCache($years);
            echo json_encode($res);
            break;

        case 'cron-rebuild-all':
            $token = $_GET['token'] ?? '';
            if ($token !== CRON_TOKEN) {
                http_response_code(403);
                echo json_encode(['success' => false, 'msg' => 'invalid token']);
                break;
            }
            $results = [];
            $companySheets = [
                '高雅瓷' => SS_ID_MAIN,
                '安帝嘉' => SS_ID_ANDYGA,
                '喜悅納' => SS_ID_XIYENA,
            ];
            foreach ($companySheets as $name => $ssId) {
                try {
                    $gsClient = new GoogleSheetsClient($ssId);
                    $companySvc = new SleeperService($gsClient);
                    $res = $companySvc->rebuildSalesYearCache();
                    $results[$name] = ['success' => true, 'msg' => $res['msg'] ?? 'OK'];
                } catch (Exception $e) {
                    $results[$name] = ['success' => false, 'msg' => $e->getMessage()];
                }
            }
            
            // Clear monthly report local JSON caches
            if (is_dir(AI_ADVISOR_CACHE_DIR)) {
                $cacheFiles = glob(AI_ADVISOR_CACHE_DIR . '/group_report_*.json');
                if ($cacheFiles) {
                    foreach ($cacheFiles as $f) {
                        @unlink($f);
                    }
                }
            }
            
            echo json_encode(['success' => true, 'results' => $results, 'time' => date('Y-m-d H:i:s')], JSON_UNESCAPED_UNICODE);
            break;

        case 'active-displays':
            $res = ['success' => true, 'data' => $svc->getActiveDisplaysMap()];
            echo json_encode($res);
            break;

        case 'trial-sheet':
            $year  = isset($_GET['year'])  ? (int)$_GET['year']  : null;
            $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
            $sales = $_GET['sales'] ?? '';
            $res = $svc->readTrialSheet($year, $month, $sales);
            echo json_encode($res);
            break;


        case 'update-row':
            $rowIdx    = (int)($_POST['rowIdx'] ?? 0);
            $qty       = (float)($_POST['qty'] ?? 0);
            $unitPrice = (float)($_POST['unitPrice'] ?? 0);
            $multiplier = (float)($_POST['multiplier'] ?? 0);
            $clearance = $_POST['clearance'] ?? '';
            $note      = $_POST['note'] ?? '';
            $res = $svc->updateTrialRow($rowIdx, $qty, $unitPrice, $multiplier, $clearance, $note);
            echo json_encode($res);
            break;

        case 'new-product-analysis':
            $cohortMonths = (int)($_GET['months'] ?? 24);
            $res = $svc->getNewProductAnalysis($cohortMonths);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        case 'send-daily-email':
            $token = $_GET['token'] ?? '';
            if ($token !== CRON_TOKEN) {
                http_response_code(403);
                echo json_encode(['success' => false, 'msg' => 'invalid token']);
                break;
            }
            $to = $_GET['to'] ?: DAILY_EMAIL_TO;
            $from = $_GET['from'] ?: DAILY_EMAIL_FROM;
            $res = $svc->sendDailyPerformanceReport($to, $from);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'msg' => '未知 action: ' . $action]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
