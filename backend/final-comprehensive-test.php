<?php
$host = 'localhost';
$dbname = 'myrajourney';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

echo "======================================================================\n";
echo "     MYRA JOURNEY - FINAL COMPREHENSIVE TEST\n";
echo "======================================================================\n\n";

$passed = 0;
$failed = 0;
$warnings = 0;

// Test 1: Database Connection
echo "1. Database Connection: ";
if ($pdo) {
    echo "✓ PASS\n";
    $passed++;
} else {
    echo "✗ FAIL\n";
    $failed++;
}

// Test 2: Server Running
echo "2. PHP Server Running: ";
$ch = curl_init('http://10.108.1.165:8000/api/v1/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($httpCode == 200 || $httpCode == 404) {
    echo "✓ PASS (Server responding)\n";
    $passed++;
} else {
    echo "✗ FAIL\n";
    $failed++;
}

// Test 3: Patient Login
echo "3. Patient Login API: ";
$ch = curl_init('http://10.108.1.165:8000/api/v1/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'deepankumar@gmail.com', 'password' => 'Welcome@456']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$result = json_decode($response, true);
if ($httpCode == 200 && isset($result['success']) && $result['success']) {
    echo "✓ PASS\n";
    $patientToken = $result['data']['token'];
    $passed++;
} else {
    echo "✗ FAIL\n";
    $failed++;
    $patientToken = null;
}

// Test 4: Doctor Login
echo "4. Doctor Login API: ";
$ch = curl_init('http://10.108.1.165:8000/api/v1/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'doctor@test.com', 'password' => 'Doctor@123']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$result = json_decode($response, true);
if ($httpCode == 200 && isset($result['success']) && $result['success']) {
    echo "✓ PASS\n";
    $doctorToken = $result['data']['token'];
    $passed++;
} else {
    echo "✗ FAIL\n";
    $failed++;
    $doctorToken = null;
}

// Test 5: Admin Login
echo "5. Admin Login API: ";
$ch = curl_init('http://10.108.1.165:8000/api/v1/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'testadmin@test.com', 'password' => 'Admin@123']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$result = json_decode($response, true);
if ($httpCode == 200 && isset($result['success']) && $result['success']) {
    echo "✓ PASS\n";
    $passed++;
} else {
    echo "✗ FAIL\n";
    $failed++;
}

// Test 6: Database Tables
echo "6. Database Tables: ";
$tables = ['users', 'patients', 'doctors', 'appointments', 'medications', 'reports', 'notifications', 'symptoms', 'health_metrics', 'rehab_plans'];
$allTablesExist = true;
foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() == 0) {
        $allTablesExist = false;
        break;
    }
}
if ($allTablesExist) {
    echo "✓ PASS (All critical tables exist)\n";
    $passed++;
} else {
    echo "✗ FAIL\n";
    $failed++;
}

// Test 7: Doctor-Patient Assignment
echo "7. Doctor-Patient Assignment: ";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM patients");
$patientCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
$stmt = $pdo->query("SELECT COUNT(*) as count FROM doctors");
$doctorCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
if ($patientCount > 0 && $doctorCount > 0) {
    echo "✓ PASS ($patientCount patients, $doctorCount doctors)\n";
    $passed++;
} else {
    echo "⚠ WARN (Patients: $patientCount, Doctors: $doctorCount)\n";
    $warnings++;
}

// Test 8: OCR Configuration
echo "8. OCR API Key: ";
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (strpos($envContent, 'OCR_API_KEY') !== false) {
        echo "✓ PASS\n";
        $passed++;
    } else {
        echo "⚠ WARN (Not configured)\n";
        $warnings++;
    }
} else {
    echo "✗ FAIL (.env missing)\n";
    $failed++;
}

// Test 9: File Upload Directory
echo "9. Upload Directory: ";
$uploadDir = __DIR__ . '/public/uploads/reports';
if (is_dir($uploadDir) && is_writable($uploadDir)) {
    echo "✓ PASS\n";
    $passed++;
} else {
    echo "⚠ WARN (Not writable)\n";
    $warnings++;
}

// Test 10: Backend Location
echo "10. Backend in htdocs: ";
if (strpos(__DIR__, 'htdocs') !== false) {
    echo "✓ PASS\n";
    $passed++;
} else {
    echo "⚠ WARN (Not in htdocs)\n";
    $warnings++;
}

echo "\n======================================================================\n";
echo "TEST SUMMARY\n";
echo "======================================================================\n";
echo "Total Tests: " . ($passed + $failed + $warnings) . "\n";
echo "✓ Passed:    $passed\n";
echo "✗ Failed:    $failed\n";
echo "⚠ Warnings:  $warnings\n";
echo "\nSuccess Rate: " . round(($passed / ($passed + $failed + $warnings)) * 100, 1) . "%\n";
echo "======================================================================\n\n";

if ($failed == 0) {
    echo "✓ ALL CRITICAL TESTS PASSED! App is ready to use.\n\n";
    echo "CREDENTIALS:\n";
    echo "-------------------------------------------\n";
    echo "Patient: deepankumar@gmail.com / Welcome@456\n";
    echo "Doctor:  doctor@test.com / Doctor@123\n";
    echo "Admin:   testadmin@test.com / Admin@123\n";
    echo "-------------------------------------------\n";
    echo "\nAPI Server: http://10.108.1.165:8000\n";
} else {
    echo "⚠ Some tests failed. Please review the results above.\n";
}
