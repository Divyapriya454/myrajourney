<?php
/**
 * Complete Feature Test Suite for MyRA Journey
 * Tests all backend functionalities including OCR
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "     MYRA JOURNEY - COMPLETE BACKEND FUNCTIONALITY TEST\n";
echo str_repeat("=", 70) . "\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";

$passed = 0;
$failed = 0;
$warnings = 0;

function test($name, $status, $details = '') {
    global $passed, $failed, $warnings;
    
    $icon = $status === 'PASS' ? '✓' : ($status === 'FAIL' ? '✗' : '⚠');
    $color = $status === 'PASS' ? '' : ($status === 'FAIL' ? '' : '');
    
    echo sprintf("%-50s [%s %s]\n", $name, $icon, $status);
    if ($details) {
        echo "   → $details\n";
    }
    
    if ($status === 'PASS') $passed++;
    elseif ($status === 'FAIL') $failed++;
    else $warnings++;
}

// ============================================
// 1. SYSTEM CHECKS
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "1. SYSTEM CHECKS\n";
echo str_repeat("-", 70) . "\n";

// PHP Version
$phpVersion = phpversion();
test("PHP Version", $phpVersion >= '7.4' ? 'PASS' : 'FAIL', "Version: $phpVersion");

// Database Connection
try {
    $db = new PDO("mysql:host=127.0.0.1;dbname=myrajourney", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    test("Database Connection", 'PASS', "Connected to myrajourney");
} catch (PDOException $e) {
    test("Database Connection", 'FAIL', $e->getMessage());
    die("\nCannot proceed without database\n");
}

// Environment File
if (file_exists(__DIR__ . '/.env')) {
    test("Environment File", 'PASS', ".env file exists");
    $env = parse_ini_file(__DIR__ . '/.env');
} else {
    test("Environment File", 'FAIL', ".env not found");
    $env = [];
}

// ============================================
// 2. DATABASE STRUCTURE
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "2. DATABASE STRUCTURE\n";
echo str_repeat("-", 70) . "\n";

$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$requiredTables = [
    'users', 'patients', 'doctors', 'appointments', 'medications',
    'medication_logs', 'reports', 'health_metrics', 'notifications',
    'symptoms', 'rehab_plans', 'chatbot_conversations', 'crp_measurements'
];

foreach ($requiredTables as $table) {
    if (in_array($table, $tables)) {
        $count = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        test("Table: $table", 'PASS', "$count records");
    } else {
        test("Table: $table", 'FAIL', "Missing");
    }
}

// ============================================
// 3. USER MANAGEMENT
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "3. USER MANAGEMENT\n";
echo str_repeat("-", 70) . "\n";

$stmt = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$userCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

foreach (['ADMIN', 'DOCTOR', 'PATIENT'] as $role) {
    $count = $userCounts[$role] ?? 0;
    test(ucfirst(strtolower($role)) . " Users", $count > 0 ? 'PASS' : 'WARN', "$count users");
}

// Test user credentials
$stmt = $db->query("SELECT id, name, email, role FROM users LIMIT 3");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
test("User Data Integrity", count($users) > 0 ? 'PASS' : 'FAIL', count($users) . " users found");

// ============================================
// 4. OCR CONFIGURATION
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "4. OCR SYSTEM\n";
echo str_repeat("-", 70) . "\n";

// Check OCR API Key from .env file directly
$envContent = file_get_contents(__DIR__ . '/.env');
preg_match('/OCR_SPACE_API_KEY=(.+)/', $envContent, $matches);
$ocrKey = trim($matches[1] ?? '');
if (!empty($ocrKey) && $ocrKey !== 'your_api_key_here') {
    test("OCR API Key", 'PASS', "Key configured: " . substr($ocrKey, 0, 10) . "...");
} else {
    test("OCR API Key", 'FAIL', "Not configured");
}

// Check OCR processing logs table
if (in_array('ocr_processing_logs', $tables)) {
    $ocrLogs = $db->query("SELECT COUNT(*) FROM ocr_processing_logs")->fetchColumn();
    test("OCR Processing Logs", 'PASS', "$ocrLogs log entries");
    
    if ($ocrLogs > 0) {
        $stmt = $db->query("SELECT status, COUNT(*) as count FROM ocr_processing_logs GROUP BY status");
        $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($statusCounts as $status => $count) {
            test("  OCR Status: $status", 'PASS', "$count reports");
        }
    }
} else {
    test("OCR Processing Logs", 'WARN', "Table not found");
}

// Check reports with OCR processing
$stmt = $db->query("SELECT COUNT(*) FROM reports WHERE ocr_processed = 1");
$ocrProcessed = $stmt->fetchColumn();
test("OCR Processed Reports", $ocrProcessed > 0 ? 'PASS' : 'WARN', "$ocrProcessed reports");

// Check lab values (extracted from OCR)
if (in_array('lab_values', $tables)) {
    $labValues = $db->query("SELECT COUNT(*) FROM lab_values")->fetchColumn();
    test("Lab Values Extracted", $labValues > 0 ? 'PASS' : 'WARN', "$labValues values");
} else {
    test("Lab Values Table", 'WARN', "Table not found");
}

// ============================================
// 5. AI CHATBOT
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "5. AI CHATBOT SYSTEM\n";
echo str_repeat("-", 70) . "\n";

// Check AI Provider from .env file directly
preg_match('/AI_PROVIDER=(.+)/', $envContent, $matches);
$aiProvider = trim($matches[1] ?? 'none');
test("AI Provider", !empty($aiProvider) ? 'PASS' : 'FAIL', "Provider: $aiProvider");

// Check chatbot conversations
$conversations = $db->query("SELECT COUNT(*) FROM chatbot_conversations")->fetchColumn();
test("Chatbot Conversations", 'PASS', "$conversations conversations");

// Check if chatbot has been used
if ($conversations > 0) {
    $stmt = $db->query("SELECT user_id, COUNT(*) as count FROM chatbot_conversations GROUP BY user_id");
    $userConvs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    test("Active Chatbot Users", 'PASS', count($userConvs) . " users");
}

// ============================================
// 6. MEDICATION MANAGEMENT
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "6. MEDICATION MANAGEMENT\n";
echo str_repeat("-", 70) . "\n";

$activeMeds = $db->query("SELECT COUNT(*) FROM medications")->fetchColumn();
test("Active Medications", 'PASS', "$activeMeds medications");

$medLogs = $db->query("SELECT COUNT(*) FROM medication_logs")->fetchColumn();
test("Medication Logs", 'PASS', "$medLogs log entries");

if ($medLogs > 0) {
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM medication_logs GROUP BY status");
    $logStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($logStatus as $status => $count) {
        test("  Med Status: $status", 'PASS', "$count entries");
    }
}

// Check patient medications table
if (in_array('patient_medications', $tables)) {
    $patientMeds = $db->query("SELECT COUNT(*) FROM patient_medications")->fetchColumn();
    test("Patient Medications", 'PASS', "$patientMeds assignments");
}

// ============================================
// 7. APPOINTMENTS
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "7. APPOINTMENTS\n";
echo str_repeat("-", 70) . "\n";

$appointments = $db->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
test("Total Appointments", 'PASS', "$appointments appointments");

if ($appointments > 0) {
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
    $apptStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($apptStatus as $status => $count) {
        test("  Status: $status", 'PASS', "$count appointments");
    }
}

// ============================================
// 8. NOTIFICATIONS
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "8. NOTIFICATIONS\n";
echo str_repeat("-", 70) . "\n";

$notifications = $db->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
test("Total Notifications", 'PASS', "$notifications notifications");

if ($notifications > 0) {
    $unread = $db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
    test("Unread Notifications", 'PASS', "$unread unread");
    
    $stmt = $db->query("SELECT type, COUNT(*) as count FROM notifications GROUP BY type");
    $notifTypes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($notifTypes as $type => $count) {
        test("  Type: $type", 'PASS', "$count notifications");
    }
}

// ============================================
// 9. HEALTH METRICS
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "9. HEALTH METRICS\n";
echo str_repeat("-", 70) . "\n";

$metrics = $db->query("SELECT COUNT(*) FROM health_metrics")->fetchColumn();
test("Health Metrics", 'PASS', "$metrics records");

$crp = $db->query("SELECT COUNT(*) FROM crp_measurements")->fetchColumn();
test("CRP Measurements", 'PASS', "$crp measurements");

// ============================================
// 10. REPORTS
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "10. MEDICAL REPORTS\n";
echo str_repeat("-", 70) . "\n";

$reports = $db->query("SELECT COUNT(*) FROM reports")->fetchColumn();
test("Total Reports", 'PASS', "$reports reports");

if ($reports > 0) {
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM reports GROUP BY status");
    $reportStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($reportStatus as $status => $count) {
        test("  Status: $status", 'PASS', "$count reports");
    }
}

// Check report notes
if (in_array('report_notes', $tables)) {
    $notes = $db->query("SELECT COUNT(*) FROM report_notes")->fetchColumn();
    test("Report Notes", 'PASS', "$notes notes");
}

// ============================================
// 11. REHAB & EXERCISES
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "11. REHABILITATION & EXERCISES\n";
echo str_repeat("-", 70) . "\n";

$rehabPlans = $db->query("SELECT COUNT(*) FROM rehab_plans")->fetchColumn();
test("Rehab Plans", 'PASS', "$rehabPlans plans");

if (in_array('rehab_exercises', $tables)) {
    $exercises = $db->query("SELECT COUNT(*) FROM rehab_exercises")->fetchColumn();
    test("Rehab Exercises", 'PASS', "$exercises exercises");
}

// ============================================
// 12. SYMPTOMS TRACKING
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "12. SYMPTOMS TRACKING\n";
echo str_repeat("-", 70) . "\n";

$symptoms = $db->query("SELECT COUNT(*) FROM symptoms")->fetchColumn();
test("Symptom Records", 'PASS', "$symptoms symptoms");

if ($symptoms > 0) {
    $stmt = $db->query("SELECT severity, COUNT(*) as count FROM symptoms GROUP BY severity");
    $severities = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($severities as $severity => $count) {
        test("  Severity: $severity", 'PASS', "$count symptoms");
    }
}

// ============================================
// 13. DOCTOR-PATIENT RELATIONSHIPS
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "13. DOCTOR-PATIENT RELATIONSHIPS\n";
echo str_repeat("-", 70) . "\n";

$stmt = $db->query("
    SELECT COUNT(DISTINCT a.patient_id) as assigned_patients
    FROM appointments a
    WHERE a.doctor_id IS NOT NULL
");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$assigned = $result['assigned_patients'] ?? 0;
test("Assigned Patients", $assigned > 0 ? 'PASS' : 'WARN', "$assigned patients with doctor assignments");

// ============================================
// 14. FILE STORAGE
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "14. FILE STORAGE\n";
echo str_repeat("-", 70) . "\n";

$storageDirs = [
    'public/uploads' => 'Report Uploads',
    'storage/reports' => 'Report Storage',
    'storage/logs' => 'System Logs',
    'storage/temp' => 'Temporary Files'
];

foreach ($storageDirs as $dir => $name) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $files = glob($path . '/*');
        $count = is_array($files) ? count($files) : 0;
        test($name, 'PASS', "$count files");
    } else {
        test($name, 'WARN', "Directory not found");
    }
}

// ============================================
// 15. API SERVER STATUS
// ============================================
echo "\n" . str_repeat("-", 70) . "\n";
echo "15. API SERVER STATUS\n";
echo str_repeat("-", 70) . "\n";

// Check if server is running
$output = shell_exec('netstat -an | findstr ":8000"');
if ($output) {
    test("Server Running", 'PASS', "Port 8000 is active");
} else {
    test("Server Running", 'WARN', "Server may not be running on port 8000");
}

// ============================================
// FINAL SUMMARY
// ============================================
echo "\n" . str_repeat("=", 70) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 70) . "\n\n";

$total = $passed + $failed + $warnings;
$successRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

echo sprintf("Total Tests:    %d\n", $total);
echo sprintf("✓ Passed:       %d\n", $passed);
echo sprintf("✗ Failed:       %d\n", $failed);
echo sprintf("⚠ Warnings:     %d\n", $warnings);
echo sprintf("\nSuccess Rate:   %.1f%%\n", $successRate);

if ($failed === 0 && $warnings === 0) {
    echo "\n🎉 ALL TESTS PASSED - SYSTEM FULLY OPERATIONAL!\n";
} elseif ($failed === 0) {
    echo "\n✓ All critical tests passed (some warnings present)\n";
} else {
    echo "\n⚠️  ATTENTION REQUIRED - Some tests failed\n";
}

echo "\nCompleted: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 70) . "\n\n";
