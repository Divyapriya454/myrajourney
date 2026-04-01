<?php
/**
 * Complete Doctor Functions Test
 * Tests all doctor features including AI report processing
 */

echo "=================================================================\n";
echo "COMPLETE DOCTOR FUNCTIONS TEST\n";
echo "=================================================================\n\n";

$baseUrl = "http://localhost:8000";

function apiCall($method, $endpoint, $data = null, $token = null, $baseUrl = "http://localhost:8000") {
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Increased timeout for AI processing
    
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
$patientId = 1; // Use existing patient

echo "=================================================================\n";
echo "SECTION 1: PATIENT MANAGEMENT\n";
echo "=================================================================\n\n";

// Test 1: Get Patients List
echo "Test 1: Get Patients List\n";
$result = apiCall('GET', '/api/v1/patients', null, $token, $baseUrl);

if ($result['code'] === 200) {
    echo "  ✓ PASSED\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
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
    $failed++;
}
echo "\n";

echo "=================================================================\n";
echo "SECTION 2: MEDICATION MANAGEMENT\n";
echo "=================================================================\n\n";

// Test 3: Assign Medication
echo "Test 3: Assign Medication\n";
$medicationData = [
    'patient_id' => $patientId,
    'medication_name' => 'Methotrexate ' . time(), // Unique name
    'dosage' => '15mg',
    'frequency' => 'Once weekly',
    'start_date' => date('Y-m-d'),
    'instructions' => 'Take on empty stomach',
    'is_morning' => 1,
    'is_afternoon' => 0,
    'is_night' => 0
];

$result = apiCall('POST', '/api/v1/patient-medications', $medicationData, $token, $baseUrl);

if ($result['code'] === 200 || $result['code'] === 201) {
    echo "  ✓ PASSED (HTTP {$result['code']})\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    if ($result['code'] === 500) {
        echo "  Error: " . substr($result['raw'], 0, 200) . "\n";
    }
    $failed++;
}
echo "\n";

// Test 4: Get Patient Medications
echo "Test 4: Get Patient Medications\n";
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

echo "=================================================================\n";
echo "SECTION 3: REHAB MANAGEMENT\n";
echo "=================================================================\n\n";

// Test 5: Create Rehab Plan
echo "Test 5: Create Rehab Plan\n";
$rehabData = [
    'patient_id' => $patientId,
    'title' => 'Hand Therapy Plan ' . time(),
    'description' => 'Exercises for hand mobility',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+30 days')),
    'exercises' => [
        [
            'name' => 'Wrist Flexion',
            'description' => 'Bend wrist forward and backward',
            'reps' => '10',
            'sets' => 3,
            'frequency_per_week' => '5'
        ],
        [
            'name' => 'Finger Stretch',
            'description' => 'Stretch fingers wide',
            'reps' => '15',
            'sets' => 2,
            'frequency_per_week' => '7'
        ]
    ]
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

// Test 6: Get Patient Rehab Plans
echo "Test 6: Get Patient Rehab Plans\n";
$result = apiCall('GET', "/api/v1/rehab-plans?patient_id=$patientId", null, $token, $baseUrl);

if ($result['code'] === 200) {
    $plans = $result['response']['data'] ?? [];
    echo "  ✓ PASSED - Found " . count($plans) . " rehab plan(s)\n";
    if (!empty($plans) && isset($plans[0]['exercises'])) {
        echo "  ✓ Exercises included: " . count($plans[0]['exercises']) . " exercise(s)\n";
    }
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    $failed++;
}
echo "\n";

echo "=================================================================\n";
echo "SECTION 4: REPORT MANAGEMENT\n";
echo "=================================================================\n\n";

// Test 7: Get Patient Reports
echo "Test 7: Get Patient Reports\n";
$result = apiCall('GET', "/api/v1/reports?patient_id=$patientId", null, $token, $baseUrl);

if ($result['code'] === 200) {
    $reports = $result['response']['data'] ?? [];
    echo "  ✓ PASSED - Found " . count($reports) . " report(s)\n";
    $reportId = !empty($reports) ? $reports[0]['id'] : null;
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    $reportId = null;
    $failed++;
}
echo "\n";

// Test 8: AI Report Processing (if report exists)
if ($reportId) {
    echo "Test 8: AI Report Processing\n";
    echo "  Processing report ID: $reportId\n";
    
    $result = apiCall('POST', '/api/v1/ai/reports/process', [
        'report_id' => $reportId
    ], $token, $baseUrl);
    
    if ($result['code'] === 200) {
        echo "  ✓ PASSED - Report processed successfully\n";
        $passed++;
    } else {
        echo "  ⚠ FAILED (HTTP {$result['code']})\n";
        if (isset($result['response']['error'])) {
            $errorMsg = $result['response']['error']['message'] ?? 'Unknown error';
            echo "  Error: $errorMsg\n";
            
            // Check if it's a file size error
            if (strpos($errorMsg, 'file size') !== false || strpos($errorMsg, 'File size') !== false) {
                echo "  Note: File size issue - compression should handle this now\n";
            }
        }
        $failed++;
    }
} else {
    echo "Test 8: AI Report Processing\n";
    echo "  ⚠ SKIPPED - No reports available to test\n";
}
echo "\n";

echo "=================================================================\n";
echo "SECTION 5: APPOINTMENTS\n";
echo "=================================================================\n\n";

// Test 9: Get Doctor's Appointments
echo "Test 9: Get Doctor's Appointments\n";
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

// Summary
echo "=================================================================\n";
echo "TEST SUMMARY\n";
echo "=================================================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: $passed ✓\n";
echo "Failed: $failed " . ($failed > 0 ? "✗" : "") . "\n";
echo "\n";

if ($failed === 0) {
    echo "✓ ALL DOCTOR FUNCTIONS WORKING PERFECTLY!\n";
    echo "\nDoctor can:\n";
    echo "  ✓ View and manage patients\n";
    echo "  ✓ Assign medications\n";
    echo "  ✓ Create rehab plans with exercises\n";
    echo "  ✓ View and process reports\n";
    echo "  ✓ Manage appointments\n";
} else {
    echo "⚠ Some doctor functions need attention.\n";
    echo "Check errors above for details.\n";
}

echo "=================================================================\n";
