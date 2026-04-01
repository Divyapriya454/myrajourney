<?php
require_once __DIR__ . '/src/bootstrap.php';

try {
    $db = Src\Config\DB::conn();
    
    // Check if status column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "Adding status column to users table...\n";
        
        // Add status column
        $db->exec("
            ALTER TABLE users 
            ADD COLUMN status ENUM('ACTIVE', 'INACTIVE', 'SUSPENDED') 
            DEFAULT 'ACTIVE' 
            AFTER is_active
        ");
        
        echo "✓ Status column added\n\n";
        
        // Set all users to ACTIVE based on is_active
        $db->exec("UPDATE users SET status = 'ACTIVE' WHERE is_active = 1");
        $db->exec("UPDATE users SET status = 'INACTIVE' WHERE is_active = 0");
        
        echo "✓ Status values set based on is_active\n\n";
    } else {
        echo "✓ Status column already exists\n\n";
        
        // Make sure all users are ACTIVE
        $db->exec("UPDATE users SET status = 'ACTIVE' WHERE is_active = 1");
        echo "✓ All active users set to ACTIVE status\n\n";
    }
    
    // Show current users
    $stmt = $db->query("SELECT id, name, email, role, is_active, status FROM users");
    $users = $stmt->fetchAll();
    
    echo "Current Users:\n";
    echo str_repeat('=', 90) . "\n";
    printf("%-3s %-25s %-30s %-10s %-8s %s\n", 'ID', 'Name', 'Email', 'Role', 'Active', 'Status');
    echo str_repeat('=', 90) . "\n";
    
    foreach ($users as $user) {
        printf("%-3d %-25s %-30s %-10s %-8s %s\n", 
            $user['id'], 
            substr($user['name'], 0, 24), 
            substr($user['email'], 0, 29), 
            $user['role'], 
            $user['is_active'] ? 'Yes' : 'No',
            $user['status'] ?? 'NULL'
        );
    }
    
    echo "\n✓ Users table updated successfully\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
