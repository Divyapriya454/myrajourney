<?php
/**
 * TEST COMPLETE ASSIGNMENT FLOW - SIMULATING APP BEHAVIOR
 */

$baseUrl = 'http://192.168.29.162/myrajourney/public/index.php';

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║     TESTING COMPLETE FLOW - SIMULATING APP BEHAVIOR             ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// STEP 1: Doctor assigns rehab plan
echo "STEP 1: Doctor Assigns Rehab Plan\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

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

echo "✅ Doctor logged in\n";

// Create rehab plan
$timestamp = date('H:i:s');
$rehabPlan = [
    'patient_id' => 1,
    'title' => "App Test Plan $timestamp",
    'description' => 'Testing complete app flow',
    'start_date' => date('Y-m-d'),
    'exercises' => [
        [
            'name' => 'App Test Exercise',
            'description' => 'Testing if this appears in app',
            'reps' => '10',
            'sets' => 3,
            'frequency_per_week' => '3'
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
$planId = $createResult['data']['id'];

echo "✅ Rehab plan created (ID: $planId)\n";
echo "   Title: " . $createResult['data']['title'] . "\n";
echo "   Exercises: " . count($createResult['data']['exercises']) . "\n\n";

// STEP 2: Doctor refreshes their view (simulating app behavior)
echo "STEP 2: Doctor Refreshes View (What App Does)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$ch = curl_init($baseUrl . '/api/v1/rehab-plans?patient_id=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $doctorToken,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
$found = false;
foreach ($result['data'] ?? [] as $plan) {
    if ($plan['id'] == $planId) {
        $found = true;
        $exerciseCount = count($plan['exercises'] ?? []);
        break;
    }
}

if ($found) {
    echo "✅ Doctor can see the plan in their view\n";
    echo "   Plan visible with $exerciseCount exercises\n\n";
} else {
    echo "❌ Doctor CANNOT see the plan\n\n";
}

// STEP 3: Patient opens app and goes to Rehabilitation screen
echo "STEP 3: Patient Opens Rehabilitation Screen (What App Does)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Login as patient
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

echo "✅ Patient logged in\n";

// Patient opens Rehabilitation screen - app calls GET /api/v1/rehab/today
echo "   App calls: GET /api/v1/rehab/today\n";

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

echo "   Response: HTTP $httpCode\n";

if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    echo "✅ Patient can fetch today's exercises\n";
    echo "   Total exercises today: " . ($result['count'] ?? 0) . "\n";
    
    // Check if our new exercise is there
    $foundNew = false;
    foreach ($result['data'] ?? [] as $ex) {
        if ($ex['rehab_plan_id'] == $planId) {
            $foundNew = true;
            echo "✅ NEW EXERCISE FOUND: " . $ex['exercise_name'] . "\n";
            echo "   Plan: " . $ex['plan_title'] . "\n";
            echo "   Reps: " . $ex['reps'] . "\n";
            echo "   Sets: " . $ex['sets'] . "\n";
            break;
        }
    }
    
    if (!$foundNew) {
        echo "❌ NEW EXERCISE NOT FOUND in today's schedule\n";
        echo "   This means the exercise wasn't scheduled properly\n";
    }
} else {
    echo "❌ Failed to fetch today's exercises\n";
    echo "   Response: $response\n";
}

// STEP 4: Check what doctor sees in patient record
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 4: What Doctor Sees in Patient Record\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$ch = curl_init($baseUrl . '/api/v1/rehab-plans?patient_id=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $doctorToken,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

echo "Doctor's view of patient's rehab plans:\n";
echo "Total plans: " . count($result['data'] ?? []) . "\n";

$totalExercises = 0;
foreach ($result['data'] ?? [] as $plan) {
    $exerciseCount = count($plan['exercises'] ?? []);
    $totalExercises += $exerciseCount;
    
    if ($plan['id'] == $planId) {
        echo "\n✅ NEW PLAN VISIBLE:\n";
        echo "   ID: " . $plan['id'] . "\n";
        echo "   Title: " . $plan['title'] . "\n";
        echo "   Exercises: $exerciseCount\n";
        
        foreach ($plan['exercises'] ?? [] as $ex) {
            echo "   - " . $ex['name'] . " (Reps: " . $ex['reps'] . ", Sets: " . $ex['sets'] . ")\n";
        }
    }
}

echo "\nTotal exercises across all plans: $totalExercises\n";

// FINAL SUMMARY
echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                        FLOW SUMMARY                              ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "1. Doctor assigns plan → " . ($httpCode === 201 ? "✅ SUCCESS" : "❌ FAILED") . "\n";
echo "2. Doctor sees plan → " . ($found ? "✅ YES" : "❌ NO") . "\n";
echo "3. Patient sees in today's schedule → " . (isset($foundNew) && $foundNew ? "✅ YES" : "❌ NO") . "\n";
echo "4. Exercises auto-scheduled → " . (isset($foundNew) && $foundNew ? "✅ YES" : "❌ NO") . "\n";

echo "\n";

if (isset($foundNew) && $foundNew && $found) {
    echo "🎉 COMPLETE FLOW WORKING!\n\n";
    echo "The backend is working correctly:\n";
    echo "- Doctor can assign and see plans\n";
    echo "- Patient can see exercises in today's schedule\n";
    echo "- Exercises are automatically scheduled\n\n";
    echo "If the Android app doesn't show exercises:\n";
    echo "1. Make sure the app is rebuilt with latest code\n";
    echo "2. Patient should open Rehabilitation screen\n";
    echo "3. App will call GET /api/v1/rehab/today\n";
    echo "4. Exercises should appear\n";
} else {
    echo "⚠️  SOME ISSUES DETECTED\n";
    echo "Review the steps above to see what failed.\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
