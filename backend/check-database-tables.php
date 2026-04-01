<?php
/**
 * Check Database Tables
 */

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/config/db.php';

use Src\Config\DB;

try {
    $db = DB::conn();
    
    echo "===========================================\n";
    echo "Checking Database Tables\n";
    echo "===========================================\n\n";
    
    // Show current database
    $stmt = $db->query("SELECT DATABASE()");
    $currentDb = $stmt->fetchColumn();
    echo "Current Database: $currentDb\n\n";
    
    // List all tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables found: " . count($tables) . "\n";
    echo "-------------------------------------------\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    echo "\n";
    
    // Check for users in different possible tables
    $possibleUserTables = ['users', 'user', 'myra_users', 'tbl_users'];
    
    echo "Checking for user data...\n";
    echo "-------------------------------------------\n";
    foreach ($possibleUserTables as $tableName) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM `$tableName`");
            $count = $stmt->fetchColumn();
            echo "✅ Table '$tableName' exists with $count records\n";
            
            // Show sample data
            $stmt = $db->query("SELECT * FROM `$tableName` LIMIT 3");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($users as $user) {
                echo "   - " . ($user['email'] ?? $user['username'] ?? 'N/A') . "\n";
            }
        } catch (Exception $e) {
            echo "❌ Table '$tableName' not found\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
