<?php

echo "=== Testing Real API Endpoint ===" . PHP_EOL . PHP_EOL;

// Set JWT_SECRET for token generation
$_ENV['JWT_SECRET'] = 'myrajourney_secret_key_2024';

// Create a valid JWT token
$payload = [
    'uid' => 1,
    'role' => 'PATIENT',
    'email' => 'test@example.com',
    'exp' => time() + 3600
];

// Simple JWT encoding (without requiring the full bootstrap)
function simpleJwtEncode($payload, $secret) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode($payload);
    
    $headerEncoded = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    $payloadEncoded = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    
    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);
    $signatureEncoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
}

$token = simpleJwtEncode($payload, $_ENV['JWT_SECRET']);

echo "Generated JWT Token: " . substr($token, 0, 50) . "..." . PHP_EOL . PHP_EOL;

// Test data
$testData = [
    'patient_medication_id' => '1',
    'medication_name' => 'Methotrexate Real Endpoint Test',
    'scheduled_time' => '2024-12-16 10:00:00',
    'missed_time' => '2024-12-16 12:30:00',
    'reason' => 'forgot',
    'notes' => 'Real endpoint test via HTTP'
];

echo "Test Data:" . PHP_EOL;
echo json_encode($testData, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

// Make HTTP request to the API
$url = 'http://localhost:8000/api/v1/missed-dose-reports';

$options = [
    'http' => [
        'header' => [
            "Content-Type: application/json",
            "Authorization: Bearer $token"
        ],
        'method' => 'POST',
        'content' => json_encode($testData)
    ]
];

$context = stream_context_create($options);

echo "Making HTTP request to: $url" . PHP_EOL;

$result = @file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "❌ HTTP request failed" . PHP_EOL;
    echo "Make sure the PHP server is running: php -S localhost:8000 -t backend/public" . PHP_EOL;
} else {
    echo "✅ HTTP request successful" . PHP_EOL;
    echo "Response:" . PHP_EOL;
    echo $result . PHP_EOL . PHP_EOL;
    
    $response = json_decode($result, true);
    
    if ($response && isset($response['success'])) {
        if ($response['success']) {
            echo "🎉 ✅ REAL API ENDPOINT TEST PASSED!" . PHP_EOL;
            echo "Report ID: " . ($response['data']['id'] ?? 'N/A') . PHP_EOL;
        } else {
            echo "❌ API returned error: " . json_encode($response['error'] ?? 'Unknown error') . PHP_EOL;
        }
    } else {
        echo "❌ Invalid JSON response" . PHP_EOL;
    }
}
