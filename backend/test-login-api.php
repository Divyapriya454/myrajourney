<?php
/**
 * Test Login API
 * Simulates what the Android app does
 */

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/controllers/AuthController.php';

use Src\Controllers\AuthController;

echo "===========================================\n";
echo "Testing Login API\n";
echo "===========================================\n\n";

// Test credentials
$testUsers = [
    ['email' => 'deepankumar@gmail.com', 'password' => 'Welcome@456'],
    ['email' => 'testadmin@test.com', 'password' => 'Admin@123'],
    ['email' => 'doctor@test.com', 'password' => 'Doctor@123'],
];

foreach ($testUsers as $credentials) {
    echo "Testing: {$credentials['email']}\n";
    echo "-------------------------------------------\n";
    
    // Simulate POST request
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [];
    
    // Simulate JSON body
    $jsonBody = json_encode($credentials);
    
    // Capture output
    ob_start();
    
    // Mock the input
    $GLOBALS['mockInput'] = $jsonBody;
    
    try {
        $controller = new AuthController();
        
        // Manually call login with test data
        $reflection = new ReflectionMethod($controller, 'login');
        $reflection->setAccessible(true);
        
        // Set up the request
        file_put_contents('php://input', $jsonBody);
        
        $controller->login();
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    $output = ob_get_clean();
    
    // Parse JSON response
    $response = json_decode($output, true);
    
    if ($response && isset($response['success']) && $response['success']) {
        echo "✅ Login successful!\n";
        echo "   User: {$response['data']['user']['name']}\n";
        echo "   Role: {$response['data']['user']['role']}\n";
    } else {
        echo "❌ Login failed!\n";
        if ($response && isset($response['error'])) {
            echo "   Error: {$response['error']['message']}\n";
        } else {
            echo "   Raw output: $output\n";
        }
    }
    
    echo "\n";
}

echo "===========================================\n";
echo "All tests complete!\n";
echo "===========================================\n";
