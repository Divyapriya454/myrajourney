<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$stmt = $db->query('DESCRIBE doctors');
echo "doctors table columns:\n";
echo str_repeat('=', 60) . "\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' (' . $row['Type'] . ')' . PHP_EOL;
}
