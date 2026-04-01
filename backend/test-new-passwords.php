<?php
/**
 * Test New Passwords via API
 */

$testUsers = [
    ['email' => 'testadmin@test.com', 'password' => 'AS@Saveetha123', 'role' => 'ADMIN'],
    ['email' => 'doctor@test.com', 'password' => 'Patrol@987', 'role' => 'DOCTOR'],
    ['email' => 'deepankumar@gmail.com', 'password' => 'Welcome@456', 'role' => 'PATIENT'],
];

echo "===========================================\n";
echo "Testing New Passwords via API\n";
echo "===========================================\n\n";

foreach ($testUsers as $credentials) {
    echo "Testing {$credentials['role']}: {$credentials['email']}\n";
    echo "-------------------------------------------\n";
    
    $url = 'http://localhost:8000/api/v1/auth/login';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => $credentials['email'],
        'password' => $credentials['password']
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $json = json_decode($response, true);
    
    if ($httpCode == 200 && $json && isset($json['success']) && $json['success']) {
        echo "✅ LOGIN SUCCESS\n";
        echo "   User: {$json['data']['user']['name']}\n";
        echo "   Role: {$json['data']['user']['role']}\n";
    } else {
        echo "❌ LOGIN FAILED (HTTP $httpCode)\n";
        if ($json && isset($json['error'])) {
            echo "   Error: {$json['error']['message']}\n";
        }
    }
    
    echo "\n";
}

echo "===========================================\n";
echo "All passwords tested!\n";
echo "===========================================\n";
