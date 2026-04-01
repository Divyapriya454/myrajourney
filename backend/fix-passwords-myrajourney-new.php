<?php
/**
 * Fix Passwords for myrajourney_new database
 */

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/config/db.php';

use Src\Config\DB;

try {
    $db = DB::conn();
    
    echo "===========================================\n";
    echo "Fixing Passwords in myrajourney_new\n";
    echo "===========================================\n\n";
    
    // Get current database
    $stmt = $db->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Database: " . $result['db_name'] . "\n\n";
    
    // Show all users with their current password status
    $stmt = $db->query("SELECT id, name, email, role, password FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current users:\n";
    echo "-------------------------------------------\n";
    foreach ($users as $user) {
        $hasPassword = !empty($user['password']) ? '✅' : '❌';
        echo sprintf("%s %-5s %-25s %-35s %s\n", 
            $hasPassword,
            $user['id'], 
            $user['name'], 
            $user['email'], 
            $user['role']
        );
    }
    echo "\n";
    
    // Define passwords for specific users
    $passwords = [
        'deepankumar@gmail.com' => 'Welcome@456',
        'testadmin@test.com' => 'Admin@123',
        'admin@myrajourney.com' => 'Admin@123',
        'drvinoth@gmail.com' => 'Doctor@123',
        'doctor@myrajourney.com' => 'Doctor@123',
        'patient@myrajourney.com' => 'Patient@123',
    ];
    
    echo "Setting passwords...\n";
    echo "-------------------------------------------\n";
    
    $fixed = 0;
    foreach ($users as $user) {
        $email = $user['email'];
        
        // Determine password
        if (isset($passwords[$email])) {
            $password = $passwords[$email];
        } else {
            // Default password based on role
            switch ($user['role']) {
                case 'ADMIN':
                    $password = 'Admin@123';
                    break;
                case 'DOCTOR':
                    $password = 'Doctor@123';
                    break;
                case 'PATIENT':
                    $password = 'Patient@123';
                    break;
                default:
                    $password = 'Password@123';
            }
        }
        
        // Hash and update
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $user['id']]);
        
        echo "✅ {$user['name']} ({$email}) → $password\n";
        $fixed++;
    }
    
    echo "\n===========================================\n";
    echo "Summary: Fixed $fixed users\n";
    echo "===========================================\n\n";
    
    // Test login for deepankumar
    echo "Testing login for deepankumar@gmail.com...\n";
    $stmt = $db->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->execute(['deepankumar@gmail.com']);
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testUser && password_verify('Welcome@456', $testUser['password'])) {
        echo "✅ Password verification successful!\n\n";
    } else {
        echo "❌ Password verification failed!\n\n";
    }
    
    echo "===========================================\n";
    echo "Login Credentials:\n";
    echo "===========================================\n";
    echo "Patient: deepankumar@gmail.com / Welcome@456\n";
    echo "Admin:   testadmin@test.com / Admin@123\n";
    echo "Doctor:  drvinoth@gmail.com / Doctor@123\n";
    echo "===========================================\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
