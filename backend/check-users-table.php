<?php
require_once __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();

echo "Users Table Structure:\n";
echo str_repeat('=', 80) . "\n";

$stmt = $db->query('DESCRIBE users');
while($row = $stmt->fetch()) {
    echo $row['Field'] . ' (' . $row['Type'] . ')' . PHP_EOL;
}

echo "\nUsers Data:\n";
echo str_repeat('=', 80) . "\n";

$stmt = $db->query('SELECT * FROM users');
$users = $stmt->fetchAll();

foreach ($users as $user) {
    echo "ID: {$user['id']}\n";
    echo "Name: {$user['name']}\n";
    echo "Email: {$user['email']}\n";
    echo "Role: {$user['role']}\n";
    foreach ($user as $key => $value) {
        if (!in_array($key, ['id', 'name', 'email', 'role', 'password'])) {
            echo "$key: $value\n";
        }
    }
    echo str_repeat('-', 80) . "\n";
}
