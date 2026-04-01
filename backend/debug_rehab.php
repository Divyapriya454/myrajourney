<?php
require __DIR__ . '/src/bootstrap.php';
use Src\Config\DB;

$db = DB::conn();

function describeTable($db, $table) {
    echo "\nTable: $table\n";
    echo str_repeat('-', 40) . "\n";
    try {
        $stmt = $db->query("DESCRIBE $table");
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['Field']} - {$row['Type']} - Null: {$row['Null']} - Key: {$row['Key']}\n";
        }
    } catch (Exception $e) {
        echo "Error describing $table: " . $e->getMessage() . "\n";
    }
}

describeTable($db, 'rehab_plans');
describeTable($db, 'rehab_exercises');
describeTable($db, 'exercise_schedule');

echo "\nRecent Rehab Plans:\n";
echo str_repeat('-', 40) . "\n";
$stmt = $db->query("SELECT id, patient_id, doctor_id, title, created_at FROM rehab_plans ORDER BY created_at DESC LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\nRecent Rehab Exercises:\n";
echo str_repeat('-', 40) . "\n";
$stmt = $db->query("SELECT id, plan_id, rehab_plan_id, exercise_name, created_at FROM rehab_exercises ORDER BY created_at DESC LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\nRecent Exercise Schedule:\n";
echo str_repeat('-', 40) . "\n";
$stmt = $db->query("SELECT id, patient_id, schedule_date, is_completed FROM exercise_schedule ORDER BY id DESC LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
