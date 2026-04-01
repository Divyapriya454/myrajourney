<?php
$pdo = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
echo "Reports Table Columns:\n";
echo "-------------------------------------------\n";
$stmt = $pdo->query('DESCRIBE reports');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
