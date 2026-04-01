<?php
echo "======================================================================\n";
echo "     FINAL COMPREHENSIVE TEST - ALL FEATURES\n";
echo "======================================================================\n\n";

$baseUrl = 'http://192.168.29.162:8000/api/v1';
$passed = 0;
$failed = 0;

function test($name, $condition, &$passed, &$failed) {
    if ($condition) {
        echo "✓ $name\n";
        $passed++;
    } else {
        echo "✗ $name\n";
        $failed++;
    }
}

// Test 1: Patient Login
echo "1. PATIENT LOGIN\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);
$result = json_decode($response, true);
test("Patient login successful", $result['success'] ?? false, $passed, $failed);
$patientToken = $result['data']['token'] ?? '';
$patientId = $result['data']['user']['id'] ?? 0;

// Test 2: Doctor Login
echo "\n2. DOCTOR LOGIN\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'doctor@test.com',
    'password' => 'Doctor@123'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);
$result = json_decode($response, true);
test("Doctor login successful", $result['success'] ?? false, $passed, $failed);
$doctorToken = $result['data']['token'] ?? '';

// Test 3: Admin Login
echo "\n3. ADMIN LOGIN\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'testadmin@test.com',
    'password' => 'Admin@123'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);
$result = json_decode($response, true);
test("Admin login successful", $result['success'] ?? false, $passed, $failed);
$adminToken = $result['data']['token'] ?? '';

// Test 4: Patient can view medications
echo "\n4. PATIENT VIEW MEDICATIONS\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/patient-medications');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $patientToken]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$result = json_decode($response, true);
test("Patient can view medications", $httpCode == 200 && isset($result['data']), $passed, $failed);
$medCount = isset($result['data']) ? count($result['data']) : 0;
echo "  Medications found: $medCount\n";

// Test 5: Patient can view rehab plans
echo "\n5. PATIENT VIEW REHAB PLANS\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/rehab-plans');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $patientToken]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$result = json_decode($response, true);
test("Patient can view rehab plans", $httpCode == 200 && isset($result['data']), $passed, $failed);
$rehabCount = isset($result['data']) ? count($result['data']) : 0;
echo "  Rehab plans found: $rehabCount\n";

// Test 6: Patient can view reports
echo "\n6. PATIENT VIEW REPORTS\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/reports');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $patientToken]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$result = json_decode($response, true);
test("Patient can view reports", $httpCode == 200 && isset($result['data']), $passed, $failed);
$reportCount = isset($result['data']) ? count($result['data']) : 0;
echo "  Reports found: $reportCount\n";

// Test 7: Doctor can assign medication
echo "\n7. DOCTOR ASSIGN MEDICATION\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/patient-medications');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'patient_id' => $patientId,
    'medication_name' => 'Final Test Med ' . time(),
    'dosage' => '20mg',
    'frequency' => 'Twice daily',
    'instructions' => 'Take after meals',
    'is_morning' => 1,
    'is_night' => 1,
    'food_relation' => 'After food',
    'start_date' => date('Y-m-d')
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $doctorToken
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$result = json_decode($response, true);
test("Doctor can assign medication", ($httpCode == 200 || $httpCode == 201) && ($result['success'] ?? false), $passed, $failed);

// Test 8: Doctor can create rehab plan
echo "\n8. DOCTOR CREATE REHAB PLAN\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/rehab-plans');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'patient_id' => $patientId,
    'title' => 'Final Test Rehab ' . time(),
    'description' => 'Comprehensive physical therapy',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+30 days')),
    'exercises' => [
        [
            'name' => 'Arm Stretch',
            'description' => 'Stretch arms overhead',
            'reps' => 10,
            'sets' => 3,
            'frequency_per_week' => '5'
        ]
    ]
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $doctorToken
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$result = json_decode($response, true);
test("Doctor can create rehab plan", ($httpCode == 200 || $httpCode == 201) && ($result['success'] ?? false), $passed, $failed);
if (!($result['success'] ?? false)) {
    echo "  Error: HTTP $httpCode - " . substr($response, 0, 200) . "\n";
}

// Test 9: Patient can save symptoms
echo "\n9. PATIENT SAVE SYMPTOMS\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/symptoms');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'date' => date('Y-m-d'),
    'pain_level' => 5,
    'stiffness_level' => 4,
    'fatigue_level' => 3,
    'joint_count' => 2,
    'notes' => 'Feeling better today'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $patientToken
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$result = json_decode($response, true);
test("Patient can save symptoms", ($httpCode == 200 || $httpCode == 201) && ($result['success'] ?? false), $passed, $failed);

// Test 10: Patient can save health metrics
echo "\n10. PATIENT SAVE HEALTH METRICS\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/health-metrics');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'metric_type' => 'WEIGHT',
    'value' => 70.5,
    'unit' => 'kg',
    'recorded_at' => date('Y-m-d H:i:s')
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $patientToken
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$result = json_decode($response, true);
test("Patient can save health metrics", ($httpCode == 200 || $httpCode == 201) && ($result['success'] ?? false), $passed, $failed);
if (!($result['success'] ?? false)) {
    echo "  Error: HTTP $httpCode - " . substr($response, 0, 200) . "\n";
}

echo "\n======================================================================\n";
echo "FINAL RESULTS\n";
echo "======================================================================\n";
echo "✓ Passed: $passed\n";
echo "✗ Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";
$percentage = ($passed + $failed) > 0 ? round(($passed / ($passed + $failed)) * 100, 2) : 0;
echo "Success Rate: $percentage%\n";

if ($failed == 0) {
    echo "\n🎉 ALL TESTS PASSED! APP IS 100% WORKING!\n";
} else {
    echo "\n⚠ Some tests failed. Please review the errors above.\n";
}
echo "======================================================================\n";
