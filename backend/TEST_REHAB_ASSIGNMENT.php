<?php
/**
 * TEST REHAB ASSIGNMENT FLOW
 */

$baseUrl = 'http://192.168.29.162/myrajourney/public/index.php';

echo "=================================================================\n";
echo "TESTING REHAB ASSIGNMENT FLOW\n";
echo "=================================================================\n\n";

// Step 1: Login as doctor
echo "1. Logging in as doctor (Dr. Avinash)...\n";
$loginData = [
    'email' => 'avinash@gmail.com',
    'password' => 'Patrol@987'
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

$doctorToken = $loginResult['data']['token'];
$doctorId = $loginResult['data']['user']['id'];
echo "✅ Login successful\n";
echo "Doctor ID: $doctorId\n";
echo "Name: " . $loginResult['data']['user']['name'] . "\n\n";

// Step 2: Create a new rehab plan
echo "=================================================================\n";
echo "2. Creating new rehab plan for patient...\n";
echo "=================================================================\n\n";

$rehabPlan = [
    'patient_id' => 1, // Deepan Kumar
    'title' => 'Test Rehab Plan ' . date('H:i:s'),
    'description' => 'Testing rehab assignment flow',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+30 days')),
    'exercises' => [
        [
            'name' => 'Test Exercise 1',
            'description' => 'First test exercise',
            'reps' => '10',
            'sets' => 3,
            'frequency_per_week' => '3'
        ],
        [
            'name' => 'Test Exercise 2',
            'description' => 'Second test exercise',
            'reps' => '15',
            'sets' => 2,
            'frequency_per_week' => '5'
        ]
    ]
];

$ch = curl_init($baseUrl . '/api/v1/rehab-plans');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($rehabPlan));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $doctorToken,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

$createResult = json_decode($response, true);

if ($httpCode === 201 && isset($createResult['success']) && $createResult['success']) {
    echo "✅ Rehab plan created successfully\n";
    $planId = $createResult['data']['id'];
    echo "Plan ID: $planId\n";
    echo "Title: " . $createResult['data']['title'] . "\n";
    echo "Exercises: " . count($createResult['data']['exercises'] ?? []) . "\n\n";
} else {
    echo "❌ Failed to create rehab plan\n";
    exit(1);
}

// Step 3: Check if exercises were scheduled
echo "=================================================================\n";
echo "3. Checking if exercises were scheduled...\n";
echo "=================================================================\n\n";

require_once __DIR__ . '/src/bootstrap.php';
$db = Src\Config\DB::conn();

$stmt = $db->prepare("SELECT COUNT(*) FROM exercise_schedule WHERE rehab_plan_id = ?");
$stmt->execute([$planId]);
$scheduleCount = $stmt->fetchColumn();

echo "Exercises in schedule table: $scheduleCount\n";

if ($scheduleCount > 0) {
    echo "✅ Exercises were automatically scheduled\n\n";
} else {
    echo "⚠️  Exercises were NOT automatically scheduled\n";
    echo "Need to manually schedule them\n\n";
}

// Step 4: Get doctor's view of rehab plans
echo "=================================================================\n";
echo "4. Getting doctor's view of rehab plans...\n";
echo "=================================================================\n\n";

$ch = curl_init($baseUrl . '/api/v1/rehab-plans?patient_id=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $doctorToken,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    echo "✅ Doctor can see rehab plans\n";
    echo "Total plans: " . count($result['data'] ?? []) . "\n";
    
    // Find our newly created plan
    $found = false;
    foreach ($result['data'] ?? [] as $plan) {
        if ($plan['id'] == $planId) {
            $found = true;
            echo "✅ Newly created plan found in list\n";
            echo "   Title: " . $plan['title'] . "\n";
            echo "   Exercises: " . count($plan['exercises'] ?? []) . "\n";
            break;
        }
    }
    
    if (!$found) {
        echo "❌ Newly created plan NOT found in list\n";
    }
} else {
    echo "❌ Failed to get rehab plans\n";
    echo "Response: $response\n";
}

// Step 5: Login as patient and check
echo "\n=================================================================\n";
echo "5. Logging in as patient (Deepan Kumar)...\n";
echo "=================================================================\n\n";

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
    exit(1);
}

$patientToken = $loginResult['data']['token'];
echo "✅ Login successful\n\n";

// Step 6: Get patient's view of rehab plans
echo "=================================================================\n";
echo "6. Getting patient's view of rehab plans...\n";
echo "=================================================================\n\n";

$ch = curl_init($baseUrl . '/api/v1/rehab-plans');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $patientToken,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    echo "✅ Patient can see rehab plans\n";
    echo "Total plans: " . count($result['data'] ?? []) . "\n";
    
    // Find our newly created plan
    $found = false;
    foreach ($result['data'] ?? [] as $plan) {
        if ($plan['id'] == $planId) {
            $found = true;
            echo "✅ Newly created plan found in patient's list\n";
            echo "   Title: " . $plan['title'] . "\n";
            echo "   Exercises: " . count($plan['exercises'] ?? []) . "\n";
            break;
        }
    }
    
    if (!$found) {
        echo "❌ Newly created plan NOT found in patient's list\n";
    }
} else {
    echo "❌ Failed to get rehab plans\n";
    echo "Response: $response\n";
}

// Step 7: Get today's schedule
echo "\n=================================================================\n";
echo "7. Getting today's scheduled exercises...\n";
echo "=================================================================\n\n";

$ch = curl_init($baseUrl . '/api/v1/rehab/today');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $patientToken,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    echo "✅ Today's schedule fetched\n";
    echo "Total exercises today: " . ($result['count'] ?? 0) . "\n";
    
    // Check if our new exercises are in today's schedule
    $foundNew = false;
    foreach ($result['data'] ?? [] as $ex) {
        if ($ex['rehab_plan_id'] == $planId) {
            $foundNew = true;
            echo "✅ New exercises found in today's schedule\n";
            echo "   Exercise: " . $ex['exercise_name'] . "\n";
            break;
        }
    }
    
    if (!$foundNew) {
        echo "⚠️  New exercises NOT in today's schedule\n";
        echo "   (They need to be scheduled first)\n";
    }
} else {
    echo "❌ Failed to get today's schedule\n";
}

echo "\n=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n\n";

echo "Test Results:\n";
echo "1. Doctor login: ✅\n";
echo "2. Create rehab plan: " . ($httpCode === 201 ? "✅" : "❌") . "\n";
echo "3. Auto-scheduling: " . ($scheduleCount > 0 ? "✅" : "⚠️") . "\n";
echo "4. Doctor can see plan: " . (isset($found) && $found ? "✅" : "❌") . "\n";
echo "5. Patient login: ✅\n";
echo "6. Patient can see plan: " . (isset($found) && $found ? "✅" : "❌") . "\n";
echo "7. Exercises in today's schedule: " . (isset($foundNew) && $foundNew ? "✅" : "⚠️") . "\n";

echo "\n=================================================================\n";
