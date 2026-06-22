<?php
define('APP_NAME', '股票戰情室');
define('DATA_DIR', __DIR__ . '/data');
define('CACHE_DIR', __DIR__ . '/cache');

// Telegram Bot
define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: '');
define('TELEGRAM_CHAT_ID', getenv('TELEGRAM_CHAT_ID') ?: '');

// Email (SMTP)
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('ALERT_EMAIL', getenv('ALERT_EMAIL') ?: '');

// AI (Gemini)
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_MODEL', getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash');

// FinMind API (optional, for richer TW data)
define('FINMIND_API_TOKEN', getenv('FINMIND_API_TOKEN') ?: '');

// Watchlist default
define('DEFAULT_TW_WATCHLIST', '2330,2317,2382,2454,2308,0050,00919');
define('DEFAULT_US_WATCHLIST', 'AAPL,NVDA,TSLA,MSFT,GOOGL');

// Signal thresholds
define('STOP_LOSS_PCT', -6.5);
define('RSI_OVERSOLD', 30);
define('RSI_OVERBOUGHT', 70);
define('MIN_VOLUME_RATIO', 1.3);

if (is_file(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

foreach ([DATA_DIR, CACHE_DIR] as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
}
