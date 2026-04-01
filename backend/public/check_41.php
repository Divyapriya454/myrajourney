<?php
require 'c:/Users/Admin/AndroidStudioProjects/myrajourney/myrajourney/backend/src/bootstrap.php';
$db = Src\Config\DB::conn();
$stmt = $db->query('SELECT id, instructions, food_relation, is_morning, is_afternoon, is_night FROM patient_medications WHERE id = 41');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "ID 41 Database Values:\n";
foreach ($row as $k => $v) {
    echo "$k: [" . ($v === null ? "NULL" : $v) . "]\n";
}
