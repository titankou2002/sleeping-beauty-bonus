<?php
// v2
if (($_GET['token'] ?? '') !== 'be4109677a907a4e5fab34f9bf302fb2') {
    http_response_code(403);
    exit('Forbidden');
}
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo 'OPcache cleared. ' . date('Y-m-d H:i:s');
} else {
    echo 'OPcache not available. ' . date('Y-m-d H:i:s');
}
