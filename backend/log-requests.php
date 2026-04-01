<?php

// Simple request logger to debug the missed dose API
// Add this to the top of MissedDoseController.php temporarily

$logFile = __DIR__ . '/missed_dose_requests.log';

$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'headers' => getallheaders(),
    'raw_input' => file_get_contents('php://input'),
    'auth_header' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'missing',
    'server_auth' => $_SERVER['auth'] ?? 'not_set'
];

file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

echo "Request logged to: $logFile" . PHP_EOL;
