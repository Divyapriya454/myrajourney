<?php

require __DIR__ . '/src/bootstrap.php';

echo "=== Testing MissedDoseController Directly ===" . PHP_EOL . PHP_EOL;

// Set up authentication
$_SERVER['auth'] = [
    'uid' => 1,
    'role' => 'PATIENT',
    'email' => 'test@example.com'
];

// Test data
$testData = [
    'patient_medication_id' => '1',
    'medication_name' => 'Methotrexate Direct Test',
    'scheduled_time' => '2024-12-16 10:00:00',
    'missed_time' => '2024-12-16 12:30:00',
    'reason' => 'forgot',
    'notes' => 'Direct controller test'
];

// Mock php://input by creating a temporary file
$tempFile = tmpfile();
fwrite($tempFile, json_encode($testData));
rewind($tempFile);

// Override php://input stream
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
    echo "Calling MissedDoseController->reportMissedDose()..." . PHP_EOL;
    
    // Capture output
    ob_start();
    
    $controller = new Src\Controllers\MissedDoseController();
    $controller->reportMissedDose();
    
    $output = ob_get_clean();
    
    echo "Controller Output:" . PHP_EOL;
    echo $output . PHP_EOL;
    
    // Try to parse as JSON
    $response = json_decode($output, true);
    
    if ($response && isset($response['success'])) {
        if ($response['success']) {
            echo "✅ Controller test PASSED!" . PHP_EOL;
            echo "Report ID: " . ($response['data']['id'] ?? 'N/A') . PHP_EOL;
        } else {
            echo "❌ Controller returned error: " . ($response['error'] ?? 'Unknown error') . PHP_EOL;
        }
    } else {
        echo "❌ Invalid JSON response from controller" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . PHP_EOL;
} finally {
    // Restore php stream wrapper
    stream_wrapper_restore("php");
}
