<?php
require 'c:/Users/Admin/AndroidStudioProjects/myrajourney/myrajourney/backend/src/bootstrap.php';
$db = Src\Config\DB::conn();
echo "--- LATEST ROW FULL DETAIL ---\n";
$stmt = $db->query('SELECT * FROM patient_medications ORDER BY id DESC LIMIT 1');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
foreach ($row as $k => $v) {
    echo "$k: [" . ($v === null ? "NULL" : $v) . "]\n";
}
