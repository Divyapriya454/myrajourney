<?php

// Add this at the very beginning of MissedDoseController.php to log everything

$logFile = __DIR__ . '/request_debug.log';

$debugData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'headers' => function_exists('getallheaders') ? getallheaders() : $_SERVER,
    'raw_input' => file_get_contents('php://input'),
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not_set',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not_set',
    'auth_header' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'missing',
    'server_auth' => $_SERVER['auth'] ?? 'not_set',
    'php_input_size' => strlen(file_get_contents('php://input')),
    'json_decode_test' => json_decode(file_get_contents('php://input'), true),
    'json_last_error' => json_last_error_msg()
];

file_put_contents($logFile, "=== REQUEST DEBUG ===" . PHP_EOL);
file_put_contents($logFile, json_encode($debugData, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL, FILE_APPEND);

echo "Debug logged to: $logFile" . PHP_EOL;
