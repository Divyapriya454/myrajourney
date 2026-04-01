<?php
/**
 * Test Extracted Data API Endpoint
 */

require __DIR__ . '/src/bootstrap.php';

echo "=== TESTING EXTRACTED DATA API ===\n\n";

// Simulate authenticated request
$_SERVER['auth'] = [
    'uid' => 75,
    'role' => 'PATIENT',
    'email' => 'deepankumar@gmail.com'
];

// Test for Report ID 16
$reportId = 16;

echo "Testing API for Report ID: $reportId\n\n";

try {
    $controller = new Src\Controllers\AIController();
    
    // Capture output
    ob_start();
    $controller->getExtractedData($reportId);
    $output = ob_get_clean();
    
    echo "API Response:\n";
    echo $output . "\n\n";
    
    // Parse and display nicely
    $data = json_decode($output, true);
    
    if ($data && $data['success']) {
        echo "✓ API call successful\n\n";
        echo "Processing Status: " . $data['data']['processing_status'] . "\n";
        echo "Values Count: " . count($data['data']['values']) . "\n\n";
        
        if (!empty($data['data']['values'])) {
            echo "Lab Values:\n";
            foreach ($data['data']['values'] as $val) {
                echo "  - {$val['test_name']}: {$val['value']} {$val['unit']}";
                if ($val['is_abnormal']) {
                    echo " [ABNORMAL]";
                }
                echo "\n";
            }
        }
    } else {
        echo "✗ API call failed\n";
        if (isset($data['error'])) {
            echo "Error: " . print_r($data['error'], true) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
