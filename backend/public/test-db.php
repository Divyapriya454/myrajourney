<?php
require __DIR__ . '/../src/bootstrap.php';

use Src\Config\DB;

try {
    $conn = DB::conn();
    echo "Database connection: SUCCESS\n";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users table exists, count: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "Database connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
}


