<?php
$pdo = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$stmt = $pdo->query('DESCRIBE patients');
echo "Patients Table Structure:\n";
echo "-------------------------------------------\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
