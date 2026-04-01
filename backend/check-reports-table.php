<?php
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/config/db.php';
use Src\Config\DB;

$db = DB::conn();

echo "===========================================\n";
echo "Reports Table Structure\n";
echo "===========================================\n\n";

$stmt = $db->query("DESCRIBE reports");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Columns:\n";
foreach ($columns as $col) {
    echo "- {$col['Field']} ({$col['Type']})\n";
}

echo "\n";
echo "Sample report:\n";
$stmt = $db->query("SELECT * FROM reports LIMIT 1");
$report = $stmt->fetch(PDO::FETCH_ASSOC);
if ($report) {
    foreach ($report as $key => $value) {
        echo "$key: " . (is_string($value) ? substr($value, 0, 50) : $value) . "\n";
    }
}
