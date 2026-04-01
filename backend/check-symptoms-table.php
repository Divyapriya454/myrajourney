<?php
$pdo = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
echo "Symptoms Table Columns:\n";
$stmt = $pdo->query('DESCRIBE symptoms');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
