<?php
require __DIR__ . '/src/bootstrap.php';
$db = Src\Config\DB::conn();

echo "Appointments table structure:" . PHP_EOL;
$stmt = $db->query('DESCRIBE appointments');
while($row = $stmt->fetch()) {
    echo "  " . $row['Field'] . " (" . $row['Type'] . ")" . PHP_EOL;
}
