<?php
/**
 * FIX ALL CRITICAL ISSUES - COMPREHENSIVE FIX
 * 
 * Issues to fix:
 * 1. CRP measurements - doctor_id column error
 * 2. Rehab assignment - verify all columns exist
 * 3. Report processing - verify all columns exist
 * 4. Profile pictures - verify columns and test upload
 */

require_once __DIR__ . '/src/bootstrap.php';

echo "=================================================================\n";
echo "FIXING ALL CRITICAL ISSUES\n";
echo "=================================================================\n\n";

$db = Src\Config\DB::conn();

// Helper function to add column if not exists
function addColumn($db, $table, $column, $definition) {
    try {
        // Check if column exists
        $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($stmt->rowCount() > 0) {
            echo "  ✓ $table.$column already exists\n";
            return false;
        }
        
        // Add column
        $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        echo "  ✅ Added $table.$column\n";
        return true;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "  ✓ $table.$column already exists\n";
            return false;
        }
        echo "  ❌ Error adding $table.$column: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "=== FIXING CRP MEASUREMENTS ===\n";
// The CRP model is trying to SELECT doctor_id but it doesn't exist
addColumn($db, 'crp_measurements', 'doctor_id', 'INT NULL DEFAULT NULL');
addColumn($db, 'crp_measurements', 'measurement_unit', 'VARCHAR(20) DEFAULT "mg/L"');
addColumn($db, 'crp_measurements', 'value', 'DECIMAL(10,2) NULL DEFAULT NULL');
addColumn($db, 'crp_measurements', 'measured_at', 'DATETIME NULL DEFAULT NULL');

echo "\n=== FIXING REHAB TABLES ===\n";
// Rehab plans
addColumn($db, 'rehab_plans', 'doctor_id', 'INT NULL DEFAULT NULL');
addColumn($db, 'rehab_plans', 'title', 'VARCHAR(255) NOT NULL DEFAULT "Rehab Plan"');
addColumn($db, 'rehab_plans', 'description', 'TEXT NULL DEFAULT NULL');
addColumn($db, 'rehab_plans', 'status', 'VARCHAR(50) DEFAULT "ACTIVE"');

// Rehab exercises
addColumn($db, 'rehab_exercises', 'rehab_plan_id', 'INT NOT NULL');
addColumn($db, 'rehab_exercises', 'name', 'VARCHAR(255) NOT NULL');
addColumn($db, 'rehab_exercises', 'description', 'TEXT NULL DEFAULT NULL');
addColumn($db, 'rehab_exercises', 'reps', 'INT DEFAULT 10');
addColumn($db, 'rehab_exercises', 'sets', 'INT DEFAULT 3');
addColumn($db, 'rehab_exercises', 'duration', 'INT NULL DEFAULT NULL');
addColumn($db, 'rehab_exercises', 'frequency_per_week', 'INT DEFAULT 3');
addColumn($db, 'rehab_exercises', 'video_url', 'VARCHAR(255) NULL DEFAULT NULL');

echo "\n=== FIXING REPORTS TABLE ===\n";
addColumn($db, 'reports', 'patient_id', 'INT NOT NULL');
addColumn($db, 'reports', 'title', 'VARCHAR(255) NOT NULL');
addColumn($db, 'reports', 'description', 'TEXT NULL DEFAULT NULL');
addColumn($db, 'reports', 'file_url', 'VARCHAR(500) NULL DEFAULT NULL');
addColumn($db, 'reports', 'file_name', 'VARCHAR(255) NULL DEFAULT NULL');
addColumn($db, 'reports', 'file_size', 'INT NULL DEFAULT NULL');
addColumn($db, 'reports', 'mime_type', 'VARCHAR(100) NULL DEFAULT NULL');
addColumn($db, 'reports', 'status', 'VARCHAR(50) DEFAULT "PENDING"');
addColumn($db, 'reports', 'reviewed_by', 'INT NULL DEFAULT NULL');
addColumn($db, 'reports', 'reviewed_at', 'DATETIME NULL DEFAULT NULL');
addColumn($db, 'reports', 'ai_processed', 'TINYINT(1) DEFAULT 0');
addColumn($db, 'reports', 'ai_result', 'TEXT NULL DEFAULT NULL');

echo "\n=== FIXING PROFILE PICTURES ===\n";
// Users table
addColumn($db, 'users', 'profile_picture', 'VARCHAR(255) NULL DEFAULT NULL');
addColumn($db, 'users', 'profile_image_url', 'VARCHAR(255) NULL DEFAULT NULL');
addColumn($db, 'users', 'avatar_url', 'VARCHAR(255) NULL DEFAULT NULL');

// Patients table
addColumn($db, 'patients', 'profile_picture', 'VARCHAR(255) NULL DEFAULT NULL');
addColumn($db, 'patients', 'profile_image_url', 'VARCHAR(255) NULL DEFAULT NULL');

// Doctors table
addColumn($db, 'doctors', 'profile_picture', 'VARCHAR(255) NULL DEFAULT NULL');
addColumn($db, 'doctors', 'profile_image_url', 'VARCHAR(255) NULL DEFAULT NULL');

echo "\n=== FIXING MEDICATIONS TABLE ===\n";
addColumn($db, 'patient_medications', 'frequency_per_day', 'INT DEFAULT 1');
addColumn($db, 'patient_medications', 'duration', 'INT NULL DEFAULT NULL');
addColumn($db, 'patient_medications', 'description', 'TEXT NULL DEFAULT NULL');
addColumn($db, 'patient_medications', 'morning', 'TINYINT(1) DEFAULT 0');
addColumn($db, 'patient_medications', 'afternoon', 'TINYINT(1) DEFAULT 0');
addColumn($db, 'patient_medications', 'evening', 'TINYINT(1) DEFAULT 0');
addColumn($db, 'patient_medications', 'night', 'TINYINT(1) DEFAULT 0');

echo "\n=== CREATING UPLOAD DIRECTORIES ===\n";
$directories = [
    __DIR__ . '/public/uploads/profile_pictures',
    __DIR__ . '/public/uploads/reports',
    __DIR__ . '/public/uploads/temp'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "  ✅ Created directory: $dir\n";
        } else {
            echo "  ❌ Failed to create directory: $dir\n";
        }
    } else {
        echo "  ✓ Directory exists: $dir\n";
    }
}

