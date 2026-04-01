<?php
/**
 * OCR and API Endpoints Test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "     OCR & API ENDPOINTS TEST\n";
echo str_repeat("=", 70) . "\n\n";

$baseUrl = "http://127.0.0.1:8000";
$passed = 0;
$failed = 0;

function testAPI($name, $url, $method = 'GET', $data = null, $headers = []) {
    global $passed, $failed, $baseUrl;
    
    $ch = curl_init();
    $fullUrl = $baseUrl . $url;
    
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($jsonData);
        }
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);
    $error = curl_error($ch);
    curl_close($ch);
    
    $status = ($httpCode >= 200 && $httpCode < 300) ? 'PASS' : 'FAIL';
    $icon = $status === 'PASS' ? '✓' : '✗';
    
    echo sprintf("%-50s [%s] HTTP %d\n", $name, $icon, $httpCode);
    
    if ($status === 'PASS') {
        $passed++;
        $decoded = json_decode($body, true);
        if ($decoded) {
            if (isset($decoded['message'])) {
                echo "   → " . substr($decoded['message'], 0, 60) . "\n";
            } elseif (isset($decoded['data'])) {
                echo "   → Data received\n";
            }
        }
    } else {
        $failed++;
        if ($error) {
            echo "   → Error: $error\n";
        } else {
            echo "   → " . substr($body, 0, 100) . "\n";
        }
    }
    
    return [
        'status' => $status,
        'code' => $httpCode,
        'body' => $body,
        'data' => json_decode($body, true)
    ];
}

// ============================================
// 1. BASIC API TESTS
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "1. BASIC API ENDPOINTS\n";
echo str_repeat("-", 70) . "\n";

testAPI("Health Check", "/");
testAPI("API Base", "/api");

// ============================================
// 2. AUTHENTICATION TESTS
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "2. AUTHENTICATION\n";
echo str_repeat("-", 70) . "\n";

// Test login with patient
$loginResult = testAPI("Login - Patient", "/api/login", 'POST', [
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
]);

$patientToken = null;
if ($loginResult['data'] && isset($loginResult['data']['token'])) {
    $patientToken = $loginResult['data']['token'];
    echo "   → Token received: " . substr($patientToken, 0, 20) . "...\n";
}

// Test login with doctor
$doctorLoginResult = testAPI("Login - Doctor", "/api/login", 'POST', [
    'email' => 'doctor@test.com',
    'password' => 'Doctor@123'
]);

$doctorToken = null;
if ($doctorLoginResult['data'] && isset($doctorLoginResult['data']['token'])) {
    $doctorToken = $doctorLoginResult['data']['token'];
}

// Test login with admin
$adminLoginResult = testAPI("Login - Admin", "/api/login", 'POST', [
    'email' => 'testadmin@test.com',
    'password' => 'Admin@123'
]);

// Test invalid login
testAPI("Login - Invalid Credentials", "/api/login", 'POST', [
    'email' => 'invalid@test.com',
    'password' => 'wrongpassword'
]);

// ============================================
// 3. PATIENT ENDPOINTS
// ============================================
if ($patientToken) {
    echo "\n" . str_repeat("-", 70) . "\n";
    echo "3. PATIENT ENDPOINTS\n";
    echo str_repeat("-", 70) . "\n";
    
    $authHeaders = ["Authorization: Bearer $patientToken"];
    
    testAPI("Get Patient Profile", "/api/patient/profile", 'GET', null, $authHeaders);
    testAPI("Get Medications", "/api/patient/medications", 'GET', null, $authHeaders);
    testAPI("Get Appointments", "/api/patient/appointments", 'GET', null, $authHeaders);
    testAPI("Get Reports", "/api/patient/reports", 'GET', null, $authHeaders);
    testAPI("Get Health Metrics", "/api/patient/health-metrics", 'GET', null, $authHeaders);
    testAPI("Get Notifications", "/api/patient/notifications", 'GET', null, $authHeaders);
}

// ============================================
// 4. DOCTOR ENDPOINTS
// ============================================
if ($doctorToken) {
    echo "\n" . str_repeat("-", 70) . "\n";
    echo "4. DOCTOR ENDPOINTS\n";
    echo str_repeat("-", 70) . "\n";
    
    $authHeaders = ["Authorization: Bearer $doctorToken"];
    
    testAPI("Get Doctor Profile", "/api/doctor/profile", 'GET', null, $authHeaders);
    testAPI("Get Patients List", "/api/doctor/patients", 'GET', null, $authHeaders);
    testAPI("Get Appointments", "/api/doctor/appointments", 'GET', null, $authHeaders);
    testAPI("Get Notifications", "/api/doctor/notifications", 'GET', null, $authHeaders);
}

// ============================================
// 5. CHATBOT ENDPOINTS
// ============================================
if ($patientToken) {
    echo "\n" . str_repeat("-", 70) . "\n";
    echo "5. AI CHATBOT ENDPOINTS\n";
    echo str_repeat("-", 70) . "\n";
    
    $authHeaders = ["Authorization: Bearer $patientToken"];
    
    testAPI("Start Conversation", "/api/chatbot/start", 'POST', [], $authHeaders);
    testAPI("Send Message", "/api/chatbot/message", 'POST', [
        'message' => 'Hello, I need help with my medication'
    ], $authHeaders);
    testAPI("Get Conversation History", "/api/chatbot/history", 'GET', null, $authHeaders);
}

// ============================================
// 6. OCR ENDPOINTS
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "6. OCR FUNCTIONALITY\n";
echo str_repeat("-", 70) . "\n";

// Check OCR service file
if (file_exists(__DIR__ . '/src/services/OCRService.php')) {
    echo "✓ OCR Service File Exists\n";
} else {
    echo "✗ OCR Service File Missing\n";
}

// Check OCR configuration
$envContent = file_get_contents(__DIR__ . '/.env');
if (preg_match('/OCR_SPACE_API_KEY=(.+)/', $envContent, $matches)) {
    $ocrKey = trim($matches[1]);
    if (!empty($ocrKey) && $ocrKey !== 'your_api_key_here') {
        echo "✓ OCR API Key Configured: " . substr($ocrKey, 0, 10) . "...\n";
        
        // Test OCR API directly
        echo "\nTesting OCR API Connection...\n";
        $testUrl = "https://api.ocr.space/parse/image";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'apikey' => $ocrKey,
            'url' => 'https://via.placeholder.com/150',
            'language' => 'eng'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['OCRExitCode']) && $result['OCRExitCode'] == 1) {
                echo "✓ OCR API Connection Successful\n";
                echo "   → OCR Service is operational\n";
            } else {
                echo "⚠ OCR API Response: " . ($result['ErrorMessage'][0] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "✗ OCR API Connection Failed (HTTP $httpCode)\n";
        }
    } else {
        echo "✗ OCR API Key Not Configured\n";
    }
}

// Test report upload endpoint (if token available)
if ($patientToken) {
    echo "\nTesting Report Upload Endpoint...\n";
    testAPI("Report Upload Endpoint", "/api/reports/upload", 'POST', [], ["Authorization: Bearer $patientToken"]);
}

// ============================================
// 7. MEDICATION ENDPOINTS
// ============================================
if ($patientToken) {
    echo "\n" . str_repeat("-", 70) . "\n";
    echo "7. MEDICATION MANAGEMENT\n";
    echo str_repeat("-", 70) . "\n";
    
    $authHeaders = ["Authorization: Bearer $patientToken"];
    
    testAPI("Get Medications", "/api/medications", 'GET', null, $authHeaders);
    testAPI("Log Medication", "/api/medications/log", 'POST', [
        'medication_id' => 1,
        'status' => 'taken',
        'taken_at' => date('Y-m-d H:i:s')
    ], $authHeaders);
    testAPI("Get Medication Logs", "/api/medications/logs", 'GET', null, $authHeaders);
    testAPI("Report Missed Dose", "/api/medications/missed", 'POST', [
        'medication_id' => 1,
        'reason' => 'Forgot'
    ], $authHeaders);
}

// ============================================
// 8. HEALTH METRICS ENDPOINTS
// ============================================
if ($patientToken) {
    echo "\n" . str_repeat("-", 70) . "\n";
    echo "8. HEALTH METRICS\n";
    echo str_repeat("-", 70) . "\n";
    
    $authHeaders = ["Authorization: Bearer $patientToken"];
    
    testAPI("Add Health Metric", "/api/health-metrics", 'POST', [
        'metric_type' => 'blood_pressure',
        'value' => '120/80',
        'recorded_at' => date('Y-m-d H:i:s')
    ], $authHeaders);
    testAPI("Get Health Metrics", "/api/health-metrics", 'GET', null, $authHeaders);
    testAPI("Add CRP Measurement", "/api/crp", 'POST', [
        'value' => 5.2,
        'measured_at' => date('Y-m-d H:i:s')
    ], $authHeaders);
    testAPI("Get CRP History", "/api/crp", 'GET', null, $authHeaders);
}

// ============================================
// 9. SYMPTOMS ENDPOINTS
// ============================================
if ($patientToken) {
    echo "\n" . str_repeat("-", 70) . "\n";
    echo "9. SYMPTOMS TRACKING\n";
    echo str_repeat("-", 70) . "\n";
    
    $authHeaders = ["Authorization: Bearer $patientToken"];
    
    testAPI("Report Symptom", "/api/symptoms", 'POST', [
        'symptom' => 'Headache',
        'severity' => 'moderate',
        'description' => 'Mild headache after lunch'
    ], $authHeaders);
    testAPI("Get Symptoms History", "/api/symptoms", 'GET', null, $authHeaders);
}

// ============================================
// 10. EXERCISE & REHAB ENDPOINTS
// ============================================
if ($patientToken) {
    echo "\n" . str_repeat("-", 70) . "\n";
    echo "10. EXERCISE & REHABILITATION\n";
    echo str_repeat("-", 70) . "\n";
    
    $authHeaders = ["Authorization: Bearer $patientToken"];
    
    testAPI("Get Rehab Plans", "/api/rehab/plans", 'GET', null, $authHeaders);
    testAPI("Get Exercises", "/api/exercises", 'GET', null, $authHeaders);
    testAPI("Log Exercise", "/api/exercises/log", 'POST', [
        'exercise_id' => 1,
        'duration' => 30,
        'completed_at' => date('Y-m-d H:i:s')
    ], $authHeaders);
}

// ============================================
// SUMMARY
// ============================================
echo "\n" . str_repeat("=", 70) . "\n";
echo "API TEST SUMMARY\n";
echo str_repeat("=", 70) . "\n\n";

$total = $passed + $failed;
$successRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

echo sprintf("Total API Tests: %d\n", $total);
echo sprintf("✓ Passed:        %d\n", $passed);
echo sprintf("✗ Failed:        %d\n", $failed);
echo sprintf("\nSuccess Rate:    %.1f%%\n", $successRate);

if ($failed === 0) {
    echo "\n🎉 ALL API TESTS PASSED!\n";
} else {
    echo "\n⚠️  Some API endpoints need attention\n";
}

echo "\n" . str_repeat("=", 70) . "\n\n";
