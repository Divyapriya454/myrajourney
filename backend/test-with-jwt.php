<?php

require __DIR__ . '/src/bootstrap.php';

echo "=== Testing Missed Dose API with Valid JWT ===" . PHP_EOL . PHP_EOL;

// Create a valid JWT token
$payload = [
    'uid' => 1,
    'role' => 'PATIENT',
    'email' => 'test@example.com',
    'exp' => time() + 3600 // Expires in 1 hour
];

$secret = $_ENV['JWT_SECRET'] ?? 'default_secret';
$token = Src\Utils\Jwt::encode($payload, $secret);

echo "Generated JWT Token: " . substr($token, 0, 50) . "..." . PHP_EOL . PHP_EOL;

// Set up the Authorization header
$_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";

// Test data
$testData = [
    'patient_medication_id' => '1',
    'medication_name' => 'Methotrexate JWT Test',
    'scheduled_time' => '2024-12-16 10:00:00',
    'missed_time' => '2024-12-16 12:30:00',
    'reason' => 'forgot',
    'notes' => 'JWT test - forgot to take medication'
];

echo "Test Data:" . PHP_EOL;
echo json_encode($testData, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

// Mock php://input
stream_wrapper_unregister("php");
stream_wrapper_register("php", "MockInputStream");

class MockInputStream {
    private static $data;
    private $position = 0;
    
    public static function setData($data) {
        self::$data = $data;
    }
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        $this->position = 0;
        return true;
    }
    
    public function stream_read($count) {
        $ret = substr(self::$data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }
    
    public function stream_eof() {
        return $this->position >= strlen(self::$data);
    }
    
    public function stream_stat() {
        return array();
    }
}

MockInputStream::setData(json_encode($testData));

try {
    echo "Testing authentication..." . PHP_EOL;
    
    // Test the Auth::bearer() method
    $authPayload = Src\Middlewares\Auth::bearer();
    if ($authPayload) {
        echo "✓ JWT authentication successful" . PHP_EOL;
        echo "  User ID: " . $authPayload['uid'] . PHP_EOL;
        echo "  Role: " . $authPayload['role'] . PHP_EOL;
    } else {
        echo "❌ JWT authentication failed" . PHP_EOL;
        exit(1);
    }
    
    echo PHP_EOL . "Calling MissedDoseController..." . PHP_EOL;
    
    // Capture output
    ob_start();
    
    $controller = new Src\Controllers\MissedDoseController();
    $controller->reportMissedDose();
    
    $output = ob_get_clean();
    
    echo "Controller Response:" . PHP_EOL;
    echo $output . PHP_EOL;
    
    // Parse response
    $response = json_decode($output, true);
    
    if ($response && isset($response['success'])) {
        if ($response['success']) {
            echo "✅ Missed dose API with JWT PASSED!" . PHP_EOL;
            echo "Report ID: " . ($response['data']['id'] ?? 'N/A') . PHP_EOL;
        } else {
            echo "❌ API returned error: " . ($response['error'] ?? 'Unknown error') . PHP_EOL;
        }
    } else {
        echo "❌ Invalid response format" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . PHP_EOL;
} finally {
    // Restore php stream wrapper
    stream_wrapper_restore("php");
}
