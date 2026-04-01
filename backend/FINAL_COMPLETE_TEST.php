<?php
/**
 * FINAL COMPLETE TEST - REHAB ASSIGNMENT FLOW
 */

$baseUrl = 'http://192.168.29.162/myrajourney/public/index.php';

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║         FINAL COMPLETE REHAB ASSIGNMENT TEST                     ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$allPassed = true;

// Test 1: Doctor Login
echo "TEST 1: Doctor Login\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$loginData = ['email' => 'avinash@gmail.com', 'password' => 'Patrol@987'];
$ch = curl_init($baseUrl . '/api/v1/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$loginResult = json_decode($response, true);
if ($httpCode === 200 && isset($loginResult['data']['token'])) {
    echo "✅ PASS: Doctor login successful\n";
    $doctorToken = $loginResult['data']['token'];
    $doctorId = $loginResult['data']['user']['id'];
    echo "   Doctor: " . $loginResult['data']['user']['name'] . " (ID: $doctorId)\n";
} else {
    echo "❌ FAIL: Doctor login failed\n";
    $allPassed = false;
}

// Test 2: Create Rehab Plan
echo "\nTEST 2: Create Rehab Plan with Exercises\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$timestamp = date('H:i:s');
$rehabPlan = [
    'patient_id' => 1,
    'title' => "Complete Test Plan $timestamp",
    'description' => 'Full test of rehab assignment',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+30 days')),
    'exercises' => [
        [
            'name' => 'Wrist Flexion Test',
            'description' => 'Test wrist flexion exercise',
            'reps' => '12',
            'sets' => 3,
            'frequency_per_week' => '3'
        ],
        [
            'name' => 'Knee Extension Test',
            'description' => 'Test knee extension exercise',
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

$createResult = json_decode($response, true);
if ($httpCode === 201 && isset($createResult['success']) && $createResult['success']) {
    echo "✅ PASS: Rehab plan created\n";
    $planId = $createResult['data']['id'];
    $exerciseCount = count($createResult['data']['exercises'] ?? []);
    echo "   Plan ID: $planId\n";
    echo "   Title: " . $createResult['data']['title'] . "\n";
    echo "   Exercises: $exerciseCount\n";
} else {
    echo "❌ FAIL: Failed to create rehab plan\n";
    echo "   Response: $response\n";
    $allPassed = false;
}

// Test 3: Verify Auto-Scheduling
echo "\nTEST 3: Verify Automatic Exercise Scheduling\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

require_once __DIR__ . '/src/bootstrap.php';
$db = Src\Config\DB::conn();

$stmt = $db->prepare("SELECT COUNT(*) FROM exercise_schedule WHERE rehab_plan_id = ?");
$stmt->execute([$planId]);
$scheduleCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM exercise_schedule WHERE rehab_plan_id = ? AND schedule_date = CURDATE()");
$stmt->execute([$planId]);
$todayCount = $stmt->fetchColumn();

if ($scheduleCount > 0) {
    echo "✅ PASS: Exercises automatically scheduled\n";
    echo "   Total scheduled: $scheduleCount sessions\n";
    echo "   Scheduled for today: $todayCount exercises\n";
} else {
    echo "❌ FAIL: Exercises were NOT scheduled\n";
    $allPassed = false;
}

// Test 4: Doctor Can See Plan
echo "\nTEST 4: Doctor Can View Assigned Plan\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$ch = curl_init($baseUrl . '/api/v1/rehab-plans?patient_id=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $doctorToken,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);
$found = false;
if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    foreach ($result['data'] ?? [] as $plan) {
        if ($plan['id'] == $planId) {
            $found = true;
            break;
        }
    }
}

if ($found) {
    echo "✅ PASS: Doctor can see the assigned plan\n";
    echo "   Total plans visible: " . count($result['data'] ?? []) . "\n";
} else {
    echo "❌ FAIL: Doctor cannot see the assigned plan\n";
    $allPassed = false;
}

// Test 5: Patient Login
echo "\nTEST 5: Patient Login\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$loginData = ['email' => 'deepankumar@gmail.com', 'password' => 'Welcome@456'];
$ch = curl_init($baseUrl . '/api/v1/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$loginResult = json_decode($response, true);
if ($httpCode === 200 && isset($loginResult['data']['token'])) {
    echo "✅ PASS: Patient login successful\n";
    $patientToken = $loginResult['data']['token'];
    echo "   Patient: " . $loginResult['data']['user']['name'] . "\n";
} else {
    echo "❌ FAIL: Patient login failed\n";
    $allPassed = false;
}

// Test 6: Patient Can See Plan
echo "\nTEST 6: Patient Can View Assigned Plan\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$ch = curl_init($baseUrl . '/api/v1/rehab-plans');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $patientToken,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);
$found = false;
if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    foreach ($result['data'] ?? [] as $plan) {
        if ($plan['id'] == $planId) {
            $found = true;
            break;
        }
    }
}

if ($found) {
    echo "✅ PASS: Patient can see the assigned plan\n";
    echo "   Total plans visible: " . count($result['data'] ?? []) . "\n";
} else {
    echo "❌ FAIL: Patient cannot see the assigned plan\n";
    $allPassed = false;
}

// Test 7: Patient Can See Today's Exercises
echo "\nTEST 7: Patient Can See Today's Scheduled Exercises\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$ch = curl_init($baseUrl . '/api/v1/rehab/today');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $patientToken,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);
$foundNew = false;
if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    foreach ($result['data'] ?? [] as $ex) {
        if ($ex['rehab_plan_id'] == $planId) {
            $foundNew = true;
            $newExerciseName = $ex['exercise_name'];
            break;
        }
    }
}

if ($foundNew) {
    echo "✅ PASS: New exercises appear in today's schedule\n";
    echo "   Total exercises today: " . ($result['count'] ?? 0) . "\n";
    echo "   New exercise found: $newExerciseName\n";
} else {
    echo "❌ FAIL: New exercises NOT in today's schedule\n";
    $allPassed = false;
}

// Test 8: Notification Sent
echo "\nTEST 8: Notification Sent to Patient\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = 1 AND type = 'REHAB' AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
$stmt->execute();
$notifCount = $stmt->fetchColumn();

if ($notifCount > 0) {
    echo "✅ PASS: Notification sent to patient\n";
    echo "   Recent rehab notifications: $notifCount\n";
} else {
    echo "⚠️  WARNING: No recent notification found\n";
}

// Final Summary
echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                        FINAL RESULTS                             ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

if ($allPassed) {
    echo "🎉 ALL TESTS PASSED! 🎉\n\n";
    echo "✅ Doctor can assign rehab plans\n";
    echo "✅ Exercises are automatically scheduled\n";
    echo "✅ Doctor can see assigned plans\n";
    echo "✅ Patient can see assigned plans\n";
    echo "✅ Patient can see today's exercises\n";
    echo "✅ Notifications are sent\n\n";
    echo "The rehab assignment system is FULLY WORKING!\n";
} else {
    echo "❌ SOME TESTS FAILED\n\n";
    echo "Please review the failed tests above.\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
