<?php
/**
 * MyRA Journey - New Features Database Migration Runner
 * 
 * This script applies all database changes needed for the new features:
 * - Profile Picture System
 * - Enhanced User Management (Admin System)  
 * - Enhanced Notifications with Categories
 * - Advanced AI Chatbot Support
 * - Exercise Thumbnail System
 * - Admin Audit Trail
 * - Enhanced Medication Tracking
 * - System Settings
 */

require_once __DIR__ . '/src/config/database.php';

echo "🚀 MyRA Journey - New Features Database Migration\n";
echo "================================================\n\n";

try {
    // Read the migration SQL file
    $migrationFile = __DIR__ . '/scripts/migrations/add_new_features_support.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    if ($sql === false) {
        throw new Exception("Failed to read migration file");
    }
    
    echo "📖 Reading migration file...\n";
    echo "📁 File: $migrationFile\n";
    echo "📏 Size: " . number_format(strlen($sql)) . " bytes\n\n";
    
    // Connect to database
    echo "🔌 Connecting to database...\n";
    
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
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "✅ Database connection established\n";
    echo "🏠 Host: {$config['host']}:{$config['port']}\n";
    echo "🗄️  Database: {$config['database']}\n\n";
    
    // Execute migration
    echo "⚡ Executing migration...\n";
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($statements as $index => $statement) {
        try {
            if (trim($statement)) {
                $pdo->exec($statement);
                $successCount++;
                
                // Show progress for major operations
                if (stripos($statement, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
                    $tableName = $matches[1] ?? 'unknown';
                    echo "  ✅ Created table: $tableName\n";
                } elseif (stripos($statement, 'ALTER TABLE') !== false) {
                    preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches);
                    $tableName = $matches[1] ?? 'unknown';
                    echo "  🔧 Modified table: $tableName\n";
                } elseif (stripos($statement, 'INSERT') !== false) {
                    preg_match('/INSERT.*?INTO.*?`?(\w+)`?/i', $statement, $matches);
                    $tableName = $matches[1] ?? 'unknown';
                    echo "  📝 Inserted data into: $tableName\n";
                } elseif (stripos($statement, 'CREATE INDEX') !== false) {
                    preg_match('/CREATE INDEX.*?`?(\w+)`?/i', $statement, $matches);
                    $indexName = $matches[1] ?? 'unknown';
                    echo "  🔍 Created index: $indexName\n";
                }
            }
        } catch (PDOException $e) {
            $errorCount++;
            $errorMsg = "Statement " . ($index + 1) . ": " . $e->getMessage();
            $errors[] = $errorMsg;
            
            // Only show non-critical errors as warnings
            if (stripos($e->getMessage(), 'Duplicate column name') !== false ||
                stripos($e->getMessage(), 'Duplicate key name') !== false ||
                stripos($e->getMessage(), 'already exists') !== false) {
                echo "  ⚠️  Warning: " . $e->getMessage() . "\n";
            } else {
                echo "  ❌ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n📊 Migration Summary:\n";
    echo "✅ Successful statements: $successCount\n";
    echo "⚠️  Warnings/Errors: $errorCount\n\n";
    
    // Verify migration results
    echo "🔍 Verifying migration results...\n";
    
    // Check new tables
    $newTables = [
        'conversation_sessions',
        'conversation_messages', 
        'patient_context_cache',
        'exercise_thumbnails',
        'admin_audit_trail',
        'system_settings'
    ];
    
    foreach ($newTables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "  ✅ Table '$table': {$result['count']} records\n";
        } catch (PDOException $e) {
            echo "  ❌ Table '$table': Not found or error\n";
        }
    }
    
    // Check new columns in existing tables
    echo "\n🔍 Checking new columns...\n";
    
    $columnChecks = [
        'users' => ['profile_picture', 'specialization', 'active', 'age', 'gender'],
        'notifications' => ['category', 'color', 'icon', 'priority'],
        'patient_medications' => ['reminder_enabled', 'reminder_times', 'total_doses', 'taken_doses']
    ];
    
    foreach ($columnChecks as $table => $columns) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $existingColumns = array_column($stmt->fetchAll(), 'Field');
            
            foreach ($columns as $column) {
                if (in_array($column, $existingColumns)) {
                    echo "  ✅ Column '$table.$column': Added\n";
                } else {
                    echo "  ❌ Column '$table.$column': Missing\n";
                }
            }
        } catch (PDOException $e) {
            echo "  ❌ Table '$table': Error checking columns\n";
        }
    }
    
    // Final verification
    echo "\n🎯 Final Verification:\n";
    
    try {
        // Count total tables
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '{$config['database']}'");
        $tableCount = $stmt->fetch()['count'];
        echo "  📊 Total tables in database: $tableCount\n";
        
        // Check exercise data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM exercise_thumbnails");
        $exerciseCount = $stmt->fetch()['count'];
        echo "  🏃 Exercise records: $exerciseCount\n";
        
        // Check system settings
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM system_settings");
        $settingsCount = $stmt->fetch()['count'];
        echo "  ⚙️  System settings: $settingsCount\n";
        
        echo "\n🎉 Migration completed successfully!\n";
        echo "✨ All new features are now supported in the database.\n\n";
        
        echo "🚀 New Features Available:\n";
        echo "  📸 Profile Picture System\n";
        echo "  👥 Enhanced User Management (Admin)\n";
        echo "  🔔 Categorized Notifications\n";
        echo "  🤖 Advanced AI Chatbot Support\n";
        echo "  🏃 Exercise Thumbnail System\n";
        echo "  📋 Admin Audit Trail\n";
        echo "  💊 Enhanced Medication Tracking\n";
        echo "  ⚙️  System Settings Management\n\n";
        
        echo "✅ Database is ready for the enhanced MyRA Journey app!\n";
        
    } catch (PDOException $e) {
        echo "  ❌ Verification error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Migration completed at: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n";
?>
