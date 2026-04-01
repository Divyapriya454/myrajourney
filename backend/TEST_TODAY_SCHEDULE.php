<?php
/**
 * TEST TODAY'S SCHEDULE ENDPOINT
 */

$baseUrl = 'http://192.168.29.162/myrajourney/public/index.php';

echo "=================================================================\n";
echo "TESTING TODAY'S SCHEDULE ENDPOINT\n";
echo "=================================================================\n\n";

// Login as patient
echo "1. Logging in as patient...\n";
$loginData = [
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
];

$ch = curl_init($baseUrl . '/api/v1/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$loginResult = json_decode($response, true);

if ($httpCode !== 200 || !isset($loginResult['data']['token'])) {
    echo "❌ Login failed\n";
    echo "Response: $response\n";
    exit(1);
}

$token = $loginResult['data']['token'];
echo "✅ Login successful\n";
echo "Token: " . substr($token, 0, 20) . "...\n\n";

// Test today's schedule endpoint
echo "2. Fetching today's scheduled exercises...\n";
$ch = curl_init($baseUrl . '/api/v1/rehab/today');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response size: " . strlen($response) . " bytes\n\n";

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    echo "✅ Today's schedule fetched successfully\n";
    echo "Date: " . ($result['date'] ?? 'N/A') . "\n";
    echo "Exercise count: " . ($result['count'] ?? 0) . "\n\n";
    
    if (!empty($result['data'])) {
        echo "Exercises scheduled for today:\n";
        foreach ($result['data'] as $exercise) {
            $name = $exercise['exercise_name'] ?? $exercise['name'] ?? 'Unknown';
            $completed = $exercise['is_completed'] ? '✅' : '⏳';
            echo "  $completed $name\n";
            echo "     Plan: " . ($exercise['plan_title'] ?? 'N/A') . "\n";
            echo "     Reps: " . ($exercise['reps'] ?? $exercise['repetitions'] ?? 'N/A') . "\n";
            echo "     Sets: " . ($exercise['sets'] ?? 'N/A') . "\n";
            echo "     Completed: " . ($exercise['is_completed'] ? 'Yes' : 'No') . "\n\n";
        }
    } else {
        echo "No exercises scheduled for today\n";
    }
} else {
    echo "❌ Failed to fetch today's schedule\n";
    echo "Response: $response\n";
}

echo "\n=================================================================\n";
echo "3. Testing old endpoint for comparison...\n";
echo "=================================================================\n\n";

$ch = curl_init($baseUrl . '/api/v1/rehab-plans');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response size: " . strlen($response) . " bytes\n\n";

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    echo "✅ Rehab plans fetched successfully\n";
    echo "Plans count: " . count($result['data'] ?? []) . "\n";
    
    $totalExercises = 0;
    foreach ($result['data'] ?? [] as $plan) {
        $totalExercises += count($plan['exercises'] ?? []);
    }
    echo "Total exercises across all plans: $totalExercises\n";
} else {
    echo "❌ Failed to fetch rehab plans\n";
}

echo "\n=================================================================\n";
echo "COMPLETE\n";
echo "=================================================================\n";
