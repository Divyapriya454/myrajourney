<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=myrajourney', 'root', '');

echo "=== MEDICATIONS TABLE ===\n";
$stmt = $db->query('DESCRIBE medications');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . "\n";
}

echo "\n=== USERS TABLE ===\n";
$stmt = $db->query('DESCRIBE users');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . "\n";
}

echo "\n=== USERS DATA ===\n";
$stmt = $db->query('SELECT id, name, email, role FROM users');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['id']}: {$row['name']} ({$row['email']}) - {$row['role']}\n";
}
