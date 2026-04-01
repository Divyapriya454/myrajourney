<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=myrajourney', 'root', '');
echo "=== PATIENTS TABLE ===\n";
$stmt = $db->query('DESCRIBE patients');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . "\n";
}
