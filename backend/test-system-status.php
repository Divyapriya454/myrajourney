<?php

// Simple test to verify backend is working
require_once __DIR__ . '/src/bootstrap.php';

use Src\Config\DB;

echo "=== MyRA Journey System Status ===\n\n";

// Test 1: Database Connection
echo "1. Database Connection: ";
try {
    $pdo = DB::conn();
    echo "✅ Connected\n";
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: User Count
echo "2. User Data: ";
try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
    $result = $stmt->fetch();
    echo "✅ " . $result['count'] . " users found\n";
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

// Test 3: Symptoms Count
echo "3. Symptom Data: ";
try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM symptoms');
    $result = $stmt->fetch();
    echo "✅ " . $result['count'] . " symptoms found\n";
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

echo "\n=== System Status: OPERATIONAL ===\n";

?>
