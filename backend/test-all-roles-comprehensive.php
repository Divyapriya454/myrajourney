<?php
echo "======================================================================\n";
echo "     COMPREHENSIVE ALL ROLES TEST\n";
echo "======================================================================\n\n";

$baseUrl = 'http://192.168.29.162:8000/api/v1';
$passed = 0;
$failed = 0;

// Test all three logins
$users = [
    ['email' => 'deepankumar@gmail.com', 'password' => 'Welcome@456', 'role' => 'PATIENT'],
    ['email' => 'doctor@test.com', 'password' => 'Doctor@123', 'role' => 'DOCTOR'],
    ['email' => 'testadmin@test.com', 'password' => 'Admin@123', 'role' => 'ADMIN']
];

$tokens = [];

foreach ($users as $user) {
    echo "Testing {$user['role']} Login\n";
    echo "-------------------------------------------\n";
    
    $ch = curl_init($baseUrl . '/auth/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => $user['email'],
        'password' => $user['password']
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    echo "HTTP Code: $httpCode\n";
    if ($httpCode == 200 && isset($result['success']) && $result['success']) {
        echo "✓ {$user['role']} login successful\n";
        echo "  User: {$result['data']['user']['name']}\n";
        $tokens[$user['role']] = $result['data']['token'];
        $passed++;
    } else {
        echo "✗ {$user['role']} login failed\n";
        echo "  Response: " . substr($response, 0, 200) . "\n";
        $failed++;
    }
    echo "\n";
}

// Test Patient Overview
if (isset($tokens['PATIENT'])) {
    echo "Testing Patient Overview\n";
    echo "-------------------------------------------\n";
    
    $ch = curl_init($baseUrl . '/patients/me/overview');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $tokens['PATIENT']
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    if ($httpCode == 200) {
        echo "✓ Patient overview working\n";
        $passed++;
    } else {
        echo "✗ Patient overview failed\n";
        echo "  Response: " . substr($response, 0, 300) . "\n";
        $failed++;
    }
    echo "\n";
}

// Test Doctor Get Patients
if (isset($tokens['DOCTOR'])) {
    echo "Testing Doctor Get Patients\n";
    echo "-------------------------------------------\n";
    
    $ch = curl_init($baseUrl . '/patients');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $tokens['DOCTOR']
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    $result = json_decode($response, true);
    if ($httpCode == 200 && isset($result['success'])) {
        echo "✓ Doctor get patients working\n";
        $patientCount = isset($result['data']) ? count($result['data']) : 0;
        echo "  Patients: $patientCount\n";
        $passed++;
    } else {
        echo "✗ Doctor get patients failed\n";
        echo "  Response: " . substr($response, 0, 300) . "\n";
        $failed++;
    }
    echo "\n";
}

// Test Admin Get Users
if (isset($tokens['ADMIN'])) {
    echo "Testing Admin Get Users\n";
    echo "-------------------------------------------\n";
    
    $ch = curl_init($baseUrl . '/users');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $tokens['ADMIN']
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    $result = json_decode($response, true);
    if ($httpCode == 200 && isset($result['success'])) {
        echo "✓ Admin get users working\n";
        $userCount = isset($result['data']) ? count($result['data']) : 0;
        echo "  Users: $userCount\n";
        $passed++;
    } else {
        echo "✗ Admin get users failed\n";
        echo "  Response: " . substr($response, 0, 300) . "\n";
        $failed++;
    }
    echo "\n";
}

// Test User Registration
echo "Testing User Registration\n";
echo "-------------------------------------------\n";

$ch = curl_init($baseUrl . '/auth/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'Test Patient ' . time(),
    'email' => 'testpatient' . time() . '@test.com',
    'password' => 'Test@123',
    'phone' => '1234567890',
    'role' => 'PATIENT'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$result = json_decode($response, true);
if ($httpCode == 200 || $httpCode == 201) {
    if (isset($result['success']) && $result['success']) {
        echo "✓ User registration working\n";
        $passed++;
    } else {
        echo "✗ Registration failed: " . ($result['error']['message'] ?? 'Unknown') . "\n";
        $failed++;
    }
} else {
    echo "✗ Registration failed with HTTP $httpCode\n";
    echo "  Response: " . substr($response, 0, 300) . "\n";
    $failed++;
}

// Summary
echo "\n======================================================================\n";
echo "TEST SUMMARY\n";
echo "======================================================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "✓ Passed:    $passed\n";
echo "✗ Failed:    $failed\n";
echo "\nSuccess Rate: " . round(($passed / ($passed + $failed)) * 100, 1) . "%\n";
echo "======================================================================\n\n";

if ($failed == 0) {
    echo "✅ ALL ROLES AND FEATURES WORKING PERFECTLY!\n";
    echo "App is ready for Play Store deployment.\n";
} else {
    echo "⚠ Some features need attention.\n";
}
