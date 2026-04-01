<?php
$baseUrl = 'http://192.168.29.162:8000/api/v1';

echo "Testing Registration with Debug\n";
echo "-------------------------------------------\n";

$testData = [
    'name' => 'Test Patient ' . time(),
    'email' => 'testpatient' . time() . '@test.com',
    'password' => 'Test@123',
    'phone' => '1234567890',
    'role' => 'PATIENT'
];

echo "Sending data:\n";
print_r($testData);
echo "\n";

$ch = curl_init($baseUrl . '/auth/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
if ($error) {
    echo "Curl Error: $error\n";
}

// Try to decode
$result = json_decode($response, true);
if ($result) {
    echo "\nDecoded Response:\n";
    print_r($result);
}
