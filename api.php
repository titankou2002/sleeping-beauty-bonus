<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/GoogleSheetsClient.php';
require_once __DIR__ . '/src/SleeperService.php';

use App\GoogleSheetsClient;
use App\SleeperService;

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

        case 'products':
            $res = $svc->getSleeperProductOverview();
            echo json_encode($res);
            break;

        case 'read-trial':
            $year  = isset($_GET['year'])  ? (int)$_GET['year']  : null;
            $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
            $sales = $_GET['sales'] ?? '';
            $res = $svc->readTrialSheet($year, $month, $sales);
            echo json_encode($res);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'msg' => '未知 action: ' . $action]);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
