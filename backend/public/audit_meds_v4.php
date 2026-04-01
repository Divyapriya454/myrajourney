<?php
require __DIR__ . '/../src/bootstrap.php';
use Src\Config\DB;
try {
    $db = DB::conn();
    echo "--- LATEST MEDS AUDIT ---\n";
    $stmt = $db->prepare("SELECT id, medication_name, doctor_id, instructions, duration, is_morning, is_afternoon, is_night, food_relation, frequency_per_day, created_at FROM patient_medications ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        print_r($row);
        echo "------------------\n";
    }
} catch (Exception $e) { echo $e->getMessage(); }
