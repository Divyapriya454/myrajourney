<?php
/**
 * Android Connection Test Endpoint
 * Access from Android app to verify connectivity
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$response = [
    'success' => true,
    'message' => 'Backend is accessible from Android',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
        'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
    ],
    'request_info' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    ]
];

// Test database connection
try {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = explode("\n", file_get_contents($envFile));
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $_ENV[trim($parts[0])] = trim($parts[1]);
            }
        }
    }
    
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $dbname = $_ENV['DB_NAME'] ?? 'myrajourney';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $response['database'] = [
        'connected' => true,
        'host' => $host,
        'database' => $dbname
    ];
    
    // Count tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $response['database']['tables_count'] = count($tables);
    
    // Count users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    $response['database']['users_count'] = $userCount;
    
} catch (PDOException $e) {
    $response['database'] = [
        'connected' => false,
        'error' => $e->getMessage()
    ];
}

// Test API endpoints
$response['api_endpoints'] = [
    'login' => '/api/v1/auth/login',
    'appointments' => '/api/v1/appointments',
    'medications' => '/api/v1/patient-medications',
    'reports' => '/api/v1/reports',
    'chatbot' => '/api/v1/chatbot/message'
];

echo json_encode($response, JSON_PRETTY_PRINT);
