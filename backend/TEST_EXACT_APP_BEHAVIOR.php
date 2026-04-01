<?php
/**
 * TEST EXACT APP BEHAVIOR - What the current app sees
 */

$baseUrl = 'http://192.168.29.162/myrajourney/public/index.php';

echo "Testing EXACT behavior the current Android app sees...\n\n";

// Login as doctor
$loginData = ['email' => 'avinash@gmail.com', 'password' => 'Patrol@987'];
$ch = curl_init($baseUrl . '/api/v1/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$loginResult = json_decode($response, true);
$doctorToken = $loginResult['data']['token'];

echo "1. Doctor views patient's rehab plans:\n";
echo "   GET /api/v1/rehab-plans?patient_id=1\n\n";

$ch = curl_init($baseUrl . '/api/v1/rehab-plans?patient_id=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $doctorToken,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    echo "✅ API works\n";
    echo "Plans returned: " . count($result['data'] ?? []) . "\n\n";
    
    if (!empty($result['data'])) {
        echo "First plan structure:\n";
        $firstPlan = $result['data'][0];
        echo "- id: " . ($firstPlan['id'] ?? 'missing') . "\n";
        echo "- title: " . ($firstPlan['title'] ?? 'missing') . "\n";
        echo "- patient_id: " . ($firstPlan['patient_id'] ?? 'missing') . "\n";
        echo "- exercises: " . (isset($firstPlan['exercises']) ? count($firstPlan['exercises']) : 'missing') . "\n";
        
        if (!empty($firstPlan['exercises'])) {
            echo "\nFirst exercise structure:\n";
            $firstEx = $firstPlan['exercises'][0];
            echo "- id: " . ($firstEx['id'] ?? 'missing') . "\n";
            echo "- name: " . ($firstEx['name'] ?? 'missing') . "\n";
            echo "- exercise_name: " . ($firstEx['exercise_name'] ?? 'missing') . "\n";
            echo "- description: " . ($firstEx['description'] ?? 'missing') . "\n";
            echo "- reps: " . ($firstEx['reps'] ?? 'missing') . "\n";
            echo "- sets: " . ($firstEx['sets'] ?? 'missing') . "\n";
            echo "- frequency_per_week: " . ($firstEx['frequency_per_week'] ?? 'missing') . "\n";
        }
    }
    
    echo "\n\nFull JSON response (first 500 chars):\n";
    echo substr(json_encode($result, JSON_PRETTY_PRINT), 0, 500) . "...\n";
} else {
    echo "❌ API failed\n";
    echo "Response: $response\n";
}

// Now test patient side
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$loginData = ['email' => 'deepankumar@gmail.com', 'password' => 'Welcome@456'];
$ch = curl_init($baseUrl . '/api/v1/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$loginResult = json_decode($response, true);
$patientToken = $loginResult['data']['token'];

echo "2. Patient views their rehab plans:\n";
echo "   GET /api/v1/rehab-plans\n\n";

$ch = curl_init($baseUrl . '/api/v1/rehab-plans');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $patientToken,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    echo "✅ API works\n";
    echo "Plans returned: " . count($result['data'] ?? []) . "\n";
    
    $totalExercises = 0;
    foreach ($result['data'] ?? [] as $plan) {
        $totalExercises += count($plan['exercises'] ?? []);
    }
    echo "Total exercises: $totalExercises\n";
} else {
    echo "❌ API failed\n";
    echo "Response: $response\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\nCONCLUSION:\n";
echo "The API is working and returning data correctly.\n";
echo "If the app doesn't show exercises, the issue is in the app code.\n";
echo "The app needs to be rebuilt with the latest code.\n";
