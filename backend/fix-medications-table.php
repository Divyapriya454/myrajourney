<?php
require_once __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();

echo "Fixing patient_medications table...\n\n";

function columnExists($db, $table, $column) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->fetch() !== false;
}

// Add missing columns
$columns = [
    'medication_id' => 'INT NULL DEFAULT NULL',
    'doctor_id' => 'INT NULL DEFAULT NULL',
    'active' => 'TINYINT(1) DEFAULT 1',
    'is_morning' => 'TINYINT(1) DEFAULT 0',
    'is_afternoon' => 'TINYINT(1) DEFAULT 0',
    'is_night' => 'TINYINT(1) DEFAULT 0',
    'food_relation' => 'VARCHAR(50) NULL DEFAULT NULL'
];

foreach ($columns as $column => $definition) {
    if (!columnExists($db, 'patient_medications', $column)) {
        try {
            $db->exec("ALTER TABLE patient_medications ADD COLUMN `$column` $definition");
            echo "✓ Added patient_medications.$column\n";
        } catch (Exception $e) {
            echo "✗ Failed to add patient_medications.$column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  patient_medications.$column already exists\n";
    }
}

echo "\n✓ Patient_medications table fixed!\n";
