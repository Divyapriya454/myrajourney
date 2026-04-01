<?php
/**
 * Test API Endpoint - Simulates curl request
 */

$url = 'http://localhost:8000/api/v1/auth/login';
$data = [
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
];

echo "===========================================\n";
echo "Testing API Endpoint\n";
echo "===========================================\n\n";

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

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo "-------------------------------------------\n";
echo $response . "\n";
echo "-------------------------------------------\n\n";

if ($error) {
    echo "cURL Error: $error\n";
}

$json = json_decode($response, true);
if ($json) {
    if (isset($json['success']) && $json['success']) {
        echo "✅ LOGIN SUCCESSFUL!\n";
        echo "User: {$json['data']['user']['name']}\n";
        echo "Role: {$json['data']['user']['role']}\n";
    } else {
        echo "❌ LOGIN FAILED!\n";
        if (isset($json['error'])) {
            echo "Error: {$json['error']['message']}\n";
            if (isset($json['error']['code'])) {
                echo "Code: {$json['error']['code']}\n";
            }
        }
    }
} else {
    echo "❌ Invalid JSON response\n";
}
