<?php
/**
 * FIX REMAINING MISSING COLUMNS
 * Based on test failures:
 * 1. rehab_plans.start_date
 * 2. patient_medications.status
 * 3. crp_measurements.updated_at
 */

require_once __DIR__ . '/src/bootstrap.php';

echo "=================================================================\n";
echo "FIXING REMAINING MISSING COLUMNS\n";
echo "=================================================================\n\n";

$db = Src\Config\DB::conn();

function addColumn($db, $table, $column, $definition) {
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        if ($stmt->fetch()) {
            echo "  ✓ $table.$column already exists\n";
            return false;
        }
        
        $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        echo "  ✅ Added $table.$column\n";
        return true;
    } catch (Exception $e) {
        echo "  ❌ Error adding $table.$column: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "Fixing rehab_plans table:\n";
addColumn($db, 'rehab_plans', 'start_date', 'DATE NULL DEFAULT NULL');
addColumn($db, 'rehab_plans', 'end_date', 'DATE NULL DEFAULT NULL');

echo "\nFixing patient_medications table:\n";
addColumn($db, 'patient_medications', 'status', 'VARCHAR(50) DEFAULT "ACTIVE"');

echo "\nFixing crp_measurements table:\n";
addColumn($db, 'crp_measurements', 'updated_at', 'TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

echo "\n✅ ALL MISSING COLUMNS FIXED!\n";
