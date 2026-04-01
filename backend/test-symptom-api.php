<?php
// Test Symptom API endpoint directly
require __DIR__ . '/src/bootstrap.php';

use Src\Config\DB;

echo "=== Symptom API Test ===" . PHP_EOL . PHP_EOL;

// Test 1: Check database connection
echo "1. Database Connection Test" . PHP_EOL;
try {
    $db = DB::conn();
    echo "   ✓ Database connected" . PHP_EOL;
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test 2: Check if symptom_logs table exists
echo PHP_EOL . "2. Table Structure Test" . PHP_EOL;
try {
    $stmt = $db->query("DESCRIBE symptom_logs");
    echo "   ✓ symptom_logs table exists" . PHP_EOL;
    echo "   Fields: ";
    $fields = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fields[] = $row['Field'];
    }
    echo implode(', ', $fields) . PHP_EOL;
} catch (Exception $e) {
    echo "   ✗ Table check failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test 3: Check if we have a test patient
echo PHP_EOL . "3. Test Patient Check" . PHP_EOL;
try {
    $stmt = $db->query("SELECT id, email, role FROM users WHERE role = 'PATIENT' LIMIT 1");
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($patient) {
        echo "   ✓ Found test patient: ID={$patient['id']}, Email={$patient['email']}" . PHP_EOL;
        $testPatientId = $patient['id'];
    } else {
        echo "   ✗ No patient found in database" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "   ✗ Patient check failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test 4: Simulate symptom creation
echo PHP_EOL . "4. Symptom Creation Test" . PHP_EOL;
$testData = [
    'patient_id' => $testPatientId,
    'date' => date('Y-m-d'),
    'pain_level' => 5,
    'stiffness_level' => 6,
    'fatigue_level' => 7,
    'notes' => 'Test symptom from API test script'
];

echo "   Test data:" . PHP_EOL;
foreach ($testData as $key => $value) {
    echo "     - $key: " . var_export($value, true) . " (empty: " . (empty($value) ? 'YES' : 'NO') . ")" . PHP_EOL;
}

// Check validation logic
echo PHP_EOL . "   Validation check:" . PHP_EOL;
$requiredFields = ['patient_id', 'date', 'pain_level', 'stiffness_level', 'fatigue_level'];
$allValid = true;
foreach ($requiredFields as $field) {
    $value = $testData[$field] ?? null;
    $isEmpty = empty($value);
    $status = $isEmpty ? '✗ FAIL' : '✓ PASS';
    echo "     $status $field: " . var_export($value, true) . " (empty: " . ($isEmpty ? 'YES' : 'NO') . ")" . PHP_EOL;
    if ($isEmpty) {
        $allValid = false;
    }
}

if (!$allValid) {
    echo PHP_EOL . "   ✗ Validation would FAIL - some required fields are empty" . PHP_EOL;
    echo PHP_EOL . "   NOTE: PHP empty() returns TRUE for: null, '', 0, '0', false, []" . PHP_EOL;
    echo "   This is a BUG in the backend validation!" . PHP_EOL;
} else {
    echo PHP_EOL . "   ✓ All validations passed" . PHP_EOL;
    
    // Try to insert
    try {
        $stmt = $db->prepare('INSERT INTO symptom_logs (patient_id, `date`, pain_level, stiffness_level, fatigue_level, notes, created_at) VALUES (:pid, :date, :pain, :stiff, :fatigue, :notes, NOW())');
        $stmt->execute([
            ':pid' => (int)$testData['patient_id'],
            ':date' => $testData['date'],
            ':pain' => $testData['pain_level'],
            ':stiff' => $testData['stiffness_level'],
            ':fatigue' => $testData['fatigue_level'],
            ':notes' => $testData['notes'],
        ]);
        $id = $db->lastInsertId();
        echo "   ✓ Symptom inserted successfully with ID: $id" . PHP_EOL;
        
        // Clean up test data
        $stmt = $db->prepare('DELETE FROM symptom_logs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        echo "   ✓ Test data cleaned up" . PHP_EOL;
    } catch (Exception $e) {
        echo "   ✗ Insert failed: " . $e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL . "=== Test Complete ===" . PHP_EOL;
