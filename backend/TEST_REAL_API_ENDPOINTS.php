<?php
/**
 * TEST REAL API ENDPOINTS
 * Simulates actual Android app requests to verify everything works
 */

$baseUrl = 'http://192.168.29.162:8000';

echo "=================================================================\n";
echo "TESTING REAL API ENDPOINTS\n";
echo "=================================================================\n\n";

// Helper function to make API requests
function apiRequest($url, $method = 'GET', $data = null, $token = null, $files = null) {
    $ch = curl_init();
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

$testResults = [];

// ============================================================================
// TEST 1: DOCTOR LOGIN
// ============================================================================
echo "TEST 1: Doctor Login...\n";
$response = apiRequest("$baseUrl/api/v1/auth/login", 'POST', [
    'email' => 'doctor@test.com',
    'password' => 'Patrol@987'
]);

if ($response['code'] === 200 && isset($response['body']['data']['token'])) {
    $doctorToken = $response['body']['data']['token'];
    $doctorId = $response['body']['data']['user']['id'];
    echo "  ✅ Doctor logged in successfully (ID: $doctorId)\n";
    $testResults['doctor_login'] = 'PASS';
} else {
    echo "  ❌ Doctor login failed\n";
    $testResults['doctor_login'] = 'FAIL';
    die("Cannot continue without doctor login\n");
}

// ============================================================================
// TEST 2: PATIENT LOGIN
// ============================================================================
echo "\nTEST 2: Patient Login...\n";
$response = apiRequest("$baseUrl/api/v1/auth/login", 'POST', [
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
]);

if ($response['code'] === 200 && isset($response['body']['data']['token'])) {
    $patientToken = $response['body']['data']['token'];
    $patientId = $response['body']['data']['user']['id'];
    echo "  ✅ Patient logged in successfully (ID: $patientId)\n";
    $testResults['patient_login'] = 'PASS';
} else {
    echo "  ❌ Patient login failed\n";
    $testResults['patient_login'] = 'FAIL';
}

// ============================================================================
// TEST 3: CREATE REHAB PLAN (Doctor)
// ============================================================================
echo "\nTEST 3: Create Rehab Plan (Doctor)...\n";
$response = apiRequest("$baseUrl/api/v1/rehab-plans", 'POST', [
    'patient_id' => $patientId,
    'title' => 'API Test Rehab Plan',
    'description' => 'Testing rehab plan creation via API',
    'exercises' => [
        [
            'name' => 'Wrist Flexion',
            'description' => 'Gentle wrist flexion',
            'reps' => 10,
            'sets' => 3,
            'frequency_per_week' => 5,
            'video_url' => '/assets/exercise_videos/ex_001_wrist_flexion.mp4'
        ]
    ]
], $doctorToken);

if ($response['code'] === 201 && isset($response['body']['data']['id'])) {
    $rehabPlanId = $response['body']['data']['id'];
    echo "  ✅ Rehab plan created successfully (ID: $rehabPlanId)\n";
    $testResults['rehab_creation'] = 'PASS';
} else {
    echo "  ❌ Rehab plan creation failed\n";
    echo "  Response: " . json_encode($response['body']) . "\n";
    $testResults['rehab_creation'] = 'FAIL';
}

// ============================================================================
// TEST 4: GET REHAB PLANS (Patient)
// ============================================================================
echo "\nTEST 4: Get Rehab Plans (Patient)...\n";
$response = apiRequest("$baseUrl/api/v1/rehab-plans", 'GET', null, $patientToken);

if ($response['code'] === 200 && isset($response['body']['data'])) {
    $planCount = count($response['body']['data']);
    echo "  ✅ Retrieved $planCount rehab plans\n";
    $testResults['rehab_retrieval'] = 'PASS';
} else {
    echo "  ❌ Failed to retrieve rehab plans\n";
    $testResults['rehab_retrieval'] = 'FAIL';
}

// ============================================================================
// TEST 5: GET PATIENT OVERVIEW
// ============================================================================
echo "\nTEST 5: Get Patient Overview...\n";
$response = apiRequest("$baseUrl/api/v1/patients/me/overview", 'GET', null, $patientToken);

if ($response['code'] === 200 && isset($response['body']['data'])) {
    echo "  ✅ Patient overview retrieved successfully\n";
    $testResults['patient_overview'] = 'PASS';
} else {
    echo "  ❌ Failed to retrieve patient overview\n";
    $testResults['patient_overview'] = 'FAIL';
}

// ============================================================================
// TEST 6: GET APPOINTMENTS (Doctor)
// ============================================================================
echo "\nTEST 6: Get Appointments (Doctor)...\n";
$response = apiRequest("$baseUrl/api/v1/appointments?doctor_id=$doctorId", 'GET', null, $doctorToken);

if ($response['code'] === 200) {
    $appointmentCount = isset($response['body']['data']) ? count($response['body']['data']) : 0;
    echo "  ✅ Retrieved $appointmentCount appointments\n";
    $testResults['appointments'] = 'PASS';
} else {
    echo "  ❌ Failed to retrieve appointments\n";
    $testResults['appointments'] = 'FAIL';
}

// ============================================================================
// TEST 7: GET NOTIFICATIONS
// ============================================================================
echo "\nTEST 7: Get Notifications (Patient)...\n";
$response = apiRequest("$baseUrl/api/v1/notifications?page=1&limit=5", 'GET', null, $patientToken);

if ($response['code'] === 200) {
    echo "  ✅ Notifications retrieved successfully\n";
    $testResults['notifications'] = 'PASS';
} else {
    echo "  ❌ Failed to retrieve notifications\n";
    $testResults['notifications'] = 'FAIL';
}

// ============================================================================
// TEST 8: UPDATE PROFILE (Doctor)
// ============================================================================
echo "\nTEST 8: Update Profile (Doctor)...\n";
$response = apiRequest("$baseUrl/api/v1/users/me", 'PUT', [
    'name' => 'Dr. Test Updated',
    'phone' => '9876543210'
], $doctorToken);

if ($response['code'] === 200 && $response['body']['success']) {
    echo "  ✅ Profile updated successfully\n";
    $testResults['profile_update'] = 'PASS';
} else {
    echo "  ❌ Profile update failed\n";
    echo "  Response: " . json_encode($response['body']) . "\n";
    $testResults['profile_update'] = 'FAIL';
}

// ============================================================================
// SUMMARY
// ============================================================================
echo "\n=================================================================\n";
echo "TEST SUMMARY\n";
echo "=================================================================\n";

$passed = 0;
$failed = 0;

foreach ($testResults as $test => $result) {
    $icon = $result === 'PASS' ? '✅' : '❌';
    echo "$icon " . str_pad(ucwords(str_replace('_', ' ', $test)), 30) . " : $result\n";
    
    if ($result === 'PASS') {
        $passed++;
    } else {
        $failed++;
    }
}

$total = $passed + $failed;
$percentage = $total > 0 ? round(($passed / $total) * 100) : 0;

echo "\n";
echo "Total Tests: $total\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Success Rate: $percentage%\n";

if ($failed === 0) {
    echo "\n🎉 ALL API TESTS PASSED! The backend is working correctly.\n";
    echo "\nYou can now test from the Android app:\n";
    echo "1. Login as doctor (doctor@test.com / Patrol@987)\n";
    echo "2. Assign rehab plans to patients\n";
    echo "3. Upload and process reports\n";
    echo "4. Update profile pictures\n";
} else {
    echo "\n⚠️  Some API tests failed. Check the errors above.\n";
}

echo "\n=================================================================\n";
