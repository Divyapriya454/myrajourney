<?php
// Test the fixed validation logic
echo "=== Testing Fixed Validation Logic ===" . PHP_EOL . PHP_EOL;

// Simulate the fixed validation
function validateField($body, $key) {
    if (!isset($body[$key]) && !array_key_exists($key, $body)) {
        return ['valid' => false, 'reason' => 'not set'];
    }
    if ($body[$key] === '' || $body[$key] === null) {
        return ['valid' => false, 'reason' => 'empty or null'];
    }
    return ['valid' => true, 'reason' => 'ok'];
}

$testCases = [
    [
        'name' => 'Valid with zero pain',
        'data' => [
            'patient_id' => 1,
            'date' => '2024-12-02',
            'pain_level' => 0,  // Zero should be valid!
            'stiffness_level' => 5,
            'fatigue_level' => 3,
        ]
    ],
    [
        'name' => 'Valid with all zeros',
        'data' => [
            'patient_id' => 1,
            'date' => '2024-12-02',
            'pain_level' => 0,
            'stiffness_level' => 0,
            'fatigue_level' => 0,
        ]
    ],
    [
        'name' => 'Invalid - missing pain_level',
        'data' => [
            'patient_id' => 1,
            'date' => '2024-12-02',
            'stiffness_level' => 5,
            'fatigue_level' => 3,
        ]
    ],
    [
        'name' => 'Invalid - null pain_level',
        'data' => [
            'patient_id' => 1,
            'date' => '2024-12-02',
            'pain_level' => null,
            'stiffness_level' => 5,
            'fatigue_level' => 3,
        ]
    ],
    [
        'name' => 'Invalid - empty string pain_level',
        'data' => [
            'patient_id' => 1,
            'date' => '2024-12-02',
            'pain_level' => '',
            'stiffness_level' => 5,
            'fatigue_level' => 3,
        ]
    ],
];

$requiredFields = ['patient_id', 'date', 'pain_level', 'stiffness_level', 'fatigue_level'];

foreach ($testCases as $test) {
    echo "Test: {$test['name']}" . PHP_EOL;
    $allValid = true;
    $failedField = null;
    
    foreach ($requiredFields as $field) {
        $result = validateField($test['data'], $field);
        if (!$result['valid']) {
            $allValid = false;
            $failedField = "$field ({$result['reason']})";
            break;
        }
    }
    
    if ($allValid) {
        echo "  ✓ PASS - All fields valid" . PHP_EOL;
    } else {
        echo "  ✗ FAIL - $failedField" . PHP_EOL;
    }
    echo PHP_EOL;
}

echo "=== Test Complete ===" . PHP_EOL;
