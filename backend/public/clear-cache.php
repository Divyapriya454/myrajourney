<?php
header('Content-Type: application/json');

$result = [];

// Clear OPcache
if (function_exists('opcache_reset')) {
    $cleared = opcache_reset();
    $result['opcache'] = $cleared ? 'cleared' : 'failed';
    $result['opcache_enabled'] = true;
} else {
    $result['opcache'] = 'not enabled';
    $result['opcache_enabled'] = false;
}

// Clear realpath cache
clearstatcache(true);
$result['realpath_cache'] = 'cleared';

// Get OPcache status
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    $result['opcache_status'] = [
        'enabled' => $status['opcache_enabled'] ?? false,
        'cache_full' => $status['cache_full'] ?? false,
        'restart_pending' => $status['restart_pending'] ?? false,
    ];
}

$result['timestamp'] = date('Y-m-d H:i:s');
$result['message'] = 'Cache cleared successfully';

echo json_encode($result, JSON_PRETTY_PRINT);
