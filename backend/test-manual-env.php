<?php

require __DIR__ . '/src/bootstrap.php';

echo "=== Testing Manual Environment Loading ===" . PHP_EOL . PHP_EOL;

// Manually set the JWT_SECRET for testing
$_ENV['JWT_SECRET'] = 'myrajourney_secret_key_2024';

echo "Manually set JWT_SECRET: " . $_ENV['JWT_SECRET'] . PHP_EOL . PHP_EOL;

// Test JWT with manual secret
$payload = [
    'uid' => 1,
    'role' => 'PATIENT',
    'email' => 'test@example.com',
    'exp' => time() + 3600
];

$secret = $_ENV['JWT_SECRET'];
$token = Src\Utils\Jwt::encode($payload, $secret);

echo "Generated token: " . substr($token, 0, 50) . "..." . PHP_EOL . PHP_EOL;

// Set up Authorization header
$_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";

// Test Auth::bearer()
echo "Testing Auth::bearer()..." . PHP_EOL;
$authPayload = Src\Middlewares\Auth::bearer();

if ($authPayload) {
    echo "✓ Authentication successful!" . PHP_EOL;
    echo "User ID: " . $authPayload['uid'] . PHP_EOL;
    echo "Role: " . $authPayload['role'] . PHP_EOL . PHP_EOL;
    
    // Now test the MissedDoseController
    echo "Testing MissedDoseController with valid auth..." . PHP_EOL;
    
    // Test data
    $testData = [
        'patient_medication_id' => '1',
        'medication_name' => 'Methotrexate Manual Test',
        'scheduled_time' => '2024-12-16 10:00:00',
        'missed_time' => '2024-12-16 12:30:00',
        'reason' => 'forgot',
        'notes' => 'Manual test with valid JWT'
    ];
    
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
        ob_start();
        $controller = new Src\Controllers\MissedDoseController();
        $controller->reportMissedDose();
        $output = ob_get_clean();
        
        echo "Controller Response:" . PHP_EOL;
        echo $output . PHP_EOL;
        
        $response = json_decode($output, true);
        if ($response && isset($response['success']) && $response['success']) {
            echo "✅ MissedDoseController test PASSED!" . PHP_EOL;
            echo "Report ID: " . ($response['data']['id'] ?? 'N/A') . PHP_EOL;
        } else {
            echo "❌ MissedDoseController test failed" . PHP_EOL;
            if ($response && isset($response['error'])) {
                echo "Error: " . json_encode($response['error']) . PHP_EOL;
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . PHP_EOL;
    } finally {
        stream_wrapper_restore("php");
    }
    
} else {
    echo "❌ Authentication failed" . PHP_EOL;
}
