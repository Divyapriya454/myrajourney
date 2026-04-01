<?php
/**
 * Fix Doctor Functions - Missing Columns
 */

require_once __DIR__ . '/src/bootstrap.php';

echo "=================================================================\n";
echo "FIXING DOCTOR FUNCTIONS\n";
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

// Fix rehab_exercises table
echo "Rehab_exercises table:\n";
addColumn($db, 'rehab_exercises', 'rehab_plan_id', 'INT NULL DEFAULT NULL');
addColumn($db, 'rehab_exercises', 'plan_id', 'INT NULL DEFAULT NULL'); // Alternative name

// Fix patient_medications table for doctor assignment
echo "\nPatient_medications table:\n";
addColumn($db, 'patient_medications', 'prescribed_by', 'INT NULL DEFAULT NULL');

// Fix CRP measurements table
echo "\nCRP_measurements table:\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'crp_measurements'");
    if ($stmt->fetch()) {
        addColumn($db, 'crp_measurements', 'measurement_unit', 'VARCHAR(50) DEFAULT \'mg/L\'');
        addColumn($db, 'crp_measurements', 'value', 'DECIMAL(10,2) NULL DEFAULT NULL');
        addColumn($db, 'crp_measurements', 'measured_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    } else {
        echo "  crp_measurements table doesn't exist (OK)\n";
    }
} catch (Exception $e) {
    echo "  Error checking crp_measurements: " . $e->getMessage() . "\n";
}

// Fix report_notes table
echo "\nReport_notes table:\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'report_notes'");
    if ($stmt->fetch()) {
        addColumn($db, 'report_notes', 'report_id', 'INT NULL DEFAULT NULL');
        addColumn($db, 'report_notes', 'doctor_id', 'INT NULL DEFAULT NULL');
        addColumn($db, 'report_notes', 'note', 'TEXT NULL DEFAULT NULL');
        addColumn($db, 'report_notes', 'created_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    }
} catch (Exception $e) {
    echo "  Error checking report_notes: " . $e->getMessage() . "\n";
}

echo "\n✓ Doctor functions fixed!\n";
echo "=================================================================\n";
