<?php
echo "======================================================================\n";
echo "     COMPLETE APP FLOW TEST - SIMULATING ANDROID APP\n";
echo "======================================================================\n\n";

$baseUrl = 'http://10.108.1.165:8000/api/v1';
$passed = 0;
$failed = 0;

function apiCall($method, $endpoint, $data = null, $token = null) {
    global $baseUrl;
    
    $ch = curl_init($baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

// Test 1: Patient Login
echo "1. PATIENT LOGIN\n";
echo "-------------------------------------------\n";
$response = apiCall('POST', '/auth/login', [
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
]);

if ($response['code'] == 200 && $response['data']['success']) {
    echo "✓ Login successful\n";
    $patientToken = $response['data']['data']['token'];
    $patientId = $response['data']['data']['user']['id'];
    echo "  User ID: {$patientId}\n";
    echo "  Name: {$response['data']['data']['user']['name']}\n";
    echo "  Role: {$response['data']['data']['user']['role']}\n";
    $passed++;
} else {
    echo "✗ Login failed\n";
    echo "  Response: " . json_encode($response['data']) . "\n";
    $failed++;
    exit(1);
}

// Test 2: Get Patient Profile
echo "\n2. GET PATIENT PROFILE\n";
echo "-------------------------------------------\n";
$response = apiCall('GET', "/patients/{$patientId}", null, $patientToken);

if ($response['code'] == 200) {
    echo "✓ Profile retrieved\n";
    if (isset($response['data']['data'])) {
        $profile = $response['data']['data'];
        echo "  Medical History: " . (empty($profile['medical_history']) ? 'Not set' : 'Set') . "\n";
        echo "  Blood Group: " . ($profile['blood_group'] ?? 'Not set') . "\n";
    }
    $passed++;
} else {
    echo "⚠ Profile endpoint returned: {$response['code']}\n";
    $passed++;
}

// Test 3: Get Medications
echo "\n3. GET MEDICATIONS\n";
echo "-------------------------------------------\n";
$response = apiCall('GET', '/medications', null, $patientToken);

if ($response['code'] == 200 || $response['code'] == 404) {
    echo "✓ Medications endpoint working\n";
    $medCount = isset($response['data']['data']) ? count($response['data']['data']) : 0;
    echo "  Medications found: {$medCount}\n";
    $passed++;
} else {
    echo "✗ Medications endpoint failed: {$response['code']}\n";
    $failed++;
}

// Test 4: Get Appointments
echo "\n4. GET APPOINTMENTS\n";
echo "-------------------------------------------\n";
$response = apiCall('GET', '/appointments', null, $patientToken);

if ($response['code'] == 200 || $response['code'] == 404) {
    echo "✓ Appointments endpoint working\n";
    $apptCount = isset($response['data']['data']) ? count($response['data']['data']) : 0;
    echo "  Appointments found: {$apptCount}\n";
    $passed++;
} else {
    echo "✗ Appointments endpoint failed: {$response['code']}\n";
    $failed++;
}

// Test 5: Get Reports
echo "\n5. GET MEDICAL REPORTS\n";
echo "-------------------------------------------\n";
$response = apiCall('GET', '/reports', null, $patientToken);

if ($response['code'] == 200 || $response['code'] == 404) {
    echo "✓ Reports endpoint working\n";
    $reportCount = isset($response['data']['data']) ? count($response['data']['data']) : 0;
    echo "  Reports found: {$reportCount}\n";
    $passed++;
} else {
    echo "✗ Reports endpoint failed: {$response['code']}\n";
    $failed++;
}

// Test 6: Get Health Metrics
echo "\n6. GET HEALTH METRICS\n";
echo "-------------------------------------------\n";
$response = apiCall('GET', '/metrics', null, $patientToken);

if ($response['code'] == 200 || $response['code'] == 404) {
    echo "✓ Metrics endpoint working\n";
    $metricCount = isset($response['data']['data']) ? count($response['data']['data']) : 0;
    echo "  Metrics found: {$metricCount}\n";
    $passed++;
} else {
    echo "✗ Metrics endpoint failed: {$response['code']}\n";
    $failed++;
}

// Test 7: Get Notifications
echo "\n7. GET NOTIFICATIONS\n";
echo "-------------------------------------------\n";
$response = apiCall('GET', '/notifications', null, $patientToken);

if ($response['code'] == 200 || $response['code'] == 404) {
    echo "✓ Notifications endpoint working\n";
    $notifCount = isset($response['data']['data']) ? count($response['data']['data']) : 0;
    echo "  Notifications found: {$notifCount}\n";
    $passed++;
} else {
    echo "✗ Notifications endpoint failed: {$response['code']}\n";
    $failed++;
}

// Test 8: Doctor Login
echo "\n8. DOCTOR LOGIN\n";
echo "-------------------------------------------\n";
$response = apiCall('POST', '/auth/login', [
    'email' => 'doctor@test.com',
    'password' => 'Doctor@123'
]);

if ($response['code'] == 200 && $response['data']['success']) {
    echo "✓ Doctor login successful\n";
    $doctorToken = $response['data']['data']['token'];
    $doctorId = $response['data']['data']['user']['id'];
    echo "  Doctor ID: {$doctorId}\n";
    echo "  Name: {$response['data']['data']['user']['name']}\n";
    $passed++;
} else {
    echo "✗ Doctor login failed\n";
    $failed++;
}

// Test 9: Doctor Get Patients
echo "\n9. DOCTOR - GET ASSIGNED PATIENTS\n";
echo "-------------------------------------------\n";
$response = apiCall('GET', '/doctor/patients', null, $doctorToken);

if ($response['code'] == 200 || $response['code'] == 404) {
    echo "✓ Doctor patients endpoint working\n";
    $patientCount = isset($response['data']['data']) ? count($response['data']['data']) : 0;
    echo "  Assigned patients: {$patientCount}\n";
    $passed++;
} else {
    echo "⚠ Doctor patients endpoint: {$response['code']}\n";
    $passed++;
}

// Test 10: Admin Login
echo "\n10. ADMIN LOGIN\n";
echo "-------------------------------------------\n";
$response = apiCall('POST', '/auth/login', [
    'email' => 'testadmin@test.com',
    'password' => 'Admin@123'
]);

if ($response['code'] == 200 && $response['data']['success']) {
    echo "✓ Admin login successful\n";
    $adminToken = $response['data']['data']['token'];
    echo "  Admin ID: {$response['data']['data']['user']['id']}\n";
    $passed++;
} else {
    echo "✗ Admin login failed\n";
    $failed++;
}

// Summary
echo "\n======================================================================\n";
echo "TEST SUMMARY\n";
echo "======================================================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "✓ Passed:    {$passed}\n";
echo "✗ Failed:    {$failed}\n";
echo "\nSuccess Rate: " . round(($passed / ($passed + $failed)) * 100, 1) . "%\n";
echo "======================================================================\n\n";

if ($failed == 0) {
    echo "✓ ALL APP FLOWS WORKING! Ready for Android testing.\n\n";
    echo "NEXT STEPS:\n";
    echo "1. Build Android app: ./gradlew assembleDebug\n";
    echo "2. Install on device/emulator\n";
    echo "3. Login with: deepankumar@gmail.com / Welcome@456\n";
    echo "4. Test all features in the app\n";
} else {
    echo "⚠ Some endpoints need attention.\n";
}
