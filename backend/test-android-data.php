<?php

echo "=== Testing Android App Data Format ===" . PHP_EOL . PHP_EOL;

// This simulates the exact data format that the Android app should be sending
$androidData = [
    'patient_medication_id' => '1',
    'medication_name' => 'Methotrexate',
    'scheduled_time' => '2024-12-16 10:00:00',
    'missed_time' => '2024-12-16 12:30:00',
    'reason' => 'forgot',
    'notes' => 'Was in a meeting and forgot to take medication'
];

echo "Expected Android data format:" . PHP_EOL;
echo json_encode($androidData, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

// Validate this data against our requirements
$requiredFields = ['patient_medication_id', 'medication_name', 'scheduled_time', 'missed_time', 'reason'];
$validReasons = ['forgot', 'side_effects', 'feeling_better', 'unavailable', 'other'];

echo "Validation check:" . PHP_EOL;

foreach ($requiredFields as $field) {
    if (!isset($androidData[$field]) || empty($androidData[$field])) {
        echo "❌ Missing field: $field" . PHP_EOL;
    } else {
        echo "✅ Field present: $field = " . $androidData[$field] . PHP_EOL;
    }
}

if (in_array($androidData['reason'], $validReasons)) {
    echo "✅ Reason is valid: " . $androidData['reason'] . PHP_EOL;
} else {
    echo "❌ Invalid reason: " . $androidData['reason'] . PHP_EOL;
}

echo PHP_EOL . "=== Common Issues That Cause 400 Errors ===" . PHP_EOL;
echo "1. Missing Authorization header (JWT token)" . PHP_EOL;
echo "2. Invalid JWT token or expired token" . PHP_EOL;
echo "3. Missing required fields in JSON data" . PHP_EOL;
echo "4. Invalid 'reason' value (must be: forgot, side_effects, feeling_better, unavailable, other)" . PHP_EOL;
echo "5. Empty or malformed JSON data" . PHP_EOL;
echo "6. Incorrect Content-Type header (should be application/json)" . PHP_EOL . PHP_EOL;

echo "=== Debugging Steps ===" . PHP_EOL;
echo "1. Check that the Android app is sending a valid JWT token in Authorization header" . PHP_EOL;
echo "2. Verify all required fields are present in the JSON payload" . PHP_EOL;
echo "3. Ensure the 'reason' field uses one of the valid values" . PHP_EOL;
echo "4. Check that the request Content-Type is set to 'application/json'" . PHP_EOL;
echo "5. Verify the JSON is properly formatted (not empty or malformed)" . PHP_EOL;
