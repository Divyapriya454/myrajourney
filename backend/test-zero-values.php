<?php
// Test what happens with zero values
echo "=== Testing Zero Values in Validation ===" . PHP_EOL . PHP_EOL;

$testCases = [
    ['value' => 0, 'name' => 'integer 0'],
    ['value' => '0', 'name' => 'string "0"'],
    ['value' => '', 'name' => 'empty string'],
    ['value' => null, 'name' => 'null'],
    ['value' => false, 'name' => 'false'],
    ['value' => 5, 'name' => 'integer 5'],
];

echo "PHP empty() function results:" . PHP_EOL;
foreach ($testCases as $test) {
    $isEmpty = empty($test['value']);
    $status = $isEmpty ? '✗ EMPTY' : '✓ NOT EMPTY';
    echo "  $status - {$test['name']}: " . var_export($test['value'], true) . PHP_EOL;
}

echo PHP_EOL . "PROBLEM IDENTIFIED:" . PHP_EOL;
echo "  If pain_level, stiffness_level, or fatigue_level is 0," . PHP_EOL;
echo "  the backend validation will FAIL because empty(0) returns TRUE!" . PHP_EOL;
echo PHP_EOL;
echo "SOLUTION:" . PHP_EOL;
echo "  Change validation from empty() to isset() or check !== null" . PHP_EOL;
