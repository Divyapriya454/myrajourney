<?php
echo "======================================================================\n";
echo "     TESTING ALL SAVE OPERATIONS\n";
echo "======================================================================\n\n";

$baseUrl = 'http://192.168.29.162:8000/api/v1';
$passed = 0;
$failed = 0;

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
    die("✗ Login failed!\n");
}

$token = $result['data']['token'];
$userId = $result['data']['user']['id'];
echo "✓ Login successful (User ID: $userId)\n\n";

// Test 1: Update Patient Profile
echo "1. UPDATE PATIENT PROFILE\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . "/patients/$userId/update");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'blood_group' => 'O+',
    'height' => 175.5,
    'weight' => 75.0,
    'allergies' => 'None',
    'emergency_contact' => '1234567890'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$result = json_decode($response, true);
if ($httpCode == 200 && $result['success']) {
    echo "✓ Profile updated successfully\n";
    $passed++;
} else {
    echo "✗ Failed: " . ($result['error']['message'] ?? 'Unknown error') . "\n";
    $failed++;
}

// Test 2: Save Health Metric
echo "\n2. SAVE HEALTH METRIC\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/health-metrics');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'metric_type' => 'weight',
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
$result = json_decode($response, true);
echo "Full Response: " . $response . "\n";
if ($httpCode == 200 || $httpCode == 201) {
    if (isset($result['success']) && $result['success']) {
        echo "✓ Health metric saved\n";
        $passed++;
    } else {
        echo "✗ Failed: " . ($result['error']['message'] ?? json_encode($result)) . "\n";
        $failed++;
    }
} else {
    echo "✗ Failed with HTTP $httpCode\n";
    echo "Response: " . substr($response, 0, 200) . "\n";
    $failed++;
}

// Test 3: Save Symptom
echo "\n3. SAVE SYMPTOM\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/symptoms');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'patient_id' => $userId,
    'date' => date('Y-m-d'),
    'pain_level' => 5,
    'stiffness_level' => 4,
    'fatigue_level' => 6,
    'swollen_joints' => 2,
    'tender_joints' => 3,
    'notes' => 'Test symptom entry'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$result = json_decode($response, true);
echo "Full Response: " . $response . "\n";
if ($httpCode == 200 || $httpCode == 201) {
    if (isset($result['success']) && $result['success']) {
        echo "✓ Symptom saved\n";
        $passed++;
    } else {
        echo "✗ Failed: " . ($result['error']['message'] ?? json_encode($result)) . "\n";
        $failed++;
    }
} else {
    echo "✗ Failed with HTTP $httpCode\n";
    echo "Response: " . substr($response, 0, 200) . "\n";
    $failed++;
}

// Test 4: Get Patient Profile
echo "\n4. GET PATIENT PROFILE\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . "/patients/$userId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$result = json_decode($response, true);
if ($httpCode == 200 && $result['success']) {
    echo "✓ Profile retrieved\n";
    echo "  Blood Group: " . ($result['data']['blood_group'] ?? 'Not set') . "\n";
    echo "  Height: " . ($result['data']['height'] ?? 'Not set') . "\n";
    echo "  Weight: " . ($result['data']['weight'] ?? 'Not set') . "\n";
    $passed++;
} else {
    echo "✗ Failed to retrieve profile\n";
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
    echo "✅ ALL SAVE OPERATIONS WORKING PERFECTLY!\n\n";
    echo "The app should now be able to save:\n";
    echo "- Patient profile updates\n";
    echo "- Health metrics\n";
    echo "- Symptoms\n";
    echo "- And retrieve all data\n";
} else {
    echo "⚠ Some operations failed. Check the errors above.\n";
}
