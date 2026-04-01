<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');

echo "rehab_plans columns:\n";
echo str_repeat('=', 60) . "\n";
$stmt = $db->query('DESCRIBE rehab_plans');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' (' . $row['Type'] . ')' . PHP_EOL;
}

echo "\nrehab_exercises columns:\n";
echo str_repeat('=', 60) . "\n";
$stmt = $db->query('DESCRIBE rehab_exercises');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' (' . $row['Type'] . ')' . PHP_EOL;
}
