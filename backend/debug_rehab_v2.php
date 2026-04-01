<?php
require __DIR__ . '/src/bootstrap.php';
use Src\Config\DB;

$db = DB::conn();

function printTable($db, $table) {
    echo "\n--- $table ---\n";
    try {
        $stmt = $db->query("SELECT * FROM $table ORDER BY id DESC LIMIT 10");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            echo "No data found in $table.\n";
            return;
        }
        foreach ($rows as $row) {
            foreach ($row as $key => $val) {
                echo "[$key]: $val | ";
            }
            echo "\n";
        }
    } catch (Exception $e) {
        echo "Error reading $table: " . $e->getMessage() . "\n";
    }
}

function checkSchema($db, $table) {
    echo "\n--- Schema: $table ---\n";
    try {
        $stmt = $db->query("DESCRIBE $table");
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['Field']} ({$row['Type']}) | ";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "Error describing $table: " . $e->getMessage() . "\n";
    }
}

checkSchema($db, 'rehab_plans');
checkSchema($db, 'rehab_exercises');
checkSchema($db, 'exercise_assignments');
checkSchema($db, 'exercise_schedule');

printTable($db, 'rehab_plans');
printTable($db, 'rehab_exercises');
printTable($db, 'exercise_assignments');

// Check for a specific patient if we can find one
$stmt = $db->query("SELECT id, name FROM users WHERE role = 'PATIENT' LIMIT 3");
while($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "\n--- Checking Data for Patient {$p['name']} (ID: {$p['id']}) ---\n";
    $stmt2 = $db->prepare("SELECT COUNT(*) FROM rehab_plans WHERE patient_id = ?");
    $stmt2->execute([$p['id']]);
    echo "rehab_plans count: " . $stmt2->fetchColumn() . "\n";
    
    $stmt2 = $db->prepare("SELECT COUNT(*) FROM exercise_assignments WHERE patient_id = ?");
    $stmt2->execute([$p['id']]);
    echo "exercise_assignments count: " . $stmt2->fetchColumn() . "\n";
}
