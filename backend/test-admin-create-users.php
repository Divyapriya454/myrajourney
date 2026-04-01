<?php
echo "======================================================================\n";
echo "     TESTING ADMIN USER CREATION\n";
echo "======================================================================\n\n";

$baseUrl = 'http://192.168.29.162:8000/api/v1';

// Login as admin
echo "1. Admin Login\n";
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
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);
if ($httpCode != 200 || !$result['success']) {
    die("✗ Admin login failed!\n");
}

$adminToken = $result['data']['token'];
echo "✓ Admin logged in\n\n";

// Test 1: Create Patient
echo "2. Create Patient\n";
echo "-------------------------------------------\n";
$patientData = [
    'name' => 'Test Patient ' . time(),
    'email' => 'patient' . time() . '@test.com',
    'password' => 'Patient@123',
    'phone' => '9876543210',
    'role' => 'PATIENT',
    'age' => 30,
    'gender' => 'MALE',
    'address' => 'Test Address'
];

$ch = curl_init($baseUrl . '/admin/users');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($patientData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $adminToken
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$result = json_decode($response, true);
if (($httpCode == 200 || $httpCode == 201) && isset($result['success']) && $result['success']) {
    echo "✓ Patient created successfully\n";
    echo "  User ID: {$result['data']['id']}\n";
    echo "  Email: {$patientData['email']}\n";
} else {
    echo "✗ Failed to create patient\n";
    echo "  Response: " . substr($response, 0, 300) . "\n";
}

// Test 2: Create Doctor
echo "\n3. Create Doctor\n";
echo "-------------------------------------------\n";
$doctorData = [
    'name' => 'Dr. Test ' . time(),
    'email' => 'doctor' . time() . '@test.com',
    'password' => 'Doctor@123',
    'phone' => '9876543211',
    'role' => 'DOCTOR',
    'specialization' => 'Rheumatology'
];

$ch = curl_init($baseUrl . '/admin/users');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($doctorData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $adminToken
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$result = json_decode($response, true);
if (($httpCode == 200 || $httpCode == 201) && isset($result['success']) && $result['success']) {
    echo "✓ Doctor created successfully\n";
    echo "  User ID: {$result['data']['id']}\n";
    echo "  Email: {$doctorData['email']}\n";
} else {
    echo "✗ Failed to create doctor\n";
    echo "  Response: " . substr($response, 0, 300) . "\n";
}

// Test 3: Verify users were created
echo "\n4. Verify Users\n";
echo "-------------------------------------------\n";
$ch = curl_init($baseUrl . '/users');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $adminToken
]);

$response = curl_exec($ch);
$result = json_decode($response, true);

if (isset($result['data'])) {
    $userCount = count($result['data']);
    echo "✓ Total users in system: $userCount\n";
    
    $patients = array_filter($result['data'], function($u) { return $u['role'] == 'PATIENT'; });
    $doctors = array_filter($result['data'], function($u) { return $u['role'] == 'DOCTOR'; });
    $admins = array_filter($result['data'], function($u) { return $u['role'] == 'ADMIN'; });
    
    echo "  Patients: " . count($patients) . "\n";
    echo "  Doctors: " . count($doctors) . "\n";
    echo "  Admins: " . count($admins) . "\n";
}

echo "\n======================================================================\n";
echo "✅ ADMIN USER CREATION TEST COMPLETE\n";
echo "======================================================================\n";
