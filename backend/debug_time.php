<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
echo json_encode([
    'php_time' => date('Y-m-d H:i:s'),
    'db_time' => $db->query('SELECT NOW()')->fetchColumn()
], JSON_PRETTY_PRINT);
?>
