<?php
$pdo = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
echo "Doctors Table:\n";
$stmt = $pdo->query('DESCRIBE doctors');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ") " . ($row['Key'] == 'PRI' ? '[PRIMARY KEY]' : '') . "\n";
}
