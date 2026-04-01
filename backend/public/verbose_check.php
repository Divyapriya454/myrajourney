<?php
require 'c:/Users/Admin/AndroidStudioProjects/myrajourney/myrajourney/backend/src/bootstrap.php';
$db = Src\Config\DB::conn();
echo "--- ALL RECENT MEDS ---\n";
$stmt = $db->query('SELECT id, medication_name, instructions, food_relation, is_morning, is_afternoon, is_night, created_at FROM patient_medications ORDER BY id DESC LIMIT 10');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | Name: " . $row['medication_name'] . " | Created: " . $row['created_at'] . "\n";
    echo "DESC: [" . $row['instructions'] . "]\n";
    echo "FOOD: [" . $row['food_relation'] . "]\n";
    echo "TIME: M=" . $row['is_morning'] . ", A=" . $row['is_afternoon'] . ", N=" . $row['is_night'] . "\n";
    echo "----------------------------------\n";
}
