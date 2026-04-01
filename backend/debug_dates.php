<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$stmt = $db->query('SELECT id, start_date, created_at FROM rehab_plans ORDER BY id DESC LIMIT 5');
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT);
?>
