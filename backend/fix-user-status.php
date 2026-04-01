<?php
require_once __DIR__ . '/src/bootstrap.php';

try {
    $db = Src\Config\DB::conn();
    
    // Update all users to active status
    $stmt = $db->prepare("UPDATE users SET status = 'ACTIVE' WHERE 1=1");
    $stmt->execute();
    
    echo "✓ All users set to ACTIVE status\n\n";
    
    // Show current users
    $stmt = $db->query("SELECT id, name, email, role, status FROM users");
    $users = $stmt->fetchAll();
    
    echo "Current Users:\n";
    echo str_repeat('=', 80) . "\n";
    foreach ($users as $user) {
        echo sprintf("%-3d %-25s %-30s %-10s %s\n", 
            $user['id'], 
            $user['name'], 
            $user['email'], 
            $user['role'], 
            $user['status']
        );
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
