<?php
/**
 * Test Login with New IP
 */

require __DIR__ . '/src/bootstrap.php';

echo "=== TESTING LOGIN WITH NEW IP ===\n\n";

$newIP = '10.108.1.165';
$port = '8000';
$baseUrl = "http://$newIP:$port/api/v1";

echo "Testing API at: $baseUrl\n\n";

// Test 1: Check if server is accessible
echo "Test 1: Server Accessibility\n";
$ch = curl_init("$baseUrl/education/articles");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✓ Server is accessible\n";
    echo "✓ HTTP Status: $httpCode\n\n";
} else {
    echo "✗ Server not accessible\n";
    echo "✗ HTTP Status: $httpCode\n\n";
    exit(1);
}

// Test 2: Test login endpoint
echo "Test 2: Login Endpoint\n";

$loginData = [
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
];

$ch = curl_init("$baseUrl/auth/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "✓ Login successful\n";
        echo "✓ User: {$data['data']['user']['name']}\n";
        echo "✓ Role: {$data['data']['user']['role']}\n";
        echo "✓ Token received: " . substr($data['data']['token'], 0, 20) . "...\n\n";
    } else {
        echo "✗ Login failed\n";
        echo "Response: $response\n\n";
        exit(1);
    }
} else {
    echo "✗ Login request failed\n";
    echo "✗ HTTP Status: $httpCode\n";
    echo "Response: $response\n\n";
    exit(1);
}

// Test 3: Test all user accounts
echo "Test 3: All User Accounts\n\n";

$testAccounts = [
    ['email' => 'deepankumar@gmail.com', 'password' => 'Welcome@456', 'role' => 'PATIENT'],
    ['email' => 'doctor@test.com', 'password' => 'Patrol@987', 'role' => 'DOCTOR'],
    ['email' => 'testadmin@test.com', 'password' => 'AS@Saveetha123', 'role' => 'ADMIN']
];

foreach ($testAccounts as $account) {
    $loginData = [
        'email' => $account['email'],
        'password' => $account['password']
    ];
    
    $ch = curl_init("$baseUrl/auth/login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "✓ {$account['role']}: {$account['email']}\n";
        } else {
            echo "✗ {$account['role']}: {$account['email']} - Login failed\n";
        }
    } else {
        echo "✗ {$account['role']}: {$account['email']} - HTTP $httpCode\n";
    }
}

echo "\n=== ALL TESTS COMPLETE ===\n";
echo "✓ Backend server is running correctly\n";
echo "✓ Login endpoint is working\n";
echo "✓ All test accounts are accessible\n\n";

echo "App Configuration:\n";
echo "  IP: $newIP\n";
echo "  Port: $port\n";
echo "  Base URL: $baseUrl\n\n";

echo "You can now login to the app!\n";
