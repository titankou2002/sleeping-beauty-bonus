<?php
// 部署探針 — 驗證 FTP 上傳是否到達網站實際讀取的目錄
header('Content-Type: text/plain; charset=utf-8');
echo "PROBE_STAMP=1784686240\n";
echo "FILE_MTIME=" . date('Y-m-d H:i:s', filemtime(__FILE__)) . "\n";
echo "FILE_PATH=" . __FILE__ . "\n";
echo "GM_MTIME=" . (file_exists(__DIR__.'/group_meeting.php') ? date('Y-m-d H:i:s', filemtime(__DIR__.'/group_meeting.php')) : 'MISSING') . "\n";
echo "GM_HAS_DAILY_BTN=" . (strpos(file_get_contents(__DIR__.'/group_meeting.php'), '每日戰報') !== false ? 'YES' : 'NO') . "\n";
