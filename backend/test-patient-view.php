<?php
echo "======================================================================\n";
echo "     TESTING PATIENT VIEW OF MEDICATIONS & REHAB\n";
echo "======================================================================\n\n";

$baseUrl = 'http://192.168.29.162:8000/api/v1';

// Login as patient
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
if (!$result['success']) {
    die("✗ Login failed!\n");
}

$patientToken = $result['data']['token'];
$patientId = $result['data']['user']['id'];
echo "✓ Patient logged in (ID: $patientId)\n\n";

// Test 1: Get Medications
echo "1. GET PATIENT MEDICATIONS\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/patient-medications');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $patientToken]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Raw response: " . substr($response, 0, 500) . "\n";
$result = json_decode($response, true);
if ($httpCode == 200 && isset($result['data'])) {
    $medCount = count($result['data']);
    echo "✓ Found $medCount medication(s)\n";
    if ($medCount > 0) {
        foreach ($result['data'] as $med) {
            echo "  - " . ($med['name'] ?? $med['medication_name']) . " (" . ($med['dosage'] ?? 'no dosage') . ")\n";
            echo "    Instructions: " . ($med['instructions'] ?? 'none') . "\n";
            echo "    Timing: Morning=" . ($med['is_morning'] ? 'Yes' : 'No') . 
                 ", Afternoon=" . ($med['is_afternoon'] ? 'Yes' : 'No') . 
                 ", Night=" . ($med['is_night'] ? 'Yes' : 'No') . "\n";
        }
    }
} else {
    echo "✗ Failed to get medications\n";
}

// Test 2: Get Rehab Plans
echo "\n2. GET REHAB PLANS\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/rehab-plans');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $patientToken]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$result = json_decode($response, true);
if ($httpCode == 200 && isset($result['data'])) {
    $rehabCount = count($result['data']);
    echo "✓ Found $rehabCount rehab plan(s)\n";
    if ($rehabCount > 0) {
        foreach ($result['data'] as $plan) {
            echo "  - " . $plan['title'] . "\n";
            echo "    Description: " . ($plan['description'] ?? 'none') . "\n";
            echo "    Status: " . ($plan['status'] ?? 'unknown') . "\n";
            $exerciseCount = isset($plan['exercises']) ? count($plan['exercises']) : 0;
            echo "    Exercises: $exerciseCount\n";
        }
    }
} else {
    echo "✗ Failed to get rehab plans\n";
}

// Test 3: Get Reports
echo "\n3. GET MEDICAL REPORTS\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/reports');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $patientToken]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$result = json_decode($response, true);
if ($httpCode == 200 && isset($result['data'])) {
    $reportCount = count($result['data']);
    echo "✓ Found $reportCount report(s)\n";
    if ($reportCount > 0) {
        foreach ($result['data'] as $report) {
            echo "  - Report ID: " . $report['id'] . "\n";
            echo "    Type: " . ($report['report_type'] ?? 'unknown') . "\n";
            echo "    Status: " . ($report['status'] ?? 'unknown') . "\n";
            echo "    Uploaded: " . ($report['uploaded_at'] ?? $report['created_at']) . "\n";
        }
    }
} else {
    echo "✗ Failed to get reports\n";
}

echo "\n======================================================================\n";
echo "✅ PATIENT VIEW TEST COMPLETE\n";
echo "======================================================================\n";
