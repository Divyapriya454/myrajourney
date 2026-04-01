<?php
/**
 * Test Live API Endpoints
 */

echo "=================================================================\n";
echo "LIVE API TEST\n";
echo "=================================================================\n\n";

$baseUrl = "http://localhost:8000";

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
        echo "  ✓ Backend is accessible\n";
        echo "  ✓ Database: " . ($json['database']['connected'] ? 'Connected' : 'Failed') . "\n";
        if (isset($json['database']['users_count'])) {
            echo "  ✓ Users in database: " . $json['database']['users_count'] . "\n";
        }
    }
} else {
    echo "  ✗ Connection failed (HTTP $httpCode)\n";
}
echo "\n";

// Test 2: Login endpoint
echo "Test 2: Login API\n";
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

echo "  URL: $baseUrl/api/v1/auth/login\n";
echo "  HTTP Code: $httpCode\n";

if ($httpCode === 200) {
    $json = json_decode($response, true);
    if ($json && isset($json['success']) && $json['success']) {
        echo "  ✓ Login successful\n";
        echo "  ✓ User: " . ($json['data']['user']['name'] ?? 'N/A') . "\n";
        echo "  ✓ Role: " . ($json['data']['user']['role'] ?? 'N/A') . "\n";
        echo "  ✓ Token received: " . (isset($json['data']['token']) ? 'Yes' : 'No') . "\n";
        
        $token = $json['data']['token'] ?? null;
        
        // Test 3: Authenticated endpoint
        if ($token) {
            echo "\nTest 3: Authenticated Request (Appointments)\n";
            
            $ch = curl_init("$baseUrl/api/v1/appointments");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                "Authorization: Bearer $token"
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            echo "  HTTP Code: $httpCode\n";
            
            if ($httpCode === 200) {
                $json = json_decode($response, true);
                if ($json && isset($json['success'])) {
                    echo "  ✓ Authenticated request successful\n";
                    if (isset($json['data'])) {
                        $count = is_array($json['data']) ? count($json['data']) : 0;
                        echo "  ✓ Appointments found: $count\n";
                    }
                }
            } else {
                echo "  ⚠ Request returned HTTP $httpCode\n";
            }
        }
        
    } else {
        echo "  ✗ Login failed\n";
        echo "  Response: " . substr($response, 0, 200) . "\n";
    }
} else {
    echo "  ✗ Login endpoint failed (HTTP $httpCode)\n";
    echo "  Response: " . substr($response, 0, 200) . "\n";
}

echo "\n=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n";
echo "Backend Server: http://localhost:8000\n";
echo "API Base URL: http://localhost:8000/api/v1/\n";
echo "\nFor Android Device:\n";
echo "- Get your PC IP: ipconfig\n";
echo "- Update network_config.xml with your IP\n";
echo "- Use: http://YOUR_IP:8000/api/v1/\n";
echo "=================================================================\n";
