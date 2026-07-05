<?php
if (($_GET['token'] ?? '') !== 'be4109677a907a4e5fab34f9bf302fb2') {
    http_response_code(403);
    exit('Forbidden');
}
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo 'OPcache cleared.';
} else {
    echo 'OPcache not available.';
}
