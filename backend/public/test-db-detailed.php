<?php
require __DIR__ . '/../src/bootstrap.php';

use Src\Config\Config;

$host = Config::get('DB_HOST', '127.0.0.1');
$dbname = Config::get('DB_NAME', 'myrajourney');
$user = Config::get('DB_USER', 'root');
$pass = Config::get('DB_PASS', '');

echo "Attempting connection with:\n";
echo "Host: $host\n";
echo "DB: $dbname\n";
echo "User: $user\n";
echo "Pass: " . ($pass ? '***SET***' : 'EMPTY') . "\n\n";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "SUCCESS: Connected to database!\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users table count: " . $result['count'] . "\n";
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
}


