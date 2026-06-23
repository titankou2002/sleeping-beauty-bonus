<?php
if (is_file(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

if (!defined('APP_NAME')) define('APP_NAME', '股票戰情室');
if (!defined('DATA_DIR')) define('DATA_DIR', __DIR__ . '/data');
if (!defined('CACHE_DIR')) define('CACHE_DIR', __DIR__ . '/cache');

if (!defined('TELEGRAM_BOT_TOKEN')) define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: '');
if (!defined('TELEGRAM_CHAT_ID')) define('TELEGRAM_CHAT_ID', getenv('TELEGRAM_CHAT_ID') ?: '');

if (!defined('LINE_CHANNEL_TOKEN')) define('LINE_CHANNEL_TOKEN', getenv('LINE_CHANNEL_TOKEN') ?: '');
if (!defined('LINE_USER_ID')) define('LINE_USER_ID', getenv('LINE_USER_ID') ?: '');

if (!defined('SMTP_HOST')) define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
if (!defined('SMTP_PORT')) define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));
if (!defined('SMTP_USER')) define('SMTP_USER', getenv('SMTP_USER') ?: '');
if (!defined('SMTP_PASS')) define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
if (!defined('ALERT_EMAIL')) define('ALERT_EMAIL', getenv('ALERT_EMAIL') ?: '');

if (!defined('GEMINI_API_KEY')) define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
if (!defined('GEMINI_MODEL')) define('GEMINI_MODEL', getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash');

if (!defined('FINMIND_API_TOKEN')) define('FINMIND_API_TOKEN', getenv('FINMIND_API_TOKEN') ?: '');

if (!defined('DEFAULT_TW_WATCHLIST')) define('DEFAULT_TW_WATCHLIST', '2330,2317,2382,2454,2308,0050,00919');
if (!defined('DEFAULT_US_WATCHLIST')) define('DEFAULT_US_WATCHLIST', 'AAPL,NVDA,TSLA,MSFT,GOOGL');

if (!defined('STOP_LOSS_PCT')) define('STOP_LOSS_PCT', -6.5);
if (!defined('RSI_OVERSOLD')) define('RSI_OVERSOLD', 30);
if (!defined('RSI_OVERBOUGHT')) define('RSI_OVERBOUGHT', 70);
if (!defined('MIN_VOLUME_RATIO')) define('MIN_VOLUME_RATIO', 1.3);

foreach ([DATA_DIR, CACHE_DIR] as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
}
