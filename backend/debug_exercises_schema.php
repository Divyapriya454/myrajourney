<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$stmt = $db->query("DESCRIBE rehab_exercises");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
?>
