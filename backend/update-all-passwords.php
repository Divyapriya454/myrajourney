<?php
/**
 * Update All User Passwords by Role
 */

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/config/db.php';

use Src\Config\DB;

try {
    $db = DB::conn();
    
    echo "===========================================\n";
    echo "Updating All User Passwords\n";
    echo "===========================================\n\n";
    
    // New passwords by role
    $rolePasswords = [
        'ADMIN' => 'AS@Saveetha123',
        'DOCTOR' => 'Patrol@987',
        'PATIENT' => 'Welcome@456'
    ];
    
    // Get all users
    $stmt = $db->query("SELECT id, name, email, role FROM users ORDER BY role, name");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = [
        'ADMIN' => 0,
        'DOCTOR' => 0,
        'PATIENT' => 0
    ];
    
    foreach ($users as $user) {
        $role = $user['role'];
        $password = $rolePasswords[$role];
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update in database
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $user['id']]);
        
        echo "✅ Updated: {$user['name']} ({$user['email']}) → $password\n";
        $updated[$role]++;
    }
    
    echo "\n===========================================\n";
    echo "Summary\n";
    echo "===========================================\n";
    echo "Admins updated: {$updated['ADMIN']} users → AS@Saveetha123\n";
    echo "Doctors updated: {$updated['DOCTOR']} users → Patrol@987\n";
    echo "Patients updated: {$updated['PATIENT']} users → Welcome@456\n";
    echo "===========================================\n\n";
    
    // Verify one user from each role
    echo "Verifying passwords...\n";
    echo "-------------------------------------------\n";
    
    $testUsers = [
        ['email' => 'testadmin@test.com', 'password' => 'AS@Saveetha123', 'role' => 'ADMIN'],
        ['email' => 'doctor@test.com', 'password' => 'Patrol@987', 'role' => 'DOCTOR'],
        ['email' => 'deepankumar@gmail.com', 'password' => 'Welcome@456', 'role' => 'PATIENT'],
    ];
    
    foreach ($testUsers as $test) {
        $stmt = $db->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->execute([$test['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($test['password'], $user['password'])) {
            echo "✅ {$test['role']}: {$test['email']} - Password verified!\n";
        } else {
            echo "❌ {$test['role']}: {$test['email']} - Password verification FAILED!\n";
        }
    }
    
    echo "\n===========================================\n";
    echo "NEW LOGIN CREDENTIALS\n";
    echo "===========================================\n";
    echo "ADMINS (7 users):\n";
    echo "  Email: Any admin email\n";
    echo "  Password: AS@Saveetha123\n";
    echo "\n";
    echo "DOCTORS (2 users):\n";
    echo "  Email: doctor@test.com or avinash@gmail.com\n";
    echo "  Password: Patrol@987\n";
    echo "\n";
    echo "PATIENTS (2 users):\n";
    echo "  Email: deepankumar@gmail.com or patient@test.com\n";
    echo "  Password: Welcome@456\n";
    echo "===========================================\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
