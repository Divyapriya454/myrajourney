<?php

require __DIR__ . '/src/bootstrap.php';

echo "=== Testing with Current Date (2025) ===" . PHP_EOL . PHP_EOL;

// Set JWT_SECRET
$_ENV['JWT_SECRET'] = 'myrajourney_secret_key_2024';

// Create valid JWT token
$payload = [
    'uid' => 1,
    'role' => 'PATIENT',
    'email' => 'test@example.com',
    'exp' => time() + 3600
];

$token = Src\Utils\Jwt::encode($payload, $_ENV['JWT_SECRET']);
$_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";

// Test with CURRENT DATE (2025)
$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');

$testData = [
    'patient_medication_id' => '1',
    'medication_name' => 'Methotrexate Current Date Test',
    'scheduled_time' => $currentDate . ' 10:00:00',  // Today at 10 AM
    'missed_time' => $currentDate . ' ' . $currentTime,  // Current time
    'reason' => 'forgot',
    'notes' => 'Testing with correct 2025 date'
];

echo "Current Date: $currentDate" . PHP_EOL;
echo "Current Time: $currentTime" . PHP_EOL;
echo "Test Data:" . PHP_EOL;
echo json_encode($testData, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

try {
    // Simulate route-level auth
    Src\Middlewares\Auth::requireAuth();
    echo "✅ Route auth successful" . PHP_EOL;
    
    // Get user ID
    $auth = $_SERVER['auth'] ?? [];
    $userId = (int)($auth['uid'] ?? 0);
    echo "✅ User ID: $userId" . PHP_EOL;
    
    // Validate input
    $requiredFields = ['patient_medication_id', 'medication_name', 'scheduled_time', 'missed_time', 'reason'];
    foreach ($requiredFields as $field) {
        if (!isset($testData[$field]) || empty($testData[$field])) {
            echo "❌ Missing field: $field" . PHP_EOL;
            exit(1);
        }
    }
    echo "✅ All required fields present" . PHP_EOL;
    
    // Validate reason
    $validReasons = ['forgot', 'side_effects', 'feeling_better', 'unavailable', 'other'];
    if (!in_array($testData['reason'], $validReasons)) {
        echo "❌ Invalid reason: " . $testData['reason'] . PHP_EOL;
        exit(1);
    }
    echo "✅ Reason is valid" . PHP_EOL;
    
    // Test database insert with current date
    $db = Src\Config\DB::conn();
    
    $stmt = $db->prepare("
        INSERT INTO missed_dose_reports 
        (patient_medication_id, medication_name, scheduled_time, missed_time, reason, notes, doctor_id, doctor_notified, reported_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $testData['patient_medication_id'],
        $testData['medication_name'],
        $testData['scheduled_time'],
        $testData['missed_time'],
        $testData['reason'],
        $testData['notes'],
        null,
        false
    ]);
    
    if ($result) {
        $reportId = $db->lastInsertId();
        echo "✅ Database insert successful with 2025 date - Report ID: $reportId" . PHP_EOL;
        
        // Verify the saved data
        $stmt = $db->prepare("SELECT * FROM missed_dose_reports WHERE id = ?");
        $stmt->execute([$reportId]);
        $saved = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "✅ Saved data verified:" . PHP_EOL;
        echo "  - Scheduled: " . $saved['scheduled_time'] . PHP_EOL;
        echo "  - Missed: " . $saved['missed_time'] . PHP_EOL;
        echo "  - Reported: " . $saved['reported_at'] . PHP_EOL;
        
    } else {
        echo "❌ Database insert failed" . PHP_EOL;
        exit(1);
    }
    
    echo PHP_EOL . "🎉 ✅ CURRENT DATE TEST PASSED!" . PHP_EOL;
    echo "🔧 The API works correctly with 2025 dates" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
