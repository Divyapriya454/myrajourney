<?php

// Test the actual API endpoint with proper authentication

$url = 'http://localhost:8000/api/v1/missed-dose-reports';

// Test data
$data = [
    'patient_medication_id' => '1',
    'medication_name' => 'Methotrexate API Test',
    'scheduled_time' => '2024-12-16 10:00:00',
    'missed_time' => '2024-12-16 12:30:00',
    'reason' => 'forgot',
    'notes' => 'Real API test - forgot to take medication'
];

// You'll need a valid JWT token for authentication
// For testing, you can get one by logging in first
$token = 'your_jwt_token_here'; // Replace with actual token

$options = [
    'http' => [
        'header' => [
            "Content-Type: application/json",
            "Authorization: Bearer $token"
        ],
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Error calling API\n";
} else {
    echo "API Response:\n";
    echo $result . "\n";
    
    $response = json_decode($result, true);
    if ($response && isset($response['success']) && $response['success']) {
        echo "✅ API call successful!\n";
    } else {
        echo "❌ API call failed!\n";
    }
}
