<?php
/**
 * Direct Login Test - Simulates exact Android request
 */

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/config/db.php';

use Src\Config\DB;

echo "===========================================\n";
echo "Direct Login Test\n";
echo "===========================================\n\n";

$email = 'deepankumar@gmail.com';
$password = 'Welcome@456';

try {
    $db = DB::conn();
    
    echo "Step 1: Finding user...\n";
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User not found!\n";
        exit(1);
    }
    
    echo "✅ User found: {$user['name']}\n";
    echo "   ID: {$user['id']}\n";
    echo "   Email: {$user['email']}\n";
    echo "   Role: {$user['role']}\n";
    echo "   Password hash exists: " . (!empty($user['password']) ? 'YES' : 'NO') . "\n\n";
    
    echo "Step 2: Verifying password...\n";
    echo "   Input password: $password\n";
    echo "   Stored hash: " . substr($user['password'], 0, 20) . "...\n";
    
    if (password_verify($password, $user['password'])) {
        echo "✅ Password verification SUCCESS!\n\n";
        
        echo "Step 3: Checking account status...\n";
        echo "   Status: {$user['status']}\n";
        
        if ($user['status'] === 'ACTIVE') {
            echo "✅ Account is ACTIVE\n\n";
            echo "===========================================\n";
            echo "LOGIN SHOULD WORK!\n";
            echo "===========================================\n";
        } else {
            echo "❌ Account is NOT ACTIVE (status: {$user['status']})\n";
        }
    } else {
        echo "❌ Password verification FAILED!\n\n";
        
        echo "Debugging:\n";
        echo "-------------------------------------------\n";
        
        // Try to hash the password and compare
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        echo "New hash would be: " . substr($newHash, 0, 20) . "...\n";
        
        // Check if password field is NULL
        if ($user['password'] === null) {
            echo "ERROR: Password field is NULL!\n";
        } else if (empty($user['password'])) {
            echo "ERROR: Password field is empty!\n";
        } else {
            echo "Password field has value but doesn't match\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
