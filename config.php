<?php
define('SS_ID_MAIN', getenv('SS_ID_MAIN') ?: '1G5q-GixMWSdJJeF8ZiXWMOfrx4FMobER25jNc8m4Zds');

define('SLEEPER_SHEET',   '睡美人');
define('TRIAL_SHEET',     '睡美人獎金試算');
define('SALES_SHEET',     '經銷銷售報表');
define('PRICE_SHEET',     '編號價目表');
define('STOCK_SHEET',     '庫存表');
define('CACHE_SHEET',     '產品年度銷售快取');

define('SS_ID_LAYOUT',    '1zTTl3IjrwZvYdxvX3UZk6YLVaGF7m_DBhb_tWM2LjW0');
define('LAYOUT_SHEET',    '版面上架清單');

// 集團分公司試算表 ID 與月報網址
define('SS_ID_ANDYGA', getenv('SS_ID_ANDYGA') ?: '16QNID9hLs2K1iy_ePo7MxYxhW4kpDrDlfEIZ2p83ixo');
define('SS_ID_XIYENA', getenv('SS_ID_XIYENA') ?: '1uFKKWBfulg-GmCbJsSomimT5LW5r0N2w28rubrPveTA');
define('URL_ANDYGA_REPORT', getenv('URL_ANDYGA_REPORT') ?: 'https://script.google.com/macros/s/AKfycbwodrFnR5aDcIfJhUIwyCeWeUsrG2m-2iUX4y0-SZaZ/exec?page=meeting');
define('URL_XIYENA_REPORT', getenv('URL_XIYENA_REPORT') ?: 'https://script.google.com/macros/s/AKfycbxc6s-6sjq3517Bnim0E_jl6AwfeD3rlZv7JrvQg2c5h0mHSPZVURlFT5_q39s-lBD5/exec?page=meeting');

define('SERVICE_ACCOUNT_FILE', __DIR__ . '/service-account.json');
define('CRON_TOKEN', getenv('CRON_TOKEN') ?: 'sb-cron-2026-rebuild');

if (is_file(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
if (!defined('GEMINI_API_KEY')) define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_MODEL', getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash');
define('AI_ADVISOR_CACHE_DIR', __DIR__ . '/cache');

// 每日業績郵件設定
define('DAILY_EMAIL_TO', getenv('DAILY_EMAIL_TO') ?: 'titankou2002@gmail.com,sb780910@gmail.com,ghosts0125@gmail.com,panus081231@gmail.com,lynnloveshumi@gmail.com');
define('DAILY_EMAIL_FROM', getenv('DAILY_EMAIL_FROM') ?: '高雅瓷戰情室 <noreply@gaoyaci.local>');

// 戰情室存取密碼
define('ACCESS_PASSWORD', '9593');

