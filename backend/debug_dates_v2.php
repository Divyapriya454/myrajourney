<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$stmt = $db->query('SELECT id, start_date, created_at FROM rehab_plans ORDER BY id DESC LIMIT 10');
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
?>
