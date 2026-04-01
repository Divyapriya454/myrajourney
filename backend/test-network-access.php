<?php
/**
 * Test Network Access from Mobile IP
 */

// Get the IP from mobile (from the error log you showed earlier)
$mobileIP = '10.34.163.176'; // From your error log
$serverIP = '10.34.163.165'; // Your PC IP

$url = "http://{$serverIP}:8000/api/v1/auth/login";
$data = [
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
];

echo "===========================================\n";
echo "Testing Network Access\n";
echo "===========================================\n\n";

echo "Server IP: $serverIP\n";
echo "Mobile IP: $mobileIP\n";
echo "URL: $url\n";
echo "Data: " . json_encode($data) . "\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

echo "Sending request...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($error) {
    echo "❌ cURL Error: $error\n";
    echo "\nThis means the server is not accessible from network!\n";
    echo "Make sure:\n";
    echo "1. PHP server is running: php -S 0.0.0.0:8000 -t public\n";
    echo "2. Firewall allows port 8000\n";
    echo "3. Mobile is connected to PC's hotspot\n";
} else {
    echo "Response:\n";
    echo "-------------------------------------------\n";
    echo $response . "\n";
    echo "-------------------------------------------\n\n";
    
    $json = json_decode($response, true);
    if ($json && isset($json['success']) && $json['success']) {
        echo "✅ NETWORK ACCESS WORKS!\n";
        echo "The Android app should be able to login!\n";
    } else {
        echo "❌ Got response but login failed\n";
    }
}
