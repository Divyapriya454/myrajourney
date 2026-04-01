<?php
require_once __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();

echo "Fixing symptoms table...\n\n";

function columnExists($db, $table, $column) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->fetch() !== false;
}

// Add missing columns
$columns = [
    'date' => 'DATE NULL DEFAULT NULL',
    'pain_level' => 'INT NULL DEFAULT NULL',
    'stiffness_level' => 'INT NULL DEFAULT NULL',
    'fatigue_level' => 'INT NULL DEFAULT NULL',
    'joint_count' => 'INT NULL DEFAULT NULL',
    'notes' => 'TEXT NULL DEFAULT NULL'
];

foreach ($columns as $column => $definition) {
    if (!columnExists($db, 'symptoms', $column)) {
        try {
            $db->exec("ALTER TABLE symptoms ADD COLUMN `$column` $definition");
            echo "✓ Added symptoms.$column\n";
        } catch (Exception $e) {
            echo "✗ Failed to add symptoms.$column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  symptoms.$column already exists\n";
    }
}

echo "\n✓ Symptoms table fixed!\n";
