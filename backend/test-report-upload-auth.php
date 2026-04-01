<?php
/**
 * Test Report Upload Authentication
 */

// First, login to get token
$loginUrl = 'http://localhost:8000/api/v1/auth/login';
$loginData = [
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
];

echo "===========================================\n";
echo "Testing Report Upload\n";
echo "===========================================\n\n";

echo "Step 1: Login to get token...\n";
$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$json = json_decode($response, true);

if ($httpCode == 200 && $json && isset($json['data']['token'])) {
    $token = $json['data']['token'];
    echo "✅ Login successful\n";
    echo "Token: " . substr($token, 0, 20) . "...\n\n";
    
    echo "Step 2: Test report upload endpoint...\n";
    $uploadUrl = 'http://localhost:8000/api/v1/reports';
    
    // Test with just headers (no actual file)
    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n\n";
    
    if ($httpCode == 403) {
        echo "❌ 403 Forbidden - Authentication issue\n";
        echo "Possible causes:\n";
        echo "- Token not being sent correctly\n";
        echo "- Middleware blocking request\n";
        echo "- CORS issue\n";
    } else if ($httpCode == 400) {
        echo "✅ Authentication works (400 = missing file data)\n";
    } else if ($httpCode == 200) {
        echo "✅ Endpoint accessible\n";
    }
    
} else {
    echo "❌ Login failed\n";
}

echo "\n===========================================\n";
