<?php

require __DIR__ . '/src/bootstrap.php';

echo "=== Testing Environment Variables ===" . PHP_EOL . PHP_EOL;

echo "JWT_SECRET from \$_ENV: " . ($_ENV['JWT_SECRET'] ?? 'NOT SET') . PHP_EOL;
echo "JWT_SECRET from Config::get(): " . (Src\Config\Config::get('JWT_SECRET') ?? 'NOT SET') . PHP_EOL;

// Test JWT encoding/decoding with the loaded secret
$secret = $_ENV['JWT_SECRET'] ?? 'fallback_secret';
echo "Using secret: $secret" . PHP_EOL . PHP_EOL;

$payload = [
    'uid' => 1,
    'role' => 'PATIENT',
    'email' => 'test@example.com',
    'exp' => time() + 3600
];

try {
    echo "Testing JWT encode..." . PHP_EOL;
    $token = Src\Utils\Jwt::encode($payload, $secret);
    echo "✓ JWT encoded successfully: " . substr($token, 0, 50) . "..." . PHP_EOL;
    
    echo "Testing JWT decode..." . PHP_EOL;
    $decoded = Src\Utils\Jwt::decode($token, $secret);
    echo "✓ JWT decoded successfully" . PHP_EOL;
    echo "Decoded payload: " . json_encode($decoded) . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ JWT test failed: " . $e->getMessage() . PHP_EOL;
}
