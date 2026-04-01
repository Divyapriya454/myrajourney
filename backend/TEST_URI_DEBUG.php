<?php
/**
 * DEBUG URI ROUTING
 */

$baseUrl = 'http://192.168.29.162/myrajourney/public/index.php';

echo "Testing URI: /api/v1/rehab/today\n\n";

// Login as patient
$loginData = [
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
];

$ch = curl_init($baseUrl . '/api/v1/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
curl_close($ch);

$loginResult = json_decode($response, true);
$token = $loginResult['data']['token'];

// Test the endpoint
$ch = curl_init($baseUrl . '/api/v1/rehab/today');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

// Check the API log
echo "Checking API log...\n";
$logFile = 'C:/xampp/htdocs/myrajourney/public/api_log.txt';
if (file_exists($logFile)) {
    $log = file_get_contents($logFile);
    $lines = explode("\n", $log);
    $recentLines = array_slice($lines, -10);
    echo implode("\n", $recentLines);
}
