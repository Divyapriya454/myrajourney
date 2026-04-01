<?php
echo "======================================================================\n";
echo "     TESTING SAVE ENDPOINTS\n";
echo "======================================================================\n\n";

$baseUrl = 'http://192.168.29.162:8000/api/v1';

// Login first
$ch = curl_init($baseUrl . '/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);
if ($httpCode != 200 || !$result['success']) {
    die("Login failed!\n");
}

$token = $result['data']['token'];
echo "✓ Login successful\n";
echo "  Token: " . substr($token, 0, 30) . "...\n\n";

// Test 1: Save Health Metric
echo "1. SAVE HEALTH METRIC\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/metrics');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'metric_type' => 'WEIGHT',
    'value' => 75.5,
    'unit' => 'kg',
    'recorded_at' => date('Y-m-d H:i:s')
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: " . substr($response, 0, 200) . "\n";
$result = json_decode($response, true);
if ($httpCode == 200 || $httpCode == 201) {
    echo "✓ Health metric saved\n";
} else {
    echo "✗ Failed to save health metric\n";
}

// Test 2: Save Symptom
echo "\n2. SAVE SYMPTOM\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/symptoms');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'symptom_type' => 'PAIN',
    'severity' => 5,
    'description' => 'Test symptom',
    'recorded_at' => date('Y-m-d H:i:s')
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: " . substr($response, 0, 200) . "\n";
if ($httpCode == 200 || $httpCode == 201) {
    echo "✓ Symptom saved\n";
} else {
    echo "✗ Failed to save symptom\n";
}

// Test 3: Update Profile
echo "\n3. UPDATE PROFILE\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/patients/1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'blood_group' => 'O+',
    'height' => 175,
    'weight' => 75
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: " . substr($response, 0, 200) . "\n";
if ($httpCode == 200) {
    echo "✓ Profile updated\n";
} else {
    echo "✗ Failed to update profile\n";
}

echo "\n======================================================================\n";
echo "TESTING COMPLETE\n";
echo "======================================================================\n";
