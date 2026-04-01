<?php
/**
 * Fix Users Table - Add missing status column
 */

echo "=== FIXING USERS TABLE ===\n\n";

$host = '127.0.0.1';
$username = 'root';
$password = '';
$dbname = 'myrajourney';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Adding status column to users table...\n";
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Status column already exists\n";
    } else {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('ACTIVE', 'SUSPENDED', 'INACTIVE') DEFAULT 'ACTIVE' AFTER is_active");
        echo "✓ Status column added\n";
    }
    
    // Update all existing users to ACTIVE status
    $pdo->exec("UPDATE users SET status = 'ACTIVE' WHERE status IS NULL OR status = ''");
    echo "✓ All users set to ACTIVE status\n";
    
    echo "\n✓ Users table fixed!\n";
    
} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
