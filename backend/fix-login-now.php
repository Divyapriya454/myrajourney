<?php
/**
 * Emergency Login Fix
 * Updates password for existing users
 */

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/config/db.php';

use Src\Config\DB;

try {
    $db = DB::conn();
    
    echo "===========================================\n";
    echo "Emergency Login Fix\n";
    echo "===========================================\n\n";
    
    // This is the hash for "password"
    $defaultHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    // Update ALL users to have this password
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE password IS NULL OR password = ''");
    $stmt->execute([$defaultHash]);
    $updated = $stmt->rowCount();
    
    echo "✅ Updated $updated users with NULL passwords\n\n";
    
    // Show all users
    $stmt = $db->query("SELECT id, name, email, role FROM users ORDER BY role, name");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Users in database:\n";
    echo "-------------------------------------------\n";
    foreach ($users as $user) {
        echo sprintf("%-5s %-20s %-30s %s\n", 
            $user['id'], 
            $user['name'], 
            $user['email'], 
            $user['role']
        );
    }
    
    echo "\n===========================================\n";
    echo "Login Credentials (ALL USERS):\n";
    echo "===========================================\n";
    echo "Password for ALL users: password\n";
    echo "-------------------------------------------\n";
    foreach ($users as $user) {
        echo "{$user['email']} / password\n";
    }
    echo "===========================================\n\n";
    
    echo "Try logging in now with any email and password: password\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    echo "This means the 'users' table doesn't exist.\n";
    echo "You need to create the database first.\n\n";
    echo "Follow these steps:\n";
    echo "1. Open http://localhost/phpmyadmin\n";
    echo "2. Click on 'myrajourney' database\n";
    echo "3. Click 'Import' tab\n";
    echo "4. Import: backend/scripts/migrations/001_users.sql\n";
    echo "5. Then run this script again\n";
}
