<?php
require_once __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();

echo "Patients table columns:\n";
echo str_repeat('=', 60) . "\n";

$stmt = $db->query('DESCRIBE patients');
while($row = $stmt->fetch()) {
    echo $row['Field'] . ' (' . $row['Type'] . ')' . PHP_EOL;
}