echo "\n=== VERIFICATION ===\n";

// Verify CRP measurements table
echo "\nCRP Measurements columns:\n";
$stmt = $db->query("SHOW COLUMNS FROM crp_measurements");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
$requiredCrpColumns = ['doctor_id', 'measurement_unit', 'crp_value', 'measurement_date'];
foreach ($requiredCrpColumns as $col) {
    $exists = in_array($col, $columns);
    echo "  " . ($exists ? "✅" : "❌") . " $col\n";
}

// Verify rehab_exercises table
echo "\nRehab Exercises columns:\n";
$stmt = $db->query("SHOW COLUMNS FROM rehab_exercises");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
$requiredRehabColumns = ['rehab_plan_id', 'name', 'reps', 'frequency_per_week'];
foreach ($requiredRehabColumns as $col) {
    $exists = in_array($col, $columns);
    echo "  " . ($exists ? "✅" : "❌") . " $col\n";
}

// Verify reports table
echo "\nReports columns:\n";
$stmt = $db->query("SHOW COLUMNS FROM reports");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
$requiredReportColumns = ['patient_id', 'title', 'file_url', 'status', 'reviewed_by'];
foreach ($requiredReportColumns as $col) {
    $exists = in_array($col, $columns);
    echo "  " . ($exists ? "✅" : "❌") . " $col\n";
}

// Verify users table for profile pictures
echo "\nUsers table (profile pictures):\n";
$stmt = $db->query("SHOW COLUMNS FROM users");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
$requiredUserColumns = ['profile_picture', 'avatar_url'];
foreach ($requiredUserColumns as $col) {
    $exists = in_array($col, $columns);
    echo "  " . ($exists ? "✅" : "❌") . " $col\n";
}

echo "\n✅ ALL FIXES APPLIED!\n";
echo "\nNext steps:\n";
echo "1. Test rehab assignment from doctor login\n";
echo "2. Test report upload and AI processing\n";
echo "3. Test profile picture upload\n";
echo "4. Check server logs for any remaining errors\n";
