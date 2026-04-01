<?php
/**
 * Fix Admin Login - Create/Update Admin User
 * This script ensures the admin user exists with correct credentials
 */

require __DIR__ . '/src/bootstrap.php';

use Src\Config\DB;

echo "===========================================\n";
echo "FIXING ADMIN LOGIN\n";
echo "===========================================\n\n";

try {
    $pdo = DB::conn();
    
    // Admin credentials
    $adminEmail = 'testadmin@test.com';
    $adminPassword = 'Admin@123';
    $adminName = 'Test Admin';
    $adminPhone = '1234567890';
    
    // Hash the password
    $passwordHash = password_hash($adminPassword, PASSWORD_BCRYPT);
    
    echo "Checking for existing admin user...\n";
    
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingAdmin) {
        echo "✓ Admin user found (ID: {$existingAdmin['id']})\n";
        echo "  Name: {$existingAdmin['name']}\n";
        echo "  Email: {$existingAdmin['email']}\n";
        echo "  Role: {$existingAdmin['role']}\n";
        echo "  Status: {$existingAdmin['status']}\n\n";
        
        // Update password and ensure status is ACTIVE
        echo "Updating admin password and status...\n";
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password_hash = ?, 
                status = 'ACTIVE',
                role = 'ADMIN',
                updated_at = NOW()
            WHERE email = ?
        ");
        $stmt->execute([$passwordHash, $adminEmail]);
        
        echo "✅ Admin password updated successfully!\n\n";
        
    } else {
        echo "✗ Admin user not found. Creating new admin user...\n";
        
        // Create new admin user
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password_hash, role, phone, status, created_at, updated_at)
            VALUES (?, ?, ?, 'ADMIN', ?, 'ACTIVE', NOW(), NOW())
        ");
        $stmt->execute([$adminName, $adminEmail, $passwordHash, $adminPhone]);
        
        $adminId = $pdo->lastInsertId();
        echo "✅ Admin user created successfully! (ID: $adminId)\n\n";
    }
    
    // Verify the admin can login
    echo "Verifying admin credentials...\n";
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role, status FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($adminPassword, $admin['password_hash'])) {
        echo "✅ Password verification successful!\n";
        echo "✅ Admin login is now working!\n\n";
    } else {
        echo "❌ Password verification failed!\n";
        echo "❌ There may be an issue with the password hash.\n\n";
    }
    
    // Also create/update alternative admin accounts
    echo "Setting up alternative admin accounts...\n";
    
    $alternativeAdmins = [
        ['email' => 'admin@myrajourney.com', 'name' => 'System Admin', 'phone' => '1234567891'],
        ['email' => 'admin@test.com', 'name' => 'Admin User', 'phone' => '1234567892']
    ];
    
    foreach ($alternativeAdmins as $altAdmin) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$altAdmin['email']]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update existing
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password_hash = ?, 
                    status = 'ACTIVE',
                    role = 'ADMIN',
                    updated_at = NOW()
                WHERE email = ?
            ");
            $stmt->execute([$passwordHash, $altAdmin['email']]);
            echo "  ✓ Updated: {$altAdmin['email']}\n";
        } else {
            // Create new
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password_hash, role, phone, status, created_at, updated_at)
                VALUES (?, ?, ?, 'ADMIN', ?, 'ACTIVE', NOW(), NOW())
            ");
            $stmt->execute([$altAdmin['name'], $altAdmin['email'], $passwordHash, $altAdmin['phone']]);
            echo "  ✓ Created: {$altAdmin['email']}\n";
        }
    }
    
    echo "\n===========================================\n";
    echo "ADMIN LOGIN CREDENTIALS\n";
    echo "===========================================\n";
    echo "Email:    testadmin@test.com\n";
    echo "Password: Admin@123\n";
    echo "\nAlternative accounts:\n";
    echo "- admin@myrajourney.com / Admin@123\n";
    echo "- admin@test.com / Admin@123\n";
    echo "===========================================\n\n";
    
    echo "✅ ALL DONE! Admin login should now work.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
