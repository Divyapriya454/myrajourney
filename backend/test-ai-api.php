<?php
/**
 * Test AI API Endpoint
 */

echo "===========================================\n";
echo "Testing AI API Endpoint\n";
echo "===========================================\n\n";

// Test with a sample report ID
$url = 'http://localhost:8000/api/v1/ai/reports/process';
$data = ['report_id' => '1'];

echo "URL: $url\n";
echo "Data: " . json_encode($data) . "\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($error) {
    echo "❌ cURL Error: $error\n";
} else {
    echo "Response:\n";
    echo "-------------------------------------------\n";
    echo $response . "\n";
    echo "-------------------------------------------\n\n";
    
    $json = json_decode($response, true);
    if ($json) {
        if (isset($json['success'])) {
            if ($json['success']) {
                echo "✅ API WORKING - Report processing initiated\n";
            } else {
                echo "⚠️  API responded but processing failed\n";
                if (isset($json['error'])) {
                    echo "Error: {$json['error']['message']}\n";
                }
            }
        }
    } else {
        echo "❌ Invalid JSON response\n";
    }
}

echo "\n===========================================\n";
echo "Note: Report ID 1 may not exist.\n";
echo "This test just verifies the endpoint loads.\n";
echo "===========================================\n";
