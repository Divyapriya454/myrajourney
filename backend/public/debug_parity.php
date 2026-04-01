<?php
require 'c:/Users/Admin/AndroidStudioProjects/myrajourney/myrajourney/backend/src/bootstrap.php';
$db = Src\Config\DB::conn();
echo "--- LATEST 5 MEDS ---\n";
$stmt = $db->query('SELECT id, medication_name, instructions, food_relation, is_morning, is_afternoon, is_night FROM patient_medications ORDER BY id DESC LIMIT 5');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
    echo "------------------\n";
}

echo "\n--- ASSIGNMENT LOG ---\n";
$log = 'c:/Users/Admin/AndroidStudioProjects/myrajourney/myrajourney/backend/public/assign_req.log';
if (file_exists($log)) {
    $lines = file($log);
    echo implode("", array_slice($lines, -5));
} else {
    echo "Log not found at $log\n";
}
