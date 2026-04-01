<?php
/**
 * Test ALL Android App Endpoints
 * Tests every endpoint the Android app uses
 */

echo "=================================================================\n";
echo "TESTING ALL ANDROID APP ENDPOINTS\n";
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
$tokens = [];

// Test different user roles
$users = [
    'patient' => ['email' => 'deepankumar@gmail.com', 'password' => 'Welcome@456'],
    'doctor' => ['email' => 'doctor@test.com', 'password' => 'Patrol@987'],
    'admin' => ['email' => 'testadmin@test.com', 'password' => 'AS@Saveetha123']
];

// Login all users
echo "=== AUTHENTICATION ===\n\n";
foreach ($users as $role => $credentials) {
    echo "Login as $role: ";
    $result = apiCall('POST', '/api/v1/auth/login', $credentials, null, $baseUrl);
    
    if ($result['code'] === 200 && isset($result['response']['success']) && $result['response']['success']) {
        echo "✓ PASSED\n";
        $tokens[$role] = $result['response']['data']['token'] ?? null;
        $passed++;
    } else {
        echo "✗ FAILED (HTTP {$result['code']})\n";
        $failed++;
    }
}
echo "\n";

// Patient endpoints
if (isset($tokens['patient'])) {
    echo "=== PATIENT ENDPOINTS ===\n\n";
    
    $patientTests = [
        ['GET', '/api/v1/patients/me/overview', null, 'Patient Overview'],
        ['GET', '/api/v1/appointments', null, 'Get Appointments'],
        ['GET', '/api/v1/patient-medications', null, 'Get Medications'],
        ['GET', '/api/v1/reports', null, 'Get Reports'],
        ['GET', '/api/v1/notifications', null, 'Get Notifications'],
        ['GET', '/api/v1/symptoms', null, 'Get Symptoms'],
        ['GET', '/api/v1/education/articles', null, 'Get Education'],
        ['GET', '/api/v1/crp/measurements', null, 'Get CRP Measurements'],
    ];
    
    foreach ($patientTests as $test) {
        list($method, $endpoint, $data, $name) = $test;
        echo "$name: ";
        $result = apiCall($method, $endpoint, $data, $tokens['patient'], $baseUrl);
        
        if ($result['code'] === 200) {
            echo "✓ PASSED\n";
            $passed++;
        } else {
            echo "✗ FAILED (HTTP {$result['code']})\n";
            $failed++;
        }
    }
    echo "\n";
}

// Doctor endpoints
if (isset($tokens['doctor'])) {
    echo "=== DOCTOR ENDPOINTS ===\n\n";
    
    $doctorTests = [
        ['GET', '/api/v1/patients', null, 'Get Patients List'],
        ['GET', '/api/v1/appointments', null, 'Get Appointments'],
        ['GET', '/api/v1/notifications', null, 'Get Notifications'],
    ];
    
    foreach ($doctorTests as $test) {
        list($method, $endpoint, $data, $name) = $test;
        echo "$name: ";
        $result = apiCall($method, $endpoint, $data, $tokens['doctor'], $baseUrl);
        
        if ($result['code'] === 200) {
            echo "✓ PASSED\n";
            $passed++;
        } else {
            echo "✗ FAILED (HTTP {$result['code']})\n";
            if ($result['code'] === 500) {
                echo "  Error: " . substr($result['raw'], 0, 200) . "\n";
            }
            $failed++;
        }
    }
    echo "\n";
}

// Admin endpoints
if (isset($tokens['admin'])) {
    echo "=== ADMIN ENDPOINTS ===\n\n";
    
    $adminTests = [
        ['GET', '/api/v1/admin/users', null, 'Get All Users'],
        ['GET', '/api/v1/patients', null, 'Get All Patients'],
        ['GET', '/api/v1/doctors', null, 'Get All Doctors'],
        ['GET', '/api/v1/appointments', null, 'Get All Appointments'],
        ['GET', '/api/v1/notifications', null, 'Get Notifications'],
    ];
    
    foreach ($adminTests as $test) {
        list($method, $endpoint, $data, $name) = $test;
        echo "$name: ";
        $result = apiCall($method, $endpoint, $data, $tokens['admin'], $baseUrl);
        
        if ($result['code'] === 200) {
            echo "✓ PASSED\n";
            $passed++;
        } else {
            echo "✗ FAILED (HTTP {$result['code']})\n";
            if ($result['code'] === 500) {
                echo "  Error: " . substr($result['raw'], 0, 200) . "\n";
            }
            $failed++;
        }
    }
    echo "\n";
}

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
    echo "All Android app endpoints are working correctly.\n";
} else {
    echo "⚠ Some tests failed. Check errors above.\n";
    echo "The app may experience issues with failed endpoints.\n";
}

echo "=================================================================\n";
