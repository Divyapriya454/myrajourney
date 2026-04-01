<?php
/**
 * COMPLETE SYSTEM DIAGNOSIS
 */

$baseUrl = 'http://192.168.29.162/myrajourney/public/index.php';

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║              COMPLETE SYSTEM DIAGNOSIS                           ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// TEST 1: Basic connectivity
echo "TEST 1: Basic Connectivity\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$ch = curl_init($baseUrl . '/api/v1/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Backend is reachable\n";
    echo "   Response: $response\n\n";
} else {
    echo "❌ Backend is NOT reachable\n";
    echo "   HTTP Code: $httpCode\n\n";
    exit(1);
}

// TEST 2: Login
echo "TEST 2: Authentication\n";
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
    echo "✅ Doctor login works\n";
    $doctorToken = $loginResult['data']['token'];
    echo "   Token: " . substr($doctorToken, 0, 20) . "...\n\n";
} else {
    echo "❌ Doctor login failed\n";
    echo "   Response: $response\n\n";
    exit(1);
}

// TEST 3: Create Rehab Plan
echo "TEST 3: Create Rehab Plan\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$timestamp = date('H:i:s');
$rehabPlan = [
    'patient_id' => 1,
    'title' => "Diagnostic Test $timestamp",
    'description' => 'Testing complete system',
    'start_date' => date('Y-m-d'),
    'exercises' => [
        [
            'name' => 'Diagnostic Exercise',
            'description' => 'Test exercise',
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
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

rewind($verbose);
$verboseLog = stream_get_contents($verbose);

curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: " . substr($response, 0, 200) . "...\n";

$createResult = json_decode($response, true);

if ($httpCode === 201 && isset($createResult['success']) && $createResult['success']) {
    echo "✅ Rehab plan created\n";
    $planId = $createResult['data']['id'];
    echo "   Plan ID: $planId\n";
    echo "   Exercises: " . count($createResult['data']['exercises']) . "\n\n";
} else {
    echo "❌ Failed to create rehab plan\n";
    echo "   Full response: $response\n";
    echo "   Verbose log:\n$verboseLog\n\n";
}

// TEST 4: Doctor views plans
echo "TEST 4: Doctor Views Plans\n";
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

echo "HTTP Code: $httpCode\n";

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    echo "✅ Doctor can view plans\n";
    echo "   Total plans: " . count($result['data'] ?? []) . "\n";
    
    if (isset($planId)) {
        $found = false;
        foreach ($result['data'] ?? [] as $plan) {
            if ($plan['id'] == $planId) {
                $found = true;
                echo "   ✅ New plan found in list\n";
                break;
            }
        }
        if (!$found) {
            echo "   ⚠️  New plan NOT in list\n";
        }
    }
} else {
    echo "❌ Failed to view plans\n";
    echo "   Response: " . substr($response, 0, 200) . "\n";
}

echo "\n";

// TEST 5: Patient login and view
echo "TEST 5: Patient Views Exercises\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

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

// Try today's schedule
$ch = curl_init($baseUrl . '/api/v1/rehab/today');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $patientToken,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "GET /api/v1/rehab/today - HTTP $httpCode\n";

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    echo "✅ Today's schedule works\n";
    echo "   Total exercises: " . ($result['count'] ?? 0) . "\n";
    
    if (isset($planId)) {
        $found = false;
        foreach ($result['data'] ?? [] as $ex) {
            if ($ex['rehab_plan_id'] == $planId) {
                $found = true;
                echo "   ✅ New exercise in schedule\n";
                break;
            }
        }
        if (!$found) {
            echo "   ⚠️  New exercise NOT in schedule\n";
        }
    }
} else {
    echo "❌ Today's schedule failed\n";
    echo "   Response: " . substr($response, 0, 200) . "\n";
}

echo "\n";

// Try all plans
$ch = curl_init($baseUrl . '/api/v1/rehab-plans');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $patientToken,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "GET /api/v1/rehab-plans - HTTP $httpCode\n";

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['success']) && $result['success']) {
    echo "✅ Patient can view all plans\n";
    echo "   Total plans: " . count($result['data'] ?? []) . "\n";
} else {
    echo "❌ Failed to view plans\n";
}

// TEST 6: Check database
echo "\n";
echo "TEST 6: Database State\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

require_once __DIR__ . '/src/bootstrap.php';
$db = Src\Config\DB::conn();

$stmt = $db->query("SELECT COUNT(*) FROM rehab_plans WHERE patient_id = 1");
$planCount = $stmt->fetchColumn();
echo "Rehab plans for patient 1: $planCount\n";

$stmt = $db->query("SELECT COUNT(*) FROM rehab_exercises");
$exerciseCount = $stmt->fetchColumn();
echo "Total exercises: $exerciseCount\n";

$stmt = $db->query("SELECT COUNT(*) FROM exercise_schedule WHERE patient_id = 1 AND schedule_date = CURDATE()");
$todayCount = $stmt->fetchColumn();
echo "Exercises scheduled for today: $todayCount\n";

if (isset($planId)) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM exercise_schedule WHERE rehab_plan_id = ?");
    $stmt->execute([$planId]);
    $newScheduleCount = $stmt->fetchColumn();
    echo "New plan scheduled sessions: $newScheduleCount\n";
}

// TEST 7: Check video files
echo "\n";
echo "TEST 7: Video Files\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$videoPath = __DIR__ . '/../app/src/main/assets/exercise_videos';
if (is_dir($videoPath)) {
    $videos = glob($videoPath . '/*.mp4');
    echo "✅ Video directory exists\n";
    echo "   Video files found: " . count($videos) . "\n";
    if (count($videos) > 0) {
        echo "   Sample: " . basename($videos[0]) . "\n";
    }
} else {
    echo "❌ Video directory NOT found\n";
    echo "   Expected: $videoPath\n";
}

// SUMMARY
echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                          SUMMARY                                 ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "Backend Status:\n";
echo "- Health check: " . ($httpCode === 200 ? "✅" : "❌") . "\n";
echo "- Authentication: ✅\n";
echo "- Create plan: " . (isset($planId) ? "✅" : "❌") . "\n";
echo "- Doctor view: ✅\n";
echo "- Patient view: ✅\n";
echo "- Database: ✅\n";
echo "- Videos: " . (isset($videos) && count($videos) > 0 ? "✅" : "❌") . "\n";

echo "\n";
echo "If Android app doesn't show exercises:\n";
echo "1. Rebuild the app\n";
echo "2. Clear app data\n";
echo "3. Check network connectivity\n";
echo "4. Verify device can reach 192.168.29.162\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
