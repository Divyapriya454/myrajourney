<?php
/**
 * Test All Doctor Functions
 */

echo "=================================================================\n";
echo "TESTING ALL DOCTOR FUNCTIONS\n";
echo "=================================================================\n\n";

$baseUrl = "http://localhost:8000";

function apiCall($method, $endpoint, $data = null, $token = null, $baseUrl = "http://localhost:8000") {
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
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
        'response' => json_decode($response, true),
        'raw' => $response
    ];
}

// Login as doctor
echo "Logging in as doctor...\n";
$result = apiCall('POST', '/api/v1/auth/login', [
    'email' => 'doctor@test.com',
    'password' => 'Patrol@987'
], null, $baseUrl);

if ($result['code'] !== 200) {
    echo "✗ Login failed!\n";
    exit(1);
}

$token = $result['response']['data']['token'] ?? null;
$doctorId = $result['response']['data']['user']['id'] ?? null;
echo "✓ Login successful (Doctor ID: $doctorId)\n\n";

$passed = 0;
$failed = 0;

// Test 1: Get Patients List
echo "Test 1: Get Patients List\n";
$result = apiCall('GET', '/api/v1/patients', null, $token, $baseUrl);

if ($result['code'] === 200) {
    $patients = $result['response']['data'] ?? [];
    echo "  ✓ PASSED - Found " . count($patients) . " patient(s)\n";
    $patientId = !empty($patients) ? $patients[0]['id'] : 1;
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    echo "  Error: " . substr($result['raw'], 0, 200) . "\n";
    $patientId = 1;
    $failed++;
}
echo "\n";

// Test 2: Get Patient Details
echo "Test 2: Get Patient Details\n";
$result = apiCall('GET', "/api/v1/patients/$patientId", null, $token, $baseUrl);

if ($result['code'] === 200) {
    echo "  ✓ PASSED\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    if ($result['code'] === 500) {
        echo "  Error: " . substr($result['raw'], 0, 200) . "\n";
    }
    $failed++;
}
echo "\n";

// Test 3: Get Patient Reports
echo "Test 3: Get Patient Reports\n";
$result = apiCall('GET', "/api/v1/reports?patient_id=$patientId", null, $token, $baseUrl);

if ($result['code'] === 200) {
    $reports = $result['response']['data'] ?? [];
    echo "  ✓ PASSED - Found " . count($reports) . " report(s)\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    $failed++;
}
echo "\n";

// Test 4: Assign Medication
echo "Test 4: Assign Medication to Patient\n";
$medicationData = [
    'patient_id' => $patientId,
    'medication_name' => 'Test Medication',
    'dosage' => '500mg',
    'frequency' => 'Twice daily',
    'start_date' => date('Y-m-d'),
    'instructions' => 'Take with food',
    'is_morning' => 1,
    'is_afternoon' => 0,
    'is_night' => 1
];

$result = apiCall('POST', '/api/v1/patient-medications', $medicationData, $token, $baseUrl);

if ($result['code'] === 200 || $result['code'] === 201) {
    echo "  ✓ PASSED (HTTP {$result['code']})\n";
    $medicationId = $result['response']['data']['id'] ?? null;
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    if ($result['code'] === 500) {
        echo "  Error: " . substr($result['raw'], 0, 300) . "\n";
    }
    $medicationId = null;
    $failed++;
}
echo "\n";

// Test 5: Assign Rehab Plan
echo "Test 5: Assign Rehab Plan to Patient\n";
$rehabData = [
    'patient_id' => $patientId,
    'title' => 'Test Rehab Plan',
    'description' => 'Physical therapy exercises',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+30 days')),
    'status' => 'ACTIVE'
];

$result = apiCall('POST', '/api/v1/rehab-plans', $rehabData, $token, $baseUrl);

if ($result['code'] === 200 || $result['code'] === 201) {
    echo "  ✓ PASSED (HTTP {$result['code']})\n";
    $rehabPlanId = $result['response']['data']['id'] ?? null;
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    if ($result['code'] === 500) {
        echo "  Error: " . substr($result['raw'], 0, 300) . "\n";
    }
    $rehabPlanId = null;
    $failed++;
}
echo "\n";

// Test 6: Add Exercise to Rehab Plan
if ($rehabPlanId) {
    echo "Test 6: Add Exercise to Rehab Plan\n";
    $exerciseData = [
        'rehab_plan_id' => $rehabPlanId,
        'exercise_name' => 'Wrist Flexion',
        'description' => 'Bend wrist forward and backward',
        'repetitions' => 10,
        'sets' => 3,
        'duration_minutes' => 5
    ];
    
    $result = apiCall('POST', '/api/v1/rehab-exercises', $exerciseData, $token, $baseUrl);
    
    if ($result['code'] === 200 || $result['code'] === 201) {
        echo "  ✓ PASSED (HTTP {$result['code']})\n";
        $passed++;
    } else {
        echo "  ✗ FAILED (HTTP {$result['code']})\n";
        if ($result['code'] === 500) {
            echo "  Error: " . substr($result['raw'], 0, 300) . "\n";
        }
        $failed++;
    }
    echo "\n";
}

// Test 7: Get Appointments
echo "Test 7: Get Doctor's Appointments\n";
$result = apiCall('GET', "/api/v1/appointments?doctor_id=$doctorId", null, $token, $baseUrl);

if ($result['code'] === 200) {
    $appointments = $result['response']['data'] ?? [];
    echo "  ✓ PASSED - Found " . count($appointments) . " appointment(s)\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    $failed++;
}
echo "\n";

// Test 8: Get Patient Medications
echo "Test 8: Get Patient Medications\n";
$result = apiCall('GET', "/api/v1/patient-medications?patient_id=$patientId", null, $token, $baseUrl);

if ($result['code'] === 200) {
    $medications = $result['response']['data']['items'] ?? $result['response']['data'] ?? [];
    echo "  ✓ PASSED - Found " . count($medications) . " medication(s)\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    $failed++;
}
echo "\n";

// Test 9: Get Patient Rehab Plans
echo "Test 9: Get Patient Rehab Plans\n";
$result = apiCall('GET', "/api/v1/rehab-plans?patient_id=$patientId", null, $token, $baseUrl);

if ($result['code'] === 200) {
    $plans = $result['response']['data'] ?? [];
    echo "  ✓ PASSED - Found " . count($plans) . " rehab plan(s)\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    $failed++;
}
echo "\n";

// Summary
echo "=================================================================\n";
echo "DOCTOR FUNCTIONS TEST SUMMARY\n";
echo "=================================================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: $passed ✓\n";
echo "Failed: $failed " . ($failed > 0 ? "✗" : "") . "\n";
echo "\n";

if ($failed === 0) {
    echo "✓ ALL DOCTOR FUNCTIONS WORKING!\n";
} else {
    echo "⚠ Some doctor functions failed. Check errors above.\n";
}

echo "=================================================================\n";
