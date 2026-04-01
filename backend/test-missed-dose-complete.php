<?php
// Complete test for missed dose functionality
echo "=== MISSED DOSE FUNCTIONALITY TEST ===\n";
echo "Testing complete workflow from login to missed dose reporting\n\n";

// Step 1: Login to get authentication token
echo "1. Logging in to get authentication token...\n";
$loginUrl = "http://10.114.201.165:8000/api/v1/auth/login";
$loginData = json_encode([
    "email" => "patient@test.com",
    "password" => "password123"
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login HTTP Code: $loginHttpCode\n";
echo "Login Response: $loginResponse\n\n";

if ($loginHttpCode !== 200) {
    echo "❌ LOGIN FAILED - Cannot proceed with test\n";
    echo "Make sure you have a test patient account with email: patient@test.com, password: password123\n";
    exit;
}

$loginData = json_decode($loginResponse, true);
if (!$loginData || !$loginData['success'] || !isset($loginData['data']['token'])) {
    echo "❌ LOGIN FAILED - Invalid response format\n";
    exit;
}

$token = $loginData['data']['token'];
$userId = $loginData['data']['user']['id'];
echo "✅ LOGIN SUCCESS - Token obtained for user ID: $userId\n\n";

// Step 2: Get patient medications
echo "2. Getting patient medications...\n";
$medicationsUrl = "http://10.114.201.165:8000/api/v1/patient-medications";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $medicationsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$medicationsResponse = curl_exec($ch);
$medicationsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Medications HTTP Code: $medicationsHttpCode\n";
echo "Medications Response: $medicationsResponse\n\n";

if ($medicationsHttpCode !== 200) {
    echo "❌ MEDICATIONS FETCH FAILED\n";
    exit;
}

$medicationsData = json_decode($medicationsResponse, true);
if (!$medicationsData || !$medicationsData['success'] || empty($medicationsData['data'])) {
    echo "❌ NO MEDICATIONS FOUND - Cannot test missed dose reporting\n";
    echo "Please assign at least one medication to the test patient\n";
    exit;
}

$firstMedication = $medicationsData['data'][0];
$medicationId = $firstMedication['id'];
$medicationName = $firstMedication['name'] ?? $firstMedication['name_override'] ?? $firstMedication['medication_name'] ?? 'Test Medication';

echo "✅ MEDICATIONS FOUND - Using medication ID: $medicationId, Name: $medicationName\n\n";

// Step 3: Test missed dose reporting
echo "3. Testing missed dose reporting...\n";
$missedDoseUrl = "http://10.114.201.165:8000/api/v1/medications/log";
$missedDoseData = json_encode([
    "patient_medication_id" => $medicationId,
    "status" => "SKIPPED",
    "notes" => "Test missed dose - forgot to take medication this morning",
    "taken_at" => date('Y-m-d H:i:s')
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $missedDoseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $missedDoseData);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$missedDoseResponse = curl_exec($ch);
$missedDoseHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Missed Dose HTTP Code: $missedDoseHttpCode\n";
echo "Missed Dose Response: $missedDoseResponse\n\n";

// Step 4: Test alternative endpoint
echo "4. Testing alternative endpoint...\n";
$altUrl = "http://10.114.201.165:8000/api/v1/medication-logs";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $altUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $missedDoseData);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$altResponse = curl_exec($ch);
$altHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Alternative Endpoint HTTP Code: $altHttpCode\n";
echo "Alternative Endpoint Response: $altResponse\n\n";

// Results
echo "=== TEST RESULTS ===\n";

if ($missedDoseHttpCode === 200 || $missedDoseHttpCode === 201) {
    $missedData = json_decode($missedDoseResponse, true);
    if ($missedData && $missedData['success']) {
        echo "✅ PRIMARY ENDPOINT (/api/v1/medications/log) - SUCCESS\n";
        echo "   - Missed dose logged successfully\n";
        echo "   - Log ID: " . ($missedData['data']['log_id'] ?? 'N/A') . "\n";
    } else {
        echo "❌ PRIMARY ENDPOINT - API Error: " . ($missedData['error']['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "❌ PRIMARY ENDPOINT - HTTP Error: $missedDoseHttpCode\n";
}

if ($altHttpCode === 200 || $altHttpCode === 201) {
    $altData = json_decode($altResponse, true);
    if ($altData && $altData['success']) {
        echo "✅ ALTERNATIVE ENDPOINT (/api/v1/medication-logs) - SUCCESS\n";
    } else {
        echo "❌ ALTERNATIVE ENDPOINT - API Error: " . ($altData['error']['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "❌ ALTERNATIVE ENDPOINT - HTTP Error: $altHttpCode\n";
}

echo "\n=== SUMMARY ===\n";
if (($missedDoseHttpCode === 200 || $missedDoseHttpCode === 201) && 
    ($altHttpCode === 200 || $altHttpCode === 201)) {
    echo "🎉 ALL TESTS PASSED!\n";
    echo "✅ The missed dose functionality is working correctly\n";
    echo "✅ Both API endpoints are functional\n";
    echo "✅ Authentication is working\n";
    echo "✅ Doctor notifications should be sent\n";
    echo "\n📱 The Android app should now be able to report missed doses successfully!\n";
} else {
    echo "❌ SOME TESTS FAILED\n";
    echo "Please check the error messages above and fix any issues\n";
}
?>