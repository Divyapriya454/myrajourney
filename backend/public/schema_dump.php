<?php
require 'c:/Users/Admin/AndroidStudioProjects/myrajourney/myrajourney/backend/src/bootstrap.php';
$db = Src\Config\DB::conn();
echo "--- TABLE CREATE STATEMENT ---\n";
$stmt = $db->query('SHOW CREATE TABLE patient_medications');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo $row['Create Table'] . "\n\n";

echo "--- COLUMNS --- \n";
$stmt = $db->query('SHOW COLUMNS FROM patient_medications');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
