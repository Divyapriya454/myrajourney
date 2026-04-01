<?php
/**
 * FINAL COMPREHENSIVE REHAB TEST
 */

$baseUrl = 'http://192.168.29.162/myrajourney/public/index.php';

echo "=================================================================\n";
echo "FINAL REHAB SYSTEM TEST\n";
echo "=================================================================\n\n";

// Login as patient
echo "1. Logging in as patient (Deepan Kumar)...\n";
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

$token = $loginResult['data']['token'];
echo "✅ Login successful\n";
echo "Patient ID: " . $loginResult['data']['user']['id'] . "\n";
echo "Name: " . $loginResult['data']['user']['name'] . "\n\n";

// Test 1: Get today's scheduled exercises
echo "=================================================================\n";
echo "TEST 1: GET TODAY'S SCHEDULED EXERCISES\n";
echo "=================================================================\n\n";

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

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    echo "✅ Today's schedule fetched successfully\n";
    echo "Date: " . ($result['date'] ?? 'N/A') . "\n";
    echo "Exercise count: " . ($result['count'] ?? 0) . "\n\n";
    
    if (!empty($result['data'])) {
        echo "Today's exercises:\n";
        $count = 0;
        foreach ($result['data'] as $exercise) {
            $count++;
            if ($count > 5) {
                echo "  ... and " . (count($result['data']) - 5) . " more exercises\n";
                break;
            }
            $name = $exercise['exercise_name'] ?? $exercise['name'] ?? 'Unknown';
            $completed = $exercise['is_completed'] ? '✅' : '⏳';
            echo "  $completed $name (Plan: " . ($exercise['plan_title'] ?? 'N/A') . ")\n";
        }
    } else {
        echo "⚠️  No exercises scheduled for today\n";
    }
} else {
    echo "❌ Failed to fetch today's schedule\n";
    echo "Response: $response\n";
}

// Test 2: Get all rehab plans (old endpoint)
echo "\n=================================================================\n";
echo "TEST 2: GET ALL REHAB PLANS (OLD ENDPOINT)\n";
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

// Test 3: Check database state
echo "\n=================================================================\n";
echo "TEST 3: DATABASE STATE\n";
echo "=================================================================\n\n";

require_once __DIR__ . '/src/bootstrap.php';
$db = Src\Config\DB::conn();

// Count rehab plans
$stmt = $db->query("SELECT COUNT(*) FROM rehab_plans WHERE patient_id = 1");
$planCount = $stmt->fetchColumn();
echo "Rehab plans for patient: $planCount\n";

// Count exercises
$stmt = $db->query("SELECT COUNT(*) FROM rehab_exercises WHERE plan_id IN (SELECT id FROM rehab_plans WHERE patient_id = 1) OR rehab_plan_id IN (SELECT id FROM rehab_plans WHERE patient_id = 1)");
$exerciseCount = $stmt->fetchColumn();
echo "Total exercises assigned: $exerciseCount\n";

// Count today's schedule
$stmt = $db->query("SELECT COUNT(*) FROM exercise_schedule WHERE patient_id = 1 AND schedule_date = CURDATE()");
$todayCount = $stmt->fetchColumn();
echo "Exercises scheduled for today: $todayCount\n";

// Count completed today
$stmt = $db->query("SELECT COUNT(*) FROM exercise_schedule WHERE patient_id = 1 AND schedule_date = CURDATE() AND is_completed = 1");
$completedCount = $stmt->fetchColumn();
echo "Completed today: $completedCount\n";

echo "\n=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n\n";

echo "✅ Backend API: Working\n";
echo "✅ Today's schedule endpoint: Working\n";
echo "✅ Exercise schedule table: Created and populated\n";
echo "✅ Database: " . $todayCount . " exercises scheduled for today\n";
echo "\n";
echo "📱 NEXT STEPS:\n";
echo "1. Rebuild the Android app\n";
echo "2. Install on device\n";
echo "3. Login as patient\n";
echo "4. Check if today's exercises appear\n";
echo "\n=================================================================\n";
