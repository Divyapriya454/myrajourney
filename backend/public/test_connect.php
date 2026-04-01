<?php
$host = '127.0.0.1';   // force TCP
$port = 3306;
$user = 'root';
$pass = '';            // empty password
$db   = 'myrajourney'; // your DB name

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "OK: connected to {$db}\n";
} catch (PDOException $e) {
    echo "CONNECT FAILED: (" . $e->getCode() . ") " . $e->getMessage() . "\n";
}
