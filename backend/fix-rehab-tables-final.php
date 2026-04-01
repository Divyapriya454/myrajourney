<?php
require_once __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();

echo "Fixing rehab tables for doctor assignment...\n\n";

function columnExists($db, $table, $column) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->fetch() !== false;
}

// Fix rehab_plans table
echo "Rehab_plans table:\n";
$columns = [
    'doctor_id' => 'INT NULL DEFAULT NULL',
];

foreach ($columns as $column => $definition) {
    if (!columnExists($db, 'rehab_plans', $column)) {
        try {
            $db->exec("ALTER TABLE rehab_plans ADD COLUMN $column $definition");
            echo "✓ Added rehab_plans.$column\n";
        } catch (Exception $e) {
            echo "✗ Failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  rehab_plans.$column already exists\n";
    }
}

// Fix rehab_exercises table
echo "\nRehab_exercises table:\n";
$columns = [
    'name' => 'VARCHAR(255) NULL DEFAULT NULL',
    'reps' => 'VARCHAR(50) NULL DEFAULT NULL',
    'sets' => 'INT NULL DEFAULT NULL',
    'frequency_per_week' => 'VARCHAR(50) NULL DEFAULT NULL',
];

foreach ($columns as $column => $definition) {
    if (!columnExists($db, 'rehab_exercises', $column)) {
        try {
            $db->exec("ALTER TABLE rehab_exercises ADD COLUMN $column $definition");
            echo "✓ Added rehab_exercises.$column\n";
        } catch (Exception $e) {
            echo "✗ Failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  rehab_exercises.$column already exists\n";
    }
}

echo "\n✓ Rehab tables fixed!\n";
