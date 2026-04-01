<?php
/**
 * Fix ALL Critical Missing Columns
 */

require_once __DIR__ . '/src/bootstrap.php';

echo "=================================================================\n";
echo "FIXING ALL CRITICAL MISSING COLUMNS\n";
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

// Patients table - missing age and assigned_doctor_id
echo "Patients table:\n";
addColumn($db, 'patients', 'age', 'INT NULL DEFAULT NULL');
addColumn($db, 'patients', 'assigned_doctor_id', 'INT NULL DEFAULT NULL');
addColumn($db, 'patients', 'diagnosis', 'TEXT NULL DEFAULT NULL');
addColumn($db, 'patients', 'medical_history', 'TEXT NULL DEFAULT NULL');

// Doctors table - missing more columns
echo "\nDoctors table:\n";
addColumn($db, 'doctors', 'qualification', 'VARCHAR(255) NULL DEFAULT NULL');
addColumn($db, 'doctors', 'experience_years', 'INT NULL DEFAULT NULL');
addColumn($db, 'doctors', 'consultation_fee', 'DECIMAL(10,2) NULL DEFAULT NULL');
addColumn($db, 'doctors', 'available_days', 'VARCHAR(255) NULL DEFAULT NULL');
addColumn($db, 'doctors', 'available_time', 'VARCHAR(255) NULL DEFAULT NULL');

// Appointments table - more missing columns
echo "\nAppointments table:\n";
addColumn($db, 'appointments', 'notes', 'TEXT NULL DEFAULT NULL');
addColumn($db, 'appointments', 'status', "ENUM('SCHEDULED', 'COMPLETED', 'CANCELLED', 'RESCHEDULED') DEFAULT 'SCHEDULED'");
addColumn($db, 'appointments', 'reminder_sent', 'TINYINT(1) DEFAULT 0');

// Reports table - more columns
echo "\nReports table:\n";
addColumn($db, 'reports', 'file_name', 'VARCHAR(255) NULL DEFAULT NULL');
addColumn($db, 'reports', 'file_size', 'INT NULL DEFAULT NULL');
addColumn($db, 'reports', 'processed', 'TINYINT(1) DEFAULT 0');
addColumn($db, 'reports', 'processed_at', 'TIMESTAMP NULL DEFAULT NULL');

// Medications table
echo "\nMedications table:\n";
addColumn($db, 'medications', 'manufacturer', 'VARCHAR(255) NULL DEFAULT NULL');
addColumn($db, 'medications', 'price', 'DECIMAL(10,2) NULL DEFAULT NULL');

// Rehab_plans table
echo "\nRehab_plans table:\n";
addColumn($db, 'rehab_plans', 'created_by', 'INT NULL DEFAULT NULL');
addColumn($db, 'rehab_plans', 'notes', 'TEXT NULL DEFAULT NULL');
addColumn($db, 'rehab_plans', 'progress', 'INT DEFAULT 0');

// Rehab_exercises table
echo "\nRehab_exercises table:\n";
addColumn($db, 'rehab_exercises', 'completed', 'TINYINT(1) DEFAULT 0');
addColumn($db, 'rehab_exercises', 'completed_at', 'TIMESTAMP NULL DEFAULT NULL');
addColumn($db, 'rehab_exercises', 'notes', 'TEXT NULL DEFAULT NULL');

// Health_metrics table (if exists)
echo "\nHealth_metrics table:\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'health_metrics'");
    if ($stmt->fetch()) {
        addColumn($db, 'health_metrics', 'metric_type', 'VARCHAR(100) NULL DEFAULT NULL');
        addColumn($db, 'health_metrics', 'value', 'DECIMAL(10,2) NULL DEFAULT NULL');
        addColumn($db, 'health_metrics', 'unit', 'VARCHAR(50) NULL DEFAULT NULL');
        addColumn($db, 'health_metrics', 'recorded_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    }
} catch (Exception $e) {
    echo "  health_metrics table doesn't exist (OK)\n";
}

echo "\n=================================================================\n";
echo "CHECKING FOR ANY OTHER MISSING COLUMNS\n";
echo "=================================================================\n\n";

// Check all tables for common missing columns
$tables = ['users', 'patients', 'doctors', 'appointments', 'reports', 
           'patient_medications', 'rehab_plans', 'rehab_exercises', 
           'symptoms', 'notifications'];

foreach ($tables as $table) {
    // Ensure created_at and updated_at exist
    if (!columnExists($db, $table, 'created_at')) {
        addColumn($db, $table, 'created_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    }
    if (!columnExists($db, $table, 'updated_at')) {
        addColumn($db, $table, 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }
}

echo "\n✓ All critical columns have been added!\n";
echo "=================================================================\n";
