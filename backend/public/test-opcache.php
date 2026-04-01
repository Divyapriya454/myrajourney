<?php
header('Content-Type: application/json');

$opcacheStatus = function_exists('opcache_get_status') ? opcache_get_status() : null;

echo json_encode([
    'opcache_enabled' => $opcacheStatus !== null && $opcacheStatus !== false,
    'opcache_status' => $opcacheStatus,
    'php_version' => phpversion(),
    'loaded_extensions' => get_loaded_extensions()
]);
