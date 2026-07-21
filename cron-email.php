<?php
// Standalone cron email sender - no auth.php dependency
if (($_GET['token'] ?? '') !== 'sb-cron-2026-rebuild') {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'forbidden']);
    exit;
}
date_default_timezone_set('Asia/Taipei');
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/GoogleSheetsClient.php';
require_once __DIR__ . '/classes/traits/DataTrait.php';
require_once __DIR__ . '/classes/traits/BonusTrait.php';
require_once __DIR__ . '/classes/traits/CustomerTrait.php';
require_once __DIR__ . '/classes/traits/ProductTrait.php';
require_once __DIR__ . '/classes/traits/ReportTrait.php';
require_once __DIR__ . '/classes/traits/ProjectTrait.php';
require_once __DIR__ . '/classes/traits/AiTrait.php';
require_once __DIR__ . '/classes/traits/RepTrait.php';
require_once __DIR__ . '/classes/traits/MailTrait.php';
require_once __DIR__ . '/classes/SleeperService.php';

try {
    $gs = new GoogleSheetsClient();
    $svc = new SleeperService($gs);
    $to   = $_GET['to']   ?? DAILY_EMAIL_TO;
    $from = $_GET['from'] ?? DAILY_EMAIL_FROM;
    $type = $_GET['type'] ?? 'group';
    $res = $svc->sendDailyPerformanceReport($to, $from, $type);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage() . ' at ' . basename($e->getFile()) . ':' . $e->getLine()]);
}
