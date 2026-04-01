<?php
/**
 * Test API from Device IP
 * Simulates Android device connection
 */

echo "=================================================================\n";
echo "TESTING FROM DEVICE IP (192.168.29.108)\n";
echo "=================================================================\n\n";

// Use the network IP that the device will use
$baseUrl = "http://192.168.29.162:8000";

echo "Testing connection to: $baseUrl\n\n";

// Test 1: Connection test
echo "Test 1: Connection Test\n";
$ch = curl_init("$baseUrl/test-android-connection.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "  ✓ Connection successful (HTTP $httpCode)\n";
    $json = json_decode($response, true);
    if ($json && isset($json['success'])) {
        echo "  ✓ Backend accessible from network\n";
        echo "  ✓ Database: " . ($json['database']['connected'] ? 'Connected' : 'Failed') . "\n";
    }
} else {
    echo "  ✗ Connection failed (HTTP $httpCode)\n";
}
echo "\n";

// Test 2: Login
echo "Test 2: Login Test\n";
$loginData = [
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
];

$ch = curl_init("$baseUrl/api/v1/auth/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $json = json_decode($response, true);
    if ($json && isset($json['success']) && $json['success']) {
        echo "  ✓ Login successful\n";
        echo "  ✓ User: " . ($json['data']['user']['name'] ?? 'N/A') . "\n";
        echo "  ✓ Token received\n";
    } else {
        echo "  ✗ Login failed\n";
    }
} else {
    echo "  ✗ Login endpoint failed (HTTP $httpCode)\n";
}
echo "\n";

echo "=================================================================\n";
echo "NETWORK TEST COMPLETE\n";
echo "=================================================================\n";
echo "\nAndroid App Configuration:\n";
echo "  IP: 192.168.29.162\n";
echo "  Port: 8000\n";
echo "  API Base: http://192.168.29.162:8000/api/v1/\n";
echo "\nIf tests passed, the Android app should be able to connect!\n";
echo "=================================================================\n";
