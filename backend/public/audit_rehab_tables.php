<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Src\Config\DB;

$db = DB::conn();
$tables = ['rehab_plans', 'rehab_exercises', 'exercise_schedule', 'exercise_assignments'];

foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    try {
        $result = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        print_r($result);
    } catch (Exception $e) {
        echo "Error or table missing: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
