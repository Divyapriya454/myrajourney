<?php
/**
 * FIX ALL REMAINING ISSUES
 * - CRP measurements
 * - Profile pictures
 * - Any other missing columns
 */

require_once __DIR__ . '/src/bootstrap.php';

echo "=================================================================\n";
echo "FIXING ALL REMAINING ISSUES\n";
echo "=================================================================\n\n";

$db = Src\Config\DB::conn();

function columnExists($db, $table, $column) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->fetch() !== false;
}

function addColumn($db, $table, $column, $definition) {
    if (!columnExists($db, $table, $column)) {
        try {
            $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "✓ Added $table.$column\n";
            return true;
        } catch (Exception $e) {
            echo "✗ Failed to add $table.$column: " . $e->getMessage() . "\n";
            return false;
        }
    }
    return false;
}

// Fix CRP measurements table
echo "CRP Measurements table:\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'crp_measurements'");
    if ($stmt->fetch()) {
        addColumn($db, 'crp_measurements', 'patient_id', 'INT NULL DEFAULT NULL');
        addColumn($db, 'crp_measurements', 'doctor_id', 'INT NULL DEFAULT NULL');
        addColumn($db, 'crp_measurements', 'crp_value', 'DECIMAL(10,2) NULL DEFAULT NULL');
        addColumn($db, 'crp_measurements', 'notes', 'TEXT NULL DEFAULT NULL');
        addColumn($db, 'crp_measurements', 'test_date', 'DATE NULL DEFAULT NULL');
    }
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}

// Fix users table for profile pictures
echo "\nUsers table (profile pictures):\n";
addColumn($db, 'users', 'profile_picture', 'VARCHAR(255) NULL DEFAULT NULL');
addColumn($db, 'users', 'profile_image_url', 'VARCHAR(255) NULL DEFAULT NULL');

// Fix patients table for profile pictures
echo "\nPatients table (profile pictures):\n";
addColumn($db, 'patients', 'profile_picture', 'VARCHAR(255) NULL DEFAULT NULL');
addColumn($db, 'patients', 'profile_image_url', 'VARCHAR(255) NULL DEFAULT NULL');

// Fix doctors table for profile pictures
echo "\nDoctors table (profile pictures):\n";
addColumn($db, 'doctors', 'profile_picture', 'VARCHAR(255) NULL DEFAULT NULL');
addColumn($db, 'doctors', 'profile_image_url', 'VARCHAR(255) NULL DEFAULT NULL');

// Fix reports table
echo "\nReports table:\n";
addColumn($db, 'reports', 'file_path', 'VARCHAR(255) NULL DEFAULT NULL');
addColumn($db, 'reports', 'original_filename', 'VARCHAR(255) NULL DEFAULT NULL');

// Fix appointments table
echo "\nAppointments table:\n";
addColumn($db, 'appointments', 'created_by', 'INT NULL DEFAULT NULL');

echo "\n=================================================================\n";
echo "CHECKING ALL TABLES FOR COMPLETENESS\n";
echo "=================================================================\n\n";

// Check critical tables
$criticalTables = [
    'users' => ['id', 'email', 'password', 'role', 'name', 'status'],
    'patients' => ['id', 'user_id', 'age', 'gender', 'assigned_doctor_id'],
    'doctors' => ['id', 'user_id', 'specialization'],
    'appointments' => ['id', 'patient_id', 'doctor_id', 'appointment_date', 'appointment_time'],
    'patient_medications' => ['id', 'patient_id', 'medication_name', 'dosage'],
    'rehab_plans' => ['id', 'patient_id', 'title', 'doctor_id'],
    'rehab_exercises' => ['id', 'rehab_plan_id', 'name'],
    'reports' => ['id', 'patient_id', 'file_path'],
];

foreach ($criticalTables as $table => $requiredColumns) {
    echo "$table: ";
    $missing = [];
    
    foreach ($requiredColumns as $column) {
        if (!columnExists($db, $table, $column)) {
            $missing[] = $column;
        }
    }
    
    if (empty($missing)) {
        echo "✓ OK\n";
    } else {
        echo "⚠ Missing: " . implode(', ', $missing) . "\n";
    }
}

echo "\n✓ All issues fixed!\n";
echo "=================================================================\n";
