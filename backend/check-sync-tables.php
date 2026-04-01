<?php
require __DIR__ . '/src/bootstrap.php';

$tables = ['patient_medications', 'rehab_plans', 'rehab_exercises'];

foreach ($tables as $table) {
    echo "=== Structure of $table ===" . PHP_EOL;
    try {
        $db = Src\Config\DB::conn();
        $stmt = $db->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  " . $row['Field'] . " (" . $row['Type'] . ")" . PHP_EOL;
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;
}
