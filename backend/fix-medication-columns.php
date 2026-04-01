<?php
require_once __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();

echo "Fixing patient_medications table...\n\n";

function columnExists($db, $table, $column) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->fetch() !== false;
}

$columns = [
    'frequency_per_day' => 'INT NULL DEFAULT NULL',
    'duration' => 'VARCHAR(100) NULL DEFAULT NULL',
    'description' => 'TEXT NULL DEFAULT NULL'
];

foreach ($columns as $column => $definition) {
    if (!columnExists($db, 'patient_medications', $column)) {
        try {
            $db->exec("ALTER TABLE patient_medications ADD COLUMN $column $definition");
            echo "✓ Added patient_medications.$column\n";
        } catch (Exception $e) {
            echo "✗ Failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  patient_medications.$column already exists\n";
    }
}

echo "\n✓ Medication table fixed!\n";
