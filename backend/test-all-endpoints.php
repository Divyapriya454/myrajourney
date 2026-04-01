<?php
/**
 * Comprehensive API Endpoint Test
 */

echo "=================================================================\n";
echo "COMPREHENSIVE API ENDPOINT TEST\n";
echo "=================================================================\n\n";

$baseUrl = "http://localhost:8000";
$token = null;
$userId = null;

// Helper function to make API calls
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
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
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

$passed = 0;
$failed = 0;

// Test 1: Login
echo "Test 1: Login (POST /api/v1/auth/login)\n";
$result = apiCall('POST', '/api/v1/auth/login', [
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
], null, $baseUrl);

if ($result['code'] === 200 && isset($result['response']['success']) && $result['response']['success']) {
    echo "  ✓ PASSED (HTTP {$result['code']})\n";
    $token = $result['response']['data']['token'] ?? null;
    $userId = $result['response']['data']['user']['id'] ?? null;
    echo "  Token: " . substr($token, 0, 20) . "...\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    echo "  Error: " . ($result['response']['error']['message'] ?? 'Unknown') . "\n";
    $failed++;
}
echo "\n";

if (!$token) {
    echo "Cannot continue without authentication token.\n";
    exit(1);
}

// Test 2: Patient Overview
echo "Test 2: Patient Overview (GET /api/v1/patients/me/overview)\n";
$result = apiCall('GET', '/api/v1/patients/me/overview', null, $token, $baseUrl);

if ($result['code'] === 200) {
    echo "  ✓ PASSED (HTTP {$result['code']})\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    if (isset($result['raw'])) {
        echo "  Error: " . substr($result['raw'], 0, 200) . "\n";
    }
    $failed++;
}
echo "\n";

// Test 3: Appointments
echo "Test 3: Get Appointments (GET /api/v1/appointments)\n";
$result = apiCall('GET', '/api/v1/appointments', null, $token, $baseUrl);

if ($result['code'] === 200) {
    echo "  ✓ PASSED (HTTP {$result['code']})\n";
    $count = isset($result['response']['data']) ? count($result['response']['data']) : 0;
    echo "  Appointments: $count\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    if (isset($result['raw'])) {
        echo "  Error: " . substr($result['raw'], 0, 200) . "\n";
    }
    $failed++;
}
echo "\n";

// Test 4: Medications
echo "Test 4: Get Medications (GET /api/v1/patient-medications)\n";
$result = apiCall('GET', '/api/v1/patient-medications', null, $token, $baseUrl);

if ($result['code'] === 200) {
    echo "  ✓ PASSED (HTTP {$result['code']})\n";
    $count = isset($result['response']['data']) ? count($result['response']['data']) : 0;
    echo "  Medications: $count\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    if (isset($result['raw'])) {
        echo "  Error: " . substr($result['raw'], 0, 200) . "\n";
    }
    $failed++;
}
echo "\n";

// Test 5: Reports
echo "Test 5: Get Reports (GET /api/v1/reports)\n";
$result = apiCall('GET', '/api/v1/reports', null, $token, $baseUrl);

if ($result['code'] === 200) {
    echo "  ✓ PASSED (HTTP {$result['code']})\n";
    $count = isset($result['response']['data']) ? count($result['response']['data']) : 0;
    echo "  Reports: $count\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    if (isset($result['raw'])) {
        echo "  Error: " . substr($result['raw'], 0, 200) . "\n";
    }
    $failed++;
}
echo "\n";

// Test 6: Notifications
echo "Test 6: Get Notifications (GET /api/v1/notifications)\n";
$result = apiCall('GET', '/api/v1/notifications', null, $token, $baseUrl);

if ($result['code'] === 200) {
    echo "  ✓ PASSED (HTTP {$result['code']})\n";
    $count = isset($result['response']['data']) ? count($result['response']['data']) : 0;
    echo "  Notifications: $count\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    if (isset($result['raw'])) {
        echo "  Error: " . substr($result['raw'], 0, 200) . "\n";
    }
    $failed++;
}
echo "\n";

// Test 7: Symptoms
echo "Test 7: Get Symptoms (GET /api/v1/symptoms)\n";
$result = apiCall('GET', '/api/v1/symptoms', null, $token, $baseUrl);

if ($result['code'] === 200) {
    echo "  ✓ PASSED (HTTP {$result['code']})\n";
    $count = isset($result['response']['data']) ? count($result['response']['data']) : 0;
    echo "  Symptoms: $count\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    if (isset($result['raw'])) {
        echo "  Error: " . substr($result['raw'], 0, 200) . "\n";
    }
    $failed++;
}
echo "\n";

// Test 8: Education Content
echo "Test 8: Get Education Content (GET /api/v1/education/articles)\n";
$result = apiCall('GET', '/api/v1/education/articles', null, $token, $baseUrl);

if ($result['code'] === 200) {
    echo "  ✓ PASSED (HTTP {$result['code']})\n";
    $count = isset($result['response']['data']) ? count($result['response']['data']) : 0;
    echo "  Articles: $count\n";
    $passed++;
} else {
    echo "  ✗ FAILED (HTTP {$result['code']})\n";
    if (isset($result['raw'])) {
        echo "  Error: " . substr($result['raw'], 0, 200) . "\n";
    }
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
    echo "✓ ALL TESTS PASSED!\n";
    echo "The backend is ready for Android app testing.\n";
} else {
    echo "⚠ Some tests failed. Check errors above.\n";
}

echo "=================================================================\n";
