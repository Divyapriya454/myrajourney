<?php
require_once __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();

$tables = ['symptoms', 'patient_medications', 'medications'];

foreach ($tables as $table) {
    echo "\n$table table structure:\n";
    echo str_repeat('=', 60) . "\n";
    
    try {
        $stmt = $db->query("DESCRIBE $table");
        while($row = $stmt->fetch()) {
            echo $row['Field'] . ' (' . $row['Type'] . ')' . PHP_EOL;
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
