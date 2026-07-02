<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

// Bypass session check for automated sync cron job with valid token
$isCron = isset($_GET['action']) && $_GET['action'] === 'cron-rebuild-all' && isset($_GET['token']) && $_GET['token'] === CRON_TOKEN;

// Backdoor: allow cron actions with valid token
if (!isset($_SESSION['war_room_auth']) && isset($_GET['token']) && $_GET['token'] === CRON_TOKEN) {
    if (in_array($_GET['action'] ?? '', ['sync', 'new-product-analysis', 'rep-analysis', 'strategy-report'])) {
        $_SESSION['war_room_auth'] = true;
    }
}

// Check if user is authenticated
$authenticated = $isCron || (isset($_SESSION['war_room_auth']) && $_SESSION['war_room_auth'] === true);

// Immediately release session write lock after read, avoiding concurrent AJAX request queueing
session_write_close();

if (!$authenticated) {
    // Check if it is an AJAX API call
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
              || (isset($_GET['action'])) 
              || (strpos($_SERVER['REQUEST_URI'] ?? '', 'api.php') !== false);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'unauthorized' => true, 
            'msg' => '登入逾時，請重新登入戰情室。'
        ]);
        exit;
    } else {
        header('Location: login.php');
        exit;
    }
}
