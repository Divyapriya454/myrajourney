<?php
/**
 * Complete Database Setup
 * Runs all migrations in order
 */

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/config/db.php';

use Src\Config\DB;

try {
    $db = DB::conn();
    
    echo "===========================================\n";
    echo "MYRA Journey - Complete Database Setup\n";
    echo "===========================================\n\n";
    
    // List of migration files in order
    $migrations = [
        '001_users.sql',
        '002_profiles.sql',
        '003_settings.sql',
        '004_appointments.sql',
        '005_reports.sql',
        '006_medications.sql',
        '007_rehab.sql',
        '008_notifications.sql',
        '009_education.sql',
        '010_symptoms_metrics.sql',
        '011_password_resets.sql',
        '012_default_users.sql',
        '013_education_seed.sql',
        '014_specific_users.sql',
        '015_chatbot_logs.sql',
        '016_enhanced_chatbot_schema.sql',
        'add_medication_status.sql',
        'add_new_features_support.sql',
        'add_rehab_exercise_tracking.sql',
    ];
    
    $migrationsDir = __DIR__ . '/scripts/migrations/';
    $success = 0;
    $failed = 0;
    
    foreach ($migrations as $migration) {
        $filePath = $migrationsDir . $migration;
        
        if (!file_exists($filePath)) {
            echo "⚠️  Skipping $migration (file not found)\n";
            continue;
        }
        
        echo "Running: $migration ... ";
        
        try {
            $sql = file_get_contents($filePath);
            
            // Split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (empty($statement)) continue;
                $db->exec($statement);
            }
            
            echo "✅ Success\n";
            $success++;
        } catch (Exception $e) {
            echo "❌ Failed: " . $e->getMessage() . "\n";
            $failed++;
        }
    }
    
    echo "\n===========================================\n";
    echo "Migration Summary:\n";
    echo "✅ Success: $success\n";
    echo "❌ Failed: $failed\n";
    echo "===========================================\n\n";
    
    // Now fix passwords
    echo "Fixing user passwords...\n";
    echo "-------------------------------------------\n";
    
    $users = [
        ['email' => 'admin@myrajourney.com', 'password' => 'Admin@123'],
        ['email' => 'testadmin@test.com', 'password' => 'Admin@123'],
        ['email' => 'doctor@myrajourney.com', 'password' => 'Doctor@123'],
        ['email' => 'drvinoth@gmail.com', 'password' => 'Doctor@123'],
        ['email' => 'testdoctor@test.com', 'password' => 'Doctor@123'],
        ['email' => 'patient@myrajourney.com', 'password' => 'Patient@123'],
        ['email' => 'deepankumar@gmail.com', 'password' => 'Welcome@456'],
        ['email' => 'testpatient@test.com', 'password' => 'Patient@123'],
    ];
    
    $fixed = 0;
    foreach ($users as $userData) {
        $email = $userData['email'];
        $password = $userData['password'];
        
        $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            continue;
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        
        echo "✅ {$user['name']} ($email) → $password\n";
        $fixed++;
    }
    
    echo "\n===========================================\n";
    echo "Database Setup Complete!\n";
    echo "===========================================\n";
    echo "Fixed $fixed user passwords\n\n";
    echo "Login Credentials:\n";
    echo "-------------------------------------------\n";
    echo "Admin:   testadmin@test.com / Admin@123\n";
    echo "Doctor:  drvinoth@gmail.com / Doctor@123\n";
    echo "Patient: deepankumar@gmail.com / Welcome@456\n";
    echo "===========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ Fatal Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
