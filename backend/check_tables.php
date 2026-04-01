<?php
require_once __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();

echo "=== REHAB_PLANS TABLE ===\n";
$stmt = $db->query('DESCRIBE rehab_plans');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $null = $row['Null'] === 'NO' ? 'NOT NULL' : 'NULL';
    $default = $row['Default'] !== null ? "DEFAULT '{$row['Default']}'" : '';
    echo "{$row['Field']} | {$row['Type']} | $null $default\n";
}

echo "\n=== REHAB_EXERCISES TABLE ===\n";
$stmt = $db->query('DESCRIBE rehab_exercises');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $null = $row['Null'] === 'NO' ? 'NOT NULL' : 'NULL';
    $default = $row['Default'] !== null ? "DEFAULT '{$row['Default']}'" : '';
    echo "{$row['Field']} | {$row['Type']} | $null $default\n";
}

echo "\n=== PATIENT_MEDICATIONS TABLE ===\n";
$stmt = $db->query('DESCRIBE patient_medications');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $null = $row['Null'] === 'NO' ? 'NOT NULL' : 'NULL';
    $default = $row['Default'] !== null ? "DEFAULT '{$row['Default']}'" : '';
    echo "{$row['Field']} | {$row['Type']} | $null $default\n";
}
