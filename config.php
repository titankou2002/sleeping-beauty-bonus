<?php
// config.php - 睡美人戰情室設定
define('SS_ID_MAIN', getenv('SS_ID_MAIN') ?: '1G5q-GixMWSdJJeF8ZiXWMOfrx4FMobER25jNc8m4Zds');

define('SLEEPER_SHEET',   '睡美人');
define('TRIAL_SHEET',     '睡美人獎金試算');
define('SALES_SHEET',     '經銷銷售報表');
define('PRICE_SHEET',     '編號價目表');
define('STOCK_SHEET',     '庫存表');

define('SERVICE_ACCOUNT_FILE', __DIR__ . '/service-account.json');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
header('Content-Type: application/json; charset=utf-8');
