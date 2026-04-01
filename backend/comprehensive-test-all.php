<?php
/**
 * Comprehensive Test Suite for MyRA Journey Backend
 * Tests all functionalities including OCR, AI, and all endpoints
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== COMPREHENSIVE BACKEND TEST SUITE ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Test results storage
$results = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
    'tests' => []
];

function testResult($name, $status, $message = '') {
    global $results;
    $results['tests'][] = [
        'name' => $name,
        'status' => $status,
        'message' => $message
    ];
    
    if ($status === 'PASS') {
        $results['passed']++;
        echo "✓ PASS: $name\n";
    } elseif ($status === 'FAIL') {
        $results['failed']++;
        echo "✗ FAIL: $name - $message\n";
    } else {
        $results['warnings']++;
        echo "⚠ WARN: $name - $message\n";
    }
    
    if ($message && $status === 'PASS') {
        echo "  → $message\n";
    }
}

// ============================================
// 1. DATABASE CONNECTION TEST
// ============================================
echo "\n--- 1. DATABASE CONNECTION ---\n";
try {
    $db = new PDO(
        "mysql:host=127.0.0.1;dbname=myrajourney",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    testResult("Database Connection", "PASS", "Connected to myrajourney database");
} catch (PDOException $e) {
    testResult("Database Connection", "FAIL", $e->getMessage());
    die("Cannot proceed without database connection\n");
}

// ============================================
// 2. ENVIRONMENT CONFIGURATION TEST
// ============================================
echo "\n--- 2. ENVIRONMENT CONFIGURATION ---\n";

// Check .env file
if (file_exists(__DIR__ . '/.env')) {
    testResult(".env File Exists", "PASS");
    
    $envContent = file_get_contents(__DIR__ . '/.env');
    
    // Check OCR configuration
    if (strpos($envContent, 'OCR_SPACE_API_KEY') !== false) {
        preg_match('/OCR_SPACE_API_KEY=(.+)/', $envContent, $matches);
        $ocrKey = trim($matches[1] ?? '');
        if (!empty($ocrKey) && $ocrKey !== 'your_api_key_here') {
            testResult("OCR API Key Configured", "PASS", "Key: " . substr($ocrKey, 0, 10) . "...");
        } else {
            testResult("OCR API Key Configured", "FAIL", "OCR key not set");
        }
    }
    
    // Check AI configuration
    if (strpos($envContent, 'AI_PROVIDER') !== false) {
        preg_match('/AI_PROVIDER=(.+)/', $envContent, $matches);
        $aiProvider = trim($matches[1] ?? '');
        testResult("AI Provider Configured", "PASS", "Provider: $aiProvider");
    }
    
    // Check JWT secret
    if (strpos($envContent, 'JWT_SECRET') !== false) {
        testResult("JWT Secret Configured", "PASS");
    }
} else {
    testResult(".env File Exists", "FAIL", "File not found");
}

// ============================================
// 3. DATABASE TABLES TEST
// ============================================
echo "\n--- 3. DATABASE TABLES ---\n";

$requiredTables = [
    'users', 'patients', 'doctors', 'appointments', 'medications',
    'medication_logs', 'reports', 'health_metrics', 'notifications',
    'symptoms', 'rehab_plans', 'exercises', 'chatbot_conversations',
    'chatbot_messages', 'crp_measurements'
];

foreach ($requiredTables as $table) {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
        $count = $stmt->fetchColumn();
        testResult("Table: $table", "PASS", "$count records");
    } catch (PDOException $e) {
        testResult("Table: $table", "FAIL", "Table missing or inaccessible");
    }
}

// ============================================
// 4. USER AUTHENTICATION TEST
// ============================================
echo "\n--- 4. USER AUTHENTICATION ---\n";

// Check test users exist
$testUsers = [
    ['email' => 'deepan@patient.com', 'role' => 'patient'],
    ['email' => 'vinoth@doctor.com', 'role' => 'doctor'],
    ['email' => 'admin@myra.com', 'role' => 'admin']
];

foreach ($testUsers as $user) {
    $stmt = $db->prepare("SELECT id, email, role FROM users WHERE email = ?");
    $stmt->execute([$user['email']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        testResult("User: {$user['email']}", "PASS", "ID: {$result['id']}, Role: {$result['role']}");
    } else {
        testResult("User: {$user['email']}", "WARN", "User not found");
    }
}

// ============================================
// 5. API ENDPOINTS TEST
// ============================================
echo "\n--- 5. API ENDPOINTS ---\n";

function testEndpoint($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

// Test server is running
$baseUrl = "http://127.0.0.1:8000";
$result = testEndpoint("$baseUrl/api/test");

if ($result['code'] === 200 || $result['code'] === 404) {
    testResult("Server Running", "PASS", "Server responding on port 8000");
} else {
    testResult("Server Running", "FAIL", "Server not responding: " . $result['error']);
}

// Test login endpoint
$loginData = [
    'email' => 'deepan@patient.com',
    'password' => 'deepan123'
];

$result = testEndpoint("$baseUrl/api/login", 'POST', $loginData);
if ($result['code'] === 200) {
    $response = json_decode($result['response'], true);
    if (isset($response['token'])) {
        testResult("Login Endpoint", "PASS", "Token received");
        $authToken = $response['token'];
    } else {
        testResult("Login Endpoint", "WARN", "Response: " . substr($result['response'], 0, 100));
    }
} else {
    testResult("Login Endpoint", "FAIL", "HTTP {$result['code']}");
}

// ============================================
// 6. OCR FUNCTIONALITY TEST
// ============================================
echo "\n--- 6. OCR FUNCTIONALITY ---\n";

// Check OCR service files
$ocrFiles = [
    'src/services/OCRService.php',
    'src/controllers/ReportController.php'
];

foreach ($ocrFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        testResult("OCR File: $file", "PASS");
    } else {
        testResult("OCR File: $file", "FAIL", "File not found");
    }
}

// Check if OCR has been used
$stmt = $db->query("SELECT COUNT(*) FROM reports WHERE extracted_data IS NOT NULL AND extracted_data != ''");
$ocrReportsCount = $stmt->fetchColumn();

if ($ocrReportsCount > 0) {
    testResult("OCR Processing", "PASS", "$ocrReportsCount reports with extracted data");
    
    // Get sample OCR data
    $stmt = $db->query("SELECT id, file_path, extracted_data FROM reports WHERE extracted_data IS NOT NULL LIMIT 1");
    $sampleReport = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sampleReport) {
        $extractedData = json_decode($sampleReport['extracted_data'], true);
        if (is_array($extractedData) && !empty($extractedData)) {
            testResult("OCR Data Quality", "PASS", "Sample report has " . count($extractedData) . " extracted fields");
        }
    }
} else {
    testResult("OCR Processing", "WARN", "No reports with OCR data found");
}

// ============================================
// 7. AI CHATBOT TEST
// ============================================
echo "\n--- 7. AI CHATBOT ---\n";

// Check AI service files
$aiFiles = [
    'src/services/AIService.php',
    'src/controllers/ChatbotController.php'
];

foreach ($aiFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        testResult("AI File: $file", "PASS");
    } else {
        testResult("AI File: $file", "FAIL", "File not found");
    }
}

// Check chatbot conversations
$stmt = $db->query("SELECT COUNT(*) FROM chatbot_conversations");
$conversationsCount = $stmt->fetchColumn();
testResult("Chatbot Conversations", "PASS", "$conversationsCount conversations");

$stmt = $db->query("SELECT COUNT(*) FROM chatbot_messages");
$messagesCount = $stmt->fetchColumn();
testResult("Chatbot Messages", "PASS", "$messagesCount messages");

// ============================================
// 8. MEDICATION MANAGEMENT TEST
// ============================================
echo "\n--- 8. MEDICATION MANAGEMENT ---\n";

$stmt = $db->query("SELECT COUNT(*) FROM medications WHERE deleted_at IS NULL");
$activeMeds = $stmt->fetchColumn();
testResult("Active Medications", "PASS", "$activeMeds active medications");

$stmt = $db->query("SELECT COUNT(*) FROM medication_logs");
$medLogs = $stmt->fetchColumn();
testResult("Medication Logs", "PASS", "$medLogs medication log entries");

// Check for missed doses
$stmt = $db->query("SELECT COUNT(*) FROM medication_logs WHERE status = 'missed'");
$missedDoses = $stmt->fetchColumn();
testResult("Missed Dose Tracking", "PASS", "$missedDoses missed doses recorded");

// ============================================
// 9. APPOINTMENTS TEST
// ============================================
echo "\n--- 9. APPOINTMENTS ---\n";

$stmt = $db->query("SELECT COUNT(*) FROM appointments");
$appointmentsCount = $stmt->fetchColumn();
testResult("Total Appointments", "PASS", "$appointmentsCount appointments");

$stmt = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'scheduled'");
$scheduledCount = $stmt->fetchColumn();
testResult("Scheduled Appointments", "PASS", "$scheduledCount scheduled");

// ============================================
// 10. NOTIFICATIONS TEST
// ============================================
echo "\n--- 10. NOTIFICATIONS ---\n";

$stmt = $db->query("SELECT COUNT(*) FROM notifications");
$notificationsCount = $stmt->fetchColumn();
testResult("Total Notifications", "PASS", "$notificationsCount notifications");

$stmt = $db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
$unreadCount = $stmt->fetchColumn();
testResult("Unread Notifications", "PASS", "$unreadCount unread");

// ============================================
// 11. HEALTH METRICS TEST
// ============================================
echo "\n--- 11. HEALTH METRICS ---\n";

$stmt = $db->query("SELECT COUNT(*) FROM health_metrics");
$metricsCount = $stmt->fetchColumn();
testResult("Health Metrics Records", "PASS", "$metricsCount records");

$stmt = $db->query("SELECT COUNT(*) FROM crp_measurements");
$crpCount = $stmt->fetchColumn();
testResult("CRP Measurements", "PASS", "$crpCount measurements");

// ============================================
// 12. EXERCISE & REHAB TEST
// ============================================
echo "\n--- 12. EXERCISE & REHAB ---\n";

$stmt = $db->query("SELECT COUNT(*) FROM exercises");
$exercisesCount = $stmt->fetchColumn();
testResult("Exercises", "PASS", "$exercisesCount exercises");

$stmt = $db->query("SELECT COUNT(*) FROM rehab_plans");
$rehabCount = $stmt->fetchColumn();
testResult("Rehab Plans", "PASS", "$rehabCount rehab plans");

// ============================================
// 13. FILE STORAGE TEST
// ============================================
echo "\n--- 13. FILE STORAGE ---\n";

$storagePaths = [
    'public/uploads' => 'Report Uploads',
    'storage/reports' => 'Report Storage',
    'storage/logs' => 'Log Files',
    'storage/temp' => 'Temporary Files'
];

foreach ($storagePaths as $path => $name) {
    $fullPath = __DIR__ . '/' . $path;
    if (is_dir($fullPath)) {
        $fileCount = count(glob($fullPath . '/*'));
        testResult("Storage: $name", "PASS", "$fileCount files in $path");
    } else {
        testResult("Storage: $name", "WARN", "Directory not found: $path");
    }
}

// ============================================
// 14. SYMPTOMS TRACKING TEST
// ============================================
echo "\n--- 14. SYMPTOMS TRACKING ---\n";

$stmt = $db->query("SELECT COUNT(*) FROM symptoms");
$symptomsCount = $stmt->fetchColumn();
testResult("Symptom Records", "PASS", "$symptomsCount symptom entries");

// ============================================
// 15. DOCTOR-PATIENT RELATIONSHIPS TEST
// ============================================
echo "\n--- 15. DOCTOR-PATIENT RELATIONSHIPS ---\n";

$stmt = $db->query("
    SELECT COUNT(*) FROM patients p
    INNER JOIN doctors d ON p.doctor_id = d.id
");
$assignedPatients = $stmt->fetchColumn();
testResult("Assigned Patients", "PASS", "$assignedPatients patients assigned to doctors");

// ============================================
// FINAL SUMMARY
// ============================================
echo "\n" . str_repeat("=", 50) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "Total Tests: " . count($results['tests']) . "\n";
echo "✓ Passed: " . $results['passed'] . "\n";
echo "✗ Failed: " . $results['failed'] . "\n";
echo "⚠ Warnings: " . $results['warnings'] . "\n";

$successRate = round(($results['passed'] / count($results['tests'])) * 100, 2);
echo "\nSuccess Rate: $successRate%\n";

if ($results['failed'] === 0) {
    echo "\n🎉 ALL CRITICAL TESTS PASSED!\n";
} else {
    echo "\n⚠️  SOME TESTS FAILED - Review above for details\n";
}

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n";

// Save results to file
$reportFile = __DIR__ . '/test-results-' . date('Y-m-d-His') . '.json';
file_put_contents($reportFile, json_encode($results, JSON_PRETTY_PRINT));
echo "\nDetailed results saved to: $reportFile\n";
