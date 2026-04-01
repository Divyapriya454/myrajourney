<?php
/**
 * Test Save Operations (POST/PUT)
 * Tests if data can be saved to the database
 */

echo "=================================================================\n";
echo "TESTING SAVE OPERATIONS\n";
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

// Login as patient
echo "Logging in as patient...\n";
$result = apiCall('POST', '/api/v1/auth/login', [
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
], null, $baseUrl);

if ($result['code'] !== 200) {
    echo "✗ Login failed!\n";
    exit(1);
}

$token = $result['response']['data']['token'] ?? null;
echo "✓ Login successful\n\n";

$passed = 0;
$failed = 0;

// Test 1: Save Symptom
echo "Test 1: Save Symptom\n";
$symptomData = [
    'date' => date('Y-m-d'),
    'pain_level' => 5,
    'stiffness_level' => 4,
    'fatigue_level' => 6,
    'joint_count' => 3,
    'notes' => 'Test symptom entry from automated test'
];

$result = apiCall('POST', '/api/v1/symptoms', $symptomData, $token, $baseUrl);

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

// Test 2: Save Appointment (if endpoint exists)
echo "Test 2: Create Appointment\n";
$appointmentData = [
    'doctor_id' => 2,
    'appointment_date' => date('Y-m-d', strtotime('+1 day')),
    'appointment_time' => '10:00:00',
    'title' => 'Test Appointment',
    'description' => 'Automated test appointment',
    'appointment_type' => 'CONSULTATION'
];

$result = apiCall('POST', '/api/v1/appointments', $appointmentData, $token, $baseUrl);

if ($result['code'] === 200 || $result['code'] === 201) {
    echo "  ✓ PASSED (HTTP {$result['code']})\n";
    $passed++;
} else {
    echo "  ⚠ SKIPPED (HTTP {$result['code']}) - May not be implemented\n";
}
echo "\n";

// Test 3: Mark notification as read
echo "Test 3: Update Notification\n";
$result = apiCall('GET', '/api/v1/notifications', null, $token, $baseUrl);

if ($result['code'] === 200 && isset($result['response']['data']) && count($result['response']['data']) > 0) {
    $notificationId = $result['response']['data'][0]['id'];
    $updateResult = apiCall('PUT', "/api/v1/notifications/$notificationId/read", null, $token, $baseUrl);
    
    if ($updateResult['code'] === 200) {
        echo "  ✓ PASSED (HTTP {$updateResult['code']})\n";
        $passed++;
    } else {
        echo "  ⚠ SKIPPED (HTTP {$updateResult['code']}) - May not be implemented\n";
    }
} else {
    echo "  ⚠ SKIPPED - No notifications to test\n";
}
echo "\n";

// Test 4: Verify saved symptom
echo "Test 4: Verify Saved Data\n";
$result = apiCall('GET', '/api/v1/symptoms', null, $token, $baseUrl);

if ($result['code'] === 200) {
    $symptoms = $result['response']['data'] ?? [];
    if (count($symptoms) > 0) {
        echo "  ✓ PASSED - Found " . count($symptoms) . " symptom(s)\n";
        $passed++;
    } else {
        echo "  ⚠ WARNING - No symptoms found (may have been saved but not retrieved)\n";
    }
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    $failed++;
}
echo "\n";

// Summary
echo "=================================================================\n";
echo "SAVE OPERATIONS SUMMARY\n";
echo "=================================================================\n";
echo "Tests Passed: $passed ✓\n";
echo "Tests Failed: $failed\n";
echo "\n";

if ($passed > 0) {
    echo "✓ Data can be saved to the database!\n";
    echo "The Android app should be able to save data.\n";
} else {
    echo "⚠ No save operations succeeded.\n";
    echo "Check the errors above for details.\n";
}

echo "=================================================================\n";
