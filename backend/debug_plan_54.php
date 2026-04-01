<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$stmt = $db->query('SELECT id, start_date, created_at FROM rehab_plans WHERE id = 54');
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
?>
