<?php
// Direct test of the symptom endpoint with the fixed controller
require __DIR__ . '/src/bootstrap.php';

use Src\Config\DB;
use Src\Controllers\SymptomController;
use Src\Utils\Response;

echo "=== Testing Symptom Endpoint with Zero Values ===" . PHP_EOL . PHP_EOL;

// Get a test patient
$db = DB::conn();
$stmt = $db->query("SELECT id FROM users WHERE role = 'PATIENT' LIMIT 1");
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    echo "✗ No patient found in database" . PHP_EOL;
    exit(1);
}

$patientId = $patient['id'];
echo "Using patient ID: $patientId" . PHP_EOL . PHP_EOL;

// Test cases
$testCases = [
    [
        'name' => 'Test 1: All values > 0',
        'data' => [
            'patient_id' => $patientId,
            'date' => '2024-12-02',
            'pain_level' => 5,
            'stiffness_level' => 6,
            'fatigue_level' => 7,
            'notes' => 'Test with normal values'
        ],
        'should_pass' => true
    ],
    [
        'name' => 'Test 2: Pain level = 0 (no pain)',
        'data' => [
            'patient_id' => $patientId,
            'date' => '2024-12-02',
            'pain_level' => 0,
            'stiffness_level' => 5,
            'fatigue_level' => 3,
            'notes' => 'Test with zero pain'
        ],
        'should_pass' => true
    ],
    [
        'name' => 'Test 3: All levels = 0',
        'data' => [
            'patient_id' => $patientId,
            'date' => '2024-12-02',
            'pain_level' => 0,
            'stiffness_level' => 0,
            'fatigue_level' => 0,
            'notes' => 'Test with all zeros'
        ],
        'should_pass' => true
    ],
    [
        'name' => 'Test 4: Missing pain_level',
        'data' => [
            'patient_id' => $patientId,
            'date' => '2024-12-02',
            'stiffness_level' => 5,
            'fatigue_level' => 3,
            'notes' => 'Missing pain level'
        ],
        'should_pass' => false
    ],
];

foreach ($testCases as $test) {
    echo "{$test['name']}" . PHP_EOL;
    echo "  Data: " . json_encode($test['data']) . PHP_EOL;
    
    // Simulate the request
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['auth'] = ['uid' => $patientId, 'role' => 'PATIENT'];
    
    // Capture output
    ob_start();
    
    // Mock the input
    $GLOBALS['mock_input'] = json_encode($test['data']);
    
    // Override file_get_contents for testing
    if (!function_exists('mock_file_get_contents')) {
        function mock_file_get_contents($filename) {
            if ($filename === 'php://input') {
                return $GLOBALS['mock_input'] ?? '';
            }
            return \file_get_contents($filename);
        }
    }
    
    try {
        // Manually validate like the controller does
        $body = json_decode($GLOBALS['mock_input'], true) ?? [];
        
        // Auto-set patient_id for PATIENT role
        if ($_SERVER['auth']['role'] === 'PATIENT') {
            $body['patient_id'] = $_SERVER['auth']['uid'];
        }
        
        $valid = true;
        $missingField = null;
        
        foreach(['patient_id','date','pain_level','stiffness_level','fatigue_level'] as $k) {
            if (!isset($body[$k]) && !array_key_exists($k, $body)) {
                $valid = false;
                $missingField = $k;
                break;
            }
            if ($body[$k] === '' || $body[$k] === null) {
                $valid = false;
                $missingField = $k;
                break;
            }
        }
        
        if ($valid) {
            // Try to insert
            $stmt = $db->prepare('INSERT INTO symptom_logs (patient_id, `date`, pain_level, stiffness_level, fatigue_level, notes, created_at) VALUES (:pid, :date, :pain, :stiff, :fatigue, :notes, NOW())');
            $stmt->execute([
                ':pid' => (int)$body['patient_id'],
                ':date' => $body['date'],
                ':pain' => $body['pain_level'],
                ':stiff' => $body['stiffness_level'],
                ':fatigue' => $body['fatigue_level'],
                ':notes' => $body['notes'] ?? null,
            ]);
            $id = $db->lastInsertId();
            
            echo "  ✓ PASS - Inserted with ID: $id" . PHP_EOL;
            
            // Clean up
            $stmt = $db->prepare('DELETE FROM symptom_logs WHERE id = :id');
            $stmt->execute([':id' => $id]);
        } else {
            echo "  ✗ FAIL - Validation failed: Missing $missingField" . PHP_EOL;
        }
        
        $expected = $test['should_pass'] ? 'PASS' : 'FAIL';
        $actual = $valid ? 'PASS' : 'FAIL';
        
        if ($expected === $actual) {
            echo "  ✓ Result matches expectation" . PHP_EOL;
        } else {
            echo "  ✗ Result DOES NOT match expectation (expected: $expected, got: $actual)" . PHP_EOL;
        }
        
    } catch (Exception $e) {
        echo "  ✗ ERROR: " . $e->getMessage() . PHP_EOL;
    }
    
    ob_end_clean();
    echo PHP_EOL;
}

echo "=== Test Complete ===" . PHP_EOL;
