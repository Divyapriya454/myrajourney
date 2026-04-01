<?php
/**
 * Fix All Missing Database Columns
 */

require_once __DIR__ . '/src/bootstrap.php';

echo "=================================================================\n";
echo "FIXING ALL MISSING DATABASE COLUMNS\n";
echo "=================================================================\n\n";

$db = Src\Config\DB::conn();
$fixed = 0;
$errors = 0;

// Function to check if column exists
function columnExists($db, $table, $column) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->fetch() !== false;
}

// Function to add column safely
function addColumn($db, $table, $column, $definition, &$fixed, &$errors) {
    if (!columnExists($db, $table, $column)) {
        try {
            $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
            $db->exec($sql);
            echo "✓ Added $table.$column\n";
            $fixed++;
            return true;
        } catch (Exception $e) {
            echo "✗ Failed to add $table.$column: " . $e->getMessage() . "\n";
            $errors++;
            return false;
        }
    }
    return false;
}

echo "Checking and fixing missing columns...\n\n";

// 1. Users table
echo "Users table:\n";
addColumn($db, 'users', 'last_login_at', 'TIMESTAMP NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'users', 'avatar_url', 'VARCHAR(255) NULL DEFAULT NULL', $fixed, $errors);

// 2. Appointments table
echo "\nAppointments table:\n";
addColumn($db, 'appointments', 'title', 'VARCHAR(255) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'appointments', 'description', 'TEXT NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'appointments', 'location', 'VARCHAR(255) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'appointments', 'appointment_type', "ENUM('CONSULTATION', 'FOLLOW_UP', 'EMERGENCY', 'ROUTINE') DEFAULT 'CONSULTATION'", $fixed, $errors);

// 3. Reports table
echo "\nReports table:\n";
addColumn($db, 'reports', 'uploaded_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP', $fixed, $errors);
addColumn($db, 'reports', 'report_type', 'VARCHAR(100) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'reports', 'report_date', 'DATE NULL DEFAULT NULL', $fixed, $errors);

// 4. Patient_medications table
echo "\nPatient_medications table:\n";
addColumn($db, 'patient_medications', 'medication_name', 'VARCHAR(255) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'patient_medications', 'dosage', 'VARCHAR(100) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'patient_medications', 'frequency', 'VARCHAR(100) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'patient_medications', 'start_date', 'DATE NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'patient_medications', 'end_date', 'DATE NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'patient_medications', 'instructions', 'TEXT NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'patient_medications', 'is_active', 'TINYINT(1) DEFAULT 1', $fixed, $errors);

// 5. Rehab_plans table
echo "\nRehab_plans table:\n";
addColumn($db, 'rehab_plans', 'title', 'VARCHAR(255) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'rehab_plans', 'description', 'TEXT NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'rehab_plans', 'start_date', 'DATE NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'rehab_plans', 'end_date', 'DATE NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'rehab_plans', 'status', "ENUM('ACTIVE', 'COMPLETED', 'PAUSED') DEFAULT 'ACTIVE'", $fixed, $errors);

// 6. Rehab_exercises table
echo "\nRehab_exercises table:\n";
addColumn($db, 'rehab_exercises', 'exercise_name', 'VARCHAR(255) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'rehab_exercises', 'description', 'TEXT NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'rehab_exercises', 'repetitions', 'INT NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'rehab_exercises', 'sets', 'INT NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'rehab_exercises', 'duration_minutes', 'INT NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'rehab_exercises', 'video_url', 'VARCHAR(255) NULL DEFAULT NULL', $fixed, $errors);

// 7. Notifications table
echo "\nNotifications table:\n";
addColumn($db, 'notifications', 'title', 'VARCHAR(255) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'notifications', 'message', 'TEXT NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'notifications', 'type', "VARCHAR(50) DEFAULT 'INFO'", $fixed, $errors);
addColumn($db, 'notifications', 'is_read', 'TINYINT(1) DEFAULT 0', $fixed, $errors);
addColumn($db, 'notifications', 'read_at', 'TIMESTAMP NULL DEFAULT NULL', $fixed, $errors);

// 8. Symptoms table
echo "\nSymptoms table:\n";
addColumn($db, 'symptoms', 'symptom_type', 'VARCHAR(100) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'symptoms', 'severity', "ENUM('MILD', 'MODERATE', 'SEVERE') DEFAULT 'MILD'", $fixed, $errors);
addColumn($db, 'symptoms', 'description', 'TEXT NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'symptoms', 'recorded_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP', $fixed, $errors);

// 9. Doctors table
echo "\nDoctors table:\n";
addColumn($db, 'doctors', 'specialization', 'VARCHAR(255) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'doctors', 'license_number', 'VARCHAR(100) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'doctors', 'department', 'VARCHAR(255) NULL DEFAULT NULL', $fixed, $errors);

// 10. Patients table
echo "\nPatients table:\n";
addColumn($db, 'patients', 'medical_record_number', 'VARCHAR(100) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'patients', 'blood_group', 'VARCHAR(10) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'patients', 'emergency_contact', 'VARCHAR(255) NULL DEFAULT NULL', $fixed, $errors);
addColumn($db, 'patients', 'emergency_phone', 'VARCHAR(20) NULL DEFAULT NULL', $fixed, $errors);

echo "\n=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n";
echo "Columns added: $fixed\n";
echo "Errors: $errors\n";

if ($errors === 0) {
    echo "\n✓ All missing columns have been added successfully!\n";
} else {
    echo "\n⚠ Some columns could not be added. Check errors above.\n";
}

echo "=================================================================\n";
