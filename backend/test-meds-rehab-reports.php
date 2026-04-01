<?php
echo "======================================================================\n";
echo "     TESTING MEDICATIONS, REHAB & REPORTS\n";
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
$result = json_decode($response, true);
if ($httpCode == 200) {
    echo "✓ Medications endpoint working\n";
    $medCount = isset($result['data']) ? count($result['data']) : 0;
    echo "  Medications: $medCount\n";
} else {
    echo "✗ Failed\n";
    echo "  Response: " . substr($response, 0, 300) . "\n";
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
if ($httpCode == 200) {
    echo "✓ Rehab plans endpoint working\n";
    $rehabCount = isset($result['data']) ? count($result['data']) : 0;
    echo "  Rehab Plans: $rehabCount\n";
} else {
    echo "✗ Failed\n";
    echo "  Response: " . substr($response, 0, 300) . "\n";
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
if ($httpCode == 200) {
    echo "✓ Reports endpoint working\n";
    $reportCount = isset($result['data']) ? count($result['data']) : 0;
    echo "  Reports: $reportCount\n";
} else {
    echo "✗ Failed\n";
    echo "  Response: " . substr($response, 0, 300) . "\n";
}

// Test 4: Get Appointments
echo "\n4. GET APPOINTMENTS\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/appointments');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $patientToken]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$result = json_decode($response, true);
if ($httpCode == 200) {
    echo "✓ Appointments endpoint working\n";
    $apptCount = isset($result['data']) ? count($result['data']) : 0;
    echo "  Appointments: $apptCount\n";
} else {
    echo "✗ Failed\n";
    echo "  Response: " . substr($response, 0, 300) . "\n";
}

// Now test as doctor to assign medication
echo "\n5. DOCTOR LOGIN\n";
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
if ($result['success']) {
    $doctorToken = $result['data']['token'];
    echo "✓ Doctor logged in\n";
    
    // Test 6: Assign Medication
    echo "\n6. ASSIGN MEDICATION TO PATIENT\n";
    echo "-------------------------------------------\n";
    $ch = curl_init($baseUrl . '/patient-medications');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'patient_id' => $patientId,
        'medication_name' => 'Test Medication',
        'dosage' => '10mg',
        'frequency' => 'Once daily',
        'instructions' => 'Take with food',
        'start_date' => date('Y-m-d')
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $doctorToken
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    $result = json_decode($response, true);
    if ($httpCode == 200 || $httpCode == 201) {
        if (isset($result['success']) && $result['success']) {
            echo "✓ Medication assigned successfully\n";
        } else {
            echo "⚠ Response: " . json_encode($result) . "\n";
        }
    } else {
        echo "✗ Failed\n";
        echo "  Response: " . substr($response, 0, 300) . "\n";
    }
    
    // Test 7: Create Rehab Plan
    echo "\n7. CREATE REHAB PLAN\n";
    echo "-------------------------------------------\n";
    $ch = curl_init($baseUrl . '/rehab-plans');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'patient_id' => $patientId,
        'title' => 'Test Rehab Plan',
        'description' => 'Physical therapy exercises',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+30 days'))
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $doctorToken
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Raw Response: " . $response . "\n";
    $result = json_decode($response, true);
    if ($httpCode == 200 || $httpCode == 201) {
        if (isset($result['success']) && $result['success']) {
            echo "✓ Rehab plan created successfully\n";
        } else {
            echo "⚠ Response: " . json_encode($result) . "\n";
        }
    } else {
        echo "✗ Failed\n";
        echo "  Response: " . substr($response, 0, 300) . "\n";
    }
}

echo "\n======================================================================\n";
echo "✅ MEDICATIONS, REHAB & REPORTS TEST COMPLETE\n";
echo "======================================================================\n";
