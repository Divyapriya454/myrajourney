<?php

require __DIR__ . '/src/bootstrap.php';

echo "=== Debug Missed Dose API ===" . PHP_EOL . PHP_EOL;

// Set up environment
$_ENV['JWT_SECRET'] = 'myrajourney_secret_key_2024';

// Create a valid JWT token
$payload = [
    'uid' => 1,
    'role' => 'PATIENT',
    'email' => 'test@example.com',
    'exp' => time() + 3600
];

$token = Src\Utils\Jwt::encode($payload, $_ENV['JWT_SECRET']);
$_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";

echo "✓ JWT Token created" . PHP_EOL;

// Verify auth works
$authPayload = Src\Middlewares\Auth::bearer();
if (!$authPayload) {
    echo "❌ Auth failed" . PHP_EOL;
    exit(1);
}
echo "✓ Auth successful - User ID: " . $authPayload['uid'] . PHP_EOL . PHP_EOL;

// Test different scenarios that might cause 400 errors

echo "=== Testing Various Input Scenarios ===" . PHP_EOL . PHP_EOL;

// Test 1: Valid data
echo "Test 1: Valid complete data" . PHP_EOL;
$validData = [
    'patient_medication_id' => '1',
    'medication_name' => 'Test Medication',
    'scheduled_time' => '2024-12-16 10:00:00',
    'missed_time' => '2024-12-16 12:30:00',
    'reason' => 'forgot',
    'notes' => 'Test notes'
];

testMissedDoseAPI($validData);

echo PHP_EOL . "Test 2: Missing required field" . PHP_EOL;
$missingField = [
    'patient_medication_id' => '1',
    'medication_name' => 'Test Medication',
    // 'scheduled_time' => '2024-12-16 10:00:00', // Missing this
    'missed_time' => '2024-12-16 12:30:00',
    'reason' => 'forgot'
];

testMissedDoseAPI($missingField);

echo PHP_EOL . "Test 3: Invalid reason" . PHP_EOL;
$invalidReason = [
    'patient_medication_id' => '1',
    'medication_name' => 'Test Medication',
    'scheduled_time' => '2024-12-16 10:00:00',
    'missed_time' => '2024-12-16 12:30:00',
    'reason' => 'invalid_reason' // Invalid reason
];

testMissedDoseAPI($invalidReason);

echo PHP_EOL . "Test 4: Empty JSON" . PHP_EOL;
testMissedDoseAPI(null);

function testMissedDoseAPI($data) {
    // Mock php://input
    $jsonData = $data ? json_encode($data) : '';
    
    // Create a temporary file to simulate php://input
    $tempFile = tmpfile();
    fwrite($tempFile, $jsonData);
    rewind($tempFile);
    
    // Override the file_get_contents function for php://input
    $originalInput = $jsonData;
    
    try {
        // Simulate the controller logic
        $input = json_decode($originalInput, true);
        
        echo "Input data: " . ($originalInput ?: 'empty') . PHP_EOL;
        
        if (!$input) {
            echo "❌ Validation failed: Invalid JSON data (400)" . PHP_EOL;
            return;
        }
        
        // Validate required fields
        $requiredFields = ['patient_medication_id', 'medication_name', 'scheduled_time', 'missed_time', 'reason'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                echo "❌ Validation failed: Missing required field: $field (400)" . PHP_EOL;
                return;
            }
        }
        
        // Validate reason
        $validReasons = ['forgot', 'side_effects', 'feeling_better', 'unavailable', 'other'];
        if (!in_array($input['reason'], $validReasons)) {
            echo "❌ Validation failed: Invalid reason provided (400)" . PHP_EOL;
            return;
        }
        
        echo "✅ Validation passed - would create report" . PHP_EOL;
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . PHP_EOL;
    }
}
