<?php
/**
 * List All Users with Their Passwords
 */

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/config/db.php';

use Src\Config\DB;

try {
    $db = DB::conn();
    
    echo "===========================================\n";
    echo "All Users in Database\n";
    echo "===========================================\n\n";
    
    // Get all users
    $stmt = $db->query("SELECT id, name, email, role, status FROM users ORDER BY role, name");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Define passwords based on role and specific users
    $specificPasswords = [
        'deepankumar@gmail.com' => 'Welcome@456',
        'testadmin@test.com' => 'Admin@123',
        'admin@myrajourney.com' => 'Admin@123',
        'drvinoth@gmail.com' => 'Doctor@123',
        'doctor@myrajourney.com' => 'Doctor@123',
        'patient@myrajourney.com' => 'Patient@123',
    ];
    
    $adminUsers = [];
    $doctorUsers = [];
    $patientUsers = [];
    
    foreach ($users as $user) {
        $email = $user['email'];
        
        // Determine password
        if (isset($specificPasswords[$email])) {
            $password = $specificPasswords[$email];
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
        
        $userInfo = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $email,
            'password' => $password,
            'status' => $user['status']
        ];
        
        // Group by role
        switch ($user['role']) {
            case 'ADMIN':
                $adminUsers[] = $userInfo;
                break;
            case 'DOCTOR':
                $doctorUsers[] = $userInfo;
                break;
            case 'PATIENT':
                $patientUsers[] = $userInfo;
                break;
        }
    }
    
    // Display Admins
    if (!empty($adminUsers)) {
        echo "ADMIN USERS (" . count($adminUsers) . ")\n";
        echo "===========================================\n";
        foreach ($adminUsers as $user) {
            echo sprintf("%-5s %-25s\n", "ID:", $user['id']);
            echo sprintf("%-5s %-25s\n", "Name:", $user['name']);
            echo sprintf("%-5s %-25s\n", "Email:", $user['email']);
            echo sprintf("%-5s %-25s\n", "Pass:", $user['password']);
            echo sprintf("%-5s %-25s\n", "Status:", $user['status']);
            echo "-------------------------------------------\n";
        }
        echo "\n";
    }
    
    // Display Doctors
    if (!empty($doctorUsers)) {
        echo "DOCTOR USERS (" . count($doctorUsers) . ")\n";
        echo "===========================================\n";
        foreach ($doctorUsers as $user) {
            echo sprintf("%-5s %-25s\n", "ID:", $user['id']);
            echo sprintf("%-5s %-25s\n", "Name:", $user['name']);
            echo sprintf("%-5s %-25s\n", "Email:", $user['email']);
            echo sprintf("%-5s %-25s\n", "Pass:", $user['password']);
            echo sprintf("%-5s %-25s\n", "Status:", $user['status']);
            echo "-------------------------------------------\n";
        }
        echo "\n";
    }
    
    // Display Patients
    if (!empty($patientUsers)) {
        echo "PATIENT USERS (" . count($patientUsers) . ")\n";
        echo "===========================================\n";
        foreach ($patientUsers as $user) {
            echo sprintf("%-5s %-25s\n", "ID:", $user['id']);
            echo sprintf("%-5s %-25s\n", "Name:", $user['name']);
            echo sprintf("%-5s %-25s\n", "Email:", $user['email']);
            echo sprintf("%-5s %-25s\n", "Pass:", $user['password']);
            echo sprintf("%-5s %-25s\n", "Status:", $user['status']);
            echo "-------------------------------------------\n";
        }
        echo "\n";
    }
    
    // Summary
    echo "===========================================\n";
    echo "SUMMARY\n";
    echo "===========================================\n";
    echo "Total Users: " . count($users) . "\n";
    echo "Admins: " . count($adminUsers) . "\n";
    echo "Doctors: " . count($doctorUsers) . "\n";
    echo "Patients: " . count($patientUsers) . "\n";
    echo "===========================================\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
