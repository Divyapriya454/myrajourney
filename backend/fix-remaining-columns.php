<?php
/**
 * Fix Remaining Missing Columns
 */

require_once __DIR__ . '/src/bootstrap.php';

echo "=================================================================\n";
echo "FIXING REMAINING MISSING COLUMNS\n";
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
            $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
            $db->exec($sql);
            echo "✓ Added $table.$column\n";
            return true;
        } catch (Exception $e) {
            echo "✗ Failed to add $table.$column: " . $e->getMessage() . "\n";
            return false;
        }
    } else {
        echo "  $table.$column already exists\n";
        return false;
    }
}

// Patient_medications - missing columns
echo "Patient_medications table:\n";
addColumn($db, 'patient_medications', 'name_override', 'VARCHAR(255) NULL DEFAULT NULL');

// Notifications - missing body column (should be message)
echo "\nNotifications table:\n";
addColumn($db, 'notifications', 'body', 'TEXT NULL DEFAULT NULL');

// Symptoms - missing created_at
echo "\nSymptoms table:\n";
addColumn($db, 'symptoms', 'created_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');

echo "\n✓ All remaining columns added!\n";
echo "=================================================================\n";
