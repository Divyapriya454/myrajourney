<?php
/**
 * Test Database Connection
 */

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/config/db.php';

use Src\Config\Config;
use Src\Config\DB;

echo "===========================================\n";
echo "Database Connection Test\n";
echo "===========================================\n\n";

echo "Environment Variables:\n";
echo "-------------------------------------------\n";
echo "DB_HOST: " . Config::get('DB_HOST', 'NOT SET') . "\n";
echo "DB_NAME: " . Config::get('DB_NAME', 'NOT SET') . "\n";
echo "DB_USER: " . Config::get('DB_USER', 'NOT SET') . "\n";
echo "DB_PASS: " . (Config::get('DB_PASS') ? '***' : '(empty)') . "\n\n";

try {
    echo "Attempting to connect...\n";
    $db = DB::conn();
    echo "✅ Connection successful!\n\n";
    
    // Get current database
    $stmt = $db->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current Database: " . $result['db_name'] . "\n\n";
    
    // List tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables found: " . count($tables) . "\n";
    echo "-------------------------------------------\n";
    if (count($tables) > 0) {
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    } else {
        echo "(No tables found)\n";
    }
    echo "\n";
    
    // Check for users table
    if (in_array('users', $tables)) {
        echo "Checking users table...\n";
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetchColumn();
        echo "✅ Users table exists with $count records\n\n";
        
        // Show sample users
        $stmt = $db->query("SELECT id, name, email, role FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sample users:\n";
        echo "-------------------------------------------\n";
        foreach ($users as $user) {
            echo sprintf("%-5s %-20s %-30s %s\n", 
                $user['id'], 
                $user['name'], 
                $user['email'], 
                $user['role']
            );
        }
    } else {
        echo "❌ Users table NOT found\n";
        echo "You need to run migrations!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n===========================================\n";
