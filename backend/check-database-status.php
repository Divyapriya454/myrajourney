<?php
/**
 * MyRA Journey - Database Status Checker
 * 
 * This script checks if the database has all the required changes
 * for the new features implemented in the app.
 */

require_once __DIR__ . '/src/config/database.php';

echo "🔍 MyRA Journey - Database Status Check\n";
echo "======================================\n\n";

try {
    // Connect to database
    $config = [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? 3306,
        'database' => $_ENV['DB_NAME'] ?? 'myrajourney',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? ''
    ];
    
    $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Connected to database: {$config['database']}\n\n";
    
    $needsMigration = false;
    
    // Check for new tables
    echo "📋 Checking New Tables:\n";
    $newTables = [
        'conversation_sessions' => 'AI Chatbot conversation tracking',
        'conversation_messages' => 'AI Chatbot message history',
        'patient_context_cache' => 'AI Chatbot patient context',
        'exercise_thumbnails' => 'Exercise thumbnail management',
        'admin_audit_trail' => 'Admin action logging',
        'system_settings' => 'System configuration'
    ];
    
    foreach ($newTables as $table => $description) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
            echo "  ✅ $table - $description\n";
        } catch (PDOException $e) {
            echo "  ❌ $table - $description (MISSING)\n";
            $needsMigration = true;
        }
    }
    
    // Check for new columns
    echo "\n🔧 Checking New Columns:\n";
    $columnChecks = [
        'users' => [
            'profile_picture' => 'Profile picture support',
            'specialization' => 'Doctor specialization',
            'active' => 'User active status',
            'age' => 'User age field',
            'gender' => 'User gender field'
        ],
        'notifications' => [
            'category' => 'Notification categories',
            'color' => 'Notification colors',
            'icon' => 'Notification icons',
            'priority' => 'Notification priority'
        ],
        'patient_medications' => [
            'reminder_enabled' => 'Medication reminders',
            'reminder_times' => 'Reminder time settings',
            'total_doses' => 'Dose tracking',
            'taken_doses' => 'Adherence tracking'
        ]
    ];
    
    foreach ($columnChecks as $table => $columns) {
        echo "\n  Table: $table\n";
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $existingColumns = array_column($stmt->fetchAll(), 'Field');
            
            foreach ($columns as $column => $description) {
                if (in_array($column, $existingColumns)) {
                    echo "    ✅ $column - $description\n";
                } else {
                    echo "    ❌ $column - $description (MISSING)\n";
                    $needsMigration = true;
                }
            }
        } catch (PDOException $e) {
            echo "    ❌ Table $table not found\n";
            $needsMigration = true;
        }
    }
    
    // Check data
    echo "\n📊 Checking Data:\n";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM exercise_thumbnails");
        $exerciseCount = $stmt->fetch()['count'];
        if ($exerciseCount >= 10) {
            echo "  ✅ Exercise thumbnails: $exerciseCount records\n";
        } else {
            echo "  ⚠️  Exercise thumbnails: $exerciseCount records (Expected 10+)\n";
            $needsMigration = true;
        }
    } catch (PDOException $e) {
        echo "  ❌ Exercise thumbnails: Table not found\n";
        $needsMigration = true;
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM system_settings");
        $settingsCount = $stmt->fetch()['count'];
        if ($settingsCount >= 10) {
            echo "  ✅ System settings: $settingsCount records\n";
        } else {
            echo "  ⚠️  System settings: $settingsCount records (Expected 10+)\n";
            $needsMigration = true;
        }
    } catch (PDOException $e) {
        echo "  ❌ System settings: Table not found\n";
        $needsMigration = true;
    }
    
    // Summary
    echo "\n" . str_repeat("=", 50) . "\n";
    
    if ($needsMigration) {
        echo "❌ DATABASE MIGRATION REQUIRED\n\n";
        echo "Your database needs to be updated to support the new features.\n";
        echo "Please run the migration script:\n\n";
        echo "  php backend/run-new-features-migration.php\n\n";
        echo "New features that need database support:\n";
        echo "  📸 Profile Picture System\n";
        echo "  👥 Enhanced User Management (Admin)\n";
        echo "  🔔 Categorized Notifications\n";
        echo "  🤖 Advanced AI Chatbot Support\n";
        echo "  🏃 Exercise Thumbnail System\n";
        echo "  📋 Admin Audit Trail\n";
        echo "  💊 Enhanced Medication Tracking\n";
        echo "  ⚙️  System Settings Management\n";
    } else {
        echo "✅ DATABASE IS UP TO DATE\n\n";
        echo "All required tables and columns are present.\n";
        echo "Your database supports all the new features!\n";
    }
    
    echo str_repeat("=", 50) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
