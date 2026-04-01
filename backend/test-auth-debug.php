<?php
// Debug script to see what headers are available
header('Content-Type: application/json');

$debug = [
    'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'NOT SET',
    'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'NOT SET',
    'apache_request_headers' => function_exists('apache_request_headers') ? apache_request_headers() : 'NOT AVAILABLE',
    'all_headers' => getallheaders() ?: 'NOT AVAILABLE',
    'all_server_vars' => array_filter($_SERVER, function($key) {
        return stripos($key, 'HTTP') !== false || stripos($key, 'AUTH') !== false;
    }, ARRAY_FILTER_USE_KEY)
];

echo json_encode($debug, JSON_PRETTY_PRINT);

