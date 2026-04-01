<?php
require __DIR__ . '/../src/bootstrap.php';
use Src\Config\DB;
try {
    $db = DB::conn();
    echo "--- MEDS FOR PATIENT 25 ---\n";
    $stmt = $db->prepare("SELECT id, medication_name, doctor_id, instructions, duration, is_morning, is_afternoon, is_night FROM patient_medications WHERE patient_id = 25 AND active = 1 ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        print_r($row);
        echo "------------------\n";
    }
} catch (Exception $e) { echo $e->getMessage(); }
