<?php
/**
 * Test All User Logins
 */

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/config/db.php';

use Src\Config\DB;

$testUsers = [
    ['email' => 'deepankumar@gmail.com', 'password' => 'Welcome@456'],
    ['email' => 'testadmin@test.com', 'password' => 'Admin@123'],
    ['email' => 'doctor@test.com', 'password' => 'Doctor@123'],
    ['email' => 'avinash@gmail.com', 'password' => 'Doctor@123'],
    ['email' => 'patient@test.com', 'password' => 'Patient@123'],
];

echo "===========================================\n";
echo "Testing All User Logins\n";
echo "===========================================\n\n";

$db = DB::conn();

foreach ($testUsers as $credentials) {
    $email = $credentials['email'];
    $password = $credentials['password'];
    
    echo "Testing: $email\n";
    echo "-------------------------------------------\n";
    
    $stmt = $db->prepare("SELECT id, name, email, role, password, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User not found\n\n";
        continue;
    }
    
    echo "User: {$user['name']}\n";
    echo "Role: {$user['role']}\n";
    echo "Status: {$user['status']}\n";
    
    if (password_verify($password, $user['password'])) {
        echo "✅ Password: CORRECT\n";
    } else {
        echo "❌ Password: WRONG - Fixing...\n";
        
        // Fix the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        
        // Verify fix
        $stmt = $db->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $newUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $newUser['password'])) {
            echo "✅ Password: FIXED\n";
        } else {
            echo "❌ Password: STILL WRONG\n";
        }
    }
    
    echo "\n";
}

echo "===========================================\n";
echo "All users tested and fixed!\n";
echo "===========================================\n";
