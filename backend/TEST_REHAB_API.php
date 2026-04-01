<?php
/**
 * TEST REHAB API - Simulate actual Android app requests
 */

$baseUrl = 'http://localhost:8000';

echo "=================================================================\n";
echo "TESTING REHAB API ENDPOINTS\n";
echo "=================================================================\n\n";

// Helper function
function apiRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['code' => 0, 'error' => $error, 'body' => null];
    }
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true),
        'raw' => $response
    ];
}

// Test 1: Login as doctor
echo "TEST 1: Doctor Login\n";
$response = apiRequest("$baseUrl/api/v1/auth/login", 'POST', [
    'email' => 'avinash@gmail.com',
    'password' => 'Patrol@987'
]);

if ($response['code'] === 200 && isset($response['body']['data']['token'])) {
    $doctorToken = $response['body']['data']['token'];
    $doctorId = $response['body']['data']['user']['id'];
    echo "  ✅ Doctor logged in (ID: $doctorId)\n";
} else {
    echo "  ❌ Login failed - Code: {$response['code']}\n";
    echo "  Error: " . ($response['error'] ?? json_encode($response['body'])) . "\n";
    exit(1);
}

// Test 2: Login as patient
echo "\nTEST 2: Patient Login\n";
$response = apiRequest("$baseUrl/api/v1/auth/login", 'POST', [
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
]);

if ($response['code'] === 200 && isset($response['body']['data']['token'])) {
    $patientToken = $response['body']['data']['token'];
    $patientId = $response['body']['data']['user']['id'];
    echo "  ✅ Patient logged in (ID: $patientId)\n";
} else {
    echo "  ❌ Login failed\n";
    exit(1);
}

// Test 3: Create rehab plan
echo "\nTEST 3: Create Rehab Plan\n";
$rehabData = [
    'patient_id' => $patientId,
    'title' => 'API Test Rehab Plan ' . date('H:i:s'),
    'description' => 'Testing rehab plan creation via API',
    'exercises' => [
        [
            'name' => 'Wrist Flexion',
            'description' => 'Gentle wrist flexion exercise',
            'reps' => 10,
            'sets' => 3,
            'frequency_per_week' => 5
        ],
        [
            'name' => 'Knee Extension',
            'description' => 'Knee extension exercise',
            'reps' => 15,
            'sets' => 2,
            'frequency_per_week' => 3
        ]
    ]
];

$response = apiRequest("$baseUrl/api/v1/rehab-plans", 'POST', $rehabData, $doctorToken);

if ($response['code'] === 201 && isset($response['body']['data']['id'])) {
    $planId = $response['body']['data']['id'];
    echo "  ✅ Rehab plan created (ID: $planId)\n";
    
    // Check if exercises were added
    if (isset($response['body']['data']['exercises'])) {
        $exerciseCount = count($response['body']['data']['exercises']);
        echo "  ✅ Plan includes $exerciseCount exercise(s)\n";
    }
} else {
    echo "  ❌ Failed to create rehab plan\n";
    echo "  Code: {$response['code']}\n";
    echo "  Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n";
}

// Test 4: Get rehab plans (patient)
echo "\nTEST 4: Get Rehab Plans (Patient)\n";
$response = apiRequest("$baseUrl/api/v1/rehab-plans", 'GET', null, $patientToken);

if ($response['code'] === 200 && isset($response['body']['data'])) {
    $planCount = count($response['body']['data']);
    echo "  ✅ Retrieved $planCount rehab plan(s)\n";
    
    // Check if plans have exercises
    $hasExercises = false;
    foreach ($response['body']['data'] as $plan) {
        if (isset($plan['exercises']) && count($plan['exercises']) > 0) {
            $hasExercises = true;
            break;
        }
    }
    
    if ($hasExercises) {
        echo "  ✅ Plans include exercises\n";
    } else {
        echo "  ⚠️  Plans don't include exercises\n";
    }
} else {
    echo "  ❌ Failed to retrieve plans\n";
    echo "  Code: {$response['code']}\n";
}

echo "\n=================================================================\n";
echo "REHAB API TEST COMPLETE\n";
echo "=================================================================\n";
