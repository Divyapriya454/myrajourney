<?php
/**
 * Fix All User Passwords
 * Sets proper password hashes for all users
 */

// Load configuration
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/config/db.php';

use Src\Config\DB;

try {
    $db = DB::conn();
    
    echo "===========================================\n";
    echo "Fixing All User Passwords\n";
    echo "===========================================\n\n";
    
    // Define users with their passwords
    $users = [
        // Admins
        ['email' => 'admin@myrajourney.com', 'password' => 'Admin@123'],
        ['email' => 'testadmin@test.com', 'password' => 'Admin@123'],
        
        // Doctors
        ['email' => 'doctor@myrajourney.com', 'password' => 'Doctor@123'],
        ['email' => 'drvinoth@gmail.com', 'password' => 'Doctor@123'],
        ['email' => 'testdoctor@test.com', 'password' => 'Doctor@123'],
        
        // Patients
        ['email' => 'patient@myrajourney.com', 'password' => 'Patient@123'],
        ['email' => 'deepankumar@gmail.com', 'password' => 'Welcome@456'],
        ['email' => 'testpatient@test.com', 'password' => 'Patient@123'],
    ];
    
    $fixed = 0;
    $notFound = 0;
    
    foreach ($users as $userData) {
        $email = $userData['email'];
        $password = $userData['password'];
        
        // Check if user exists
        $stmt = $db->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "❌ User not found: $email\n";
            $notFound++;
            continue;
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update the password
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        
        echo "✅ Fixed password for: {$user['name']} ($email)\n";
        echo "   Password: $password\n";
        $fixed++;
    }
    
    echo "\n===========================================\n";
    echo "Summary:\n";
    echo "✅ Fixed: $fixed users\n";
    echo "❌ Not found: $notFound users\n";
    echo "===========================================\n\n";
    
    // Verify one user can login
    echo "Testing login for: deepankumar@gmail.com\n";
    $stmt = $db->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->execute(['deepankumar@gmail.com']);
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testUser && $testUser['password']) {
        $testPassword = 'Welcome@456';
        if (password_verify($testPassword, $testUser['password'])) {
            echo "✅ Password verification successful!\n";
            echo "   You can now login with: deepankumar@gmail.com / Welcome@456\n";
        } else {
            echo "❌ Password verification failed!\n";
        }
    } else {
        echo "❌ Test user not found or password is NULL\n";
    }
    
    echo "\n===========================================\n";
    echo "Login Credentials:\n";
    echo "===========================================\n";
    echo "Admin:   testadmin@test.com / Admin@123\n";
    echo "Doctor:  drvinoth@gmail.com / Doctor@123\n";
    echo "Patient: deepankumar@gmail.com / Welcome@456\n";
    echo "===========================================\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
