<?php
$pdo = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
echo "Appointments Table Columns:\n";
$stmt = $pdo->query('DESCRIBE appointments');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
