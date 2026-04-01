<?php
$host = 'localhost';
$dbname = 'myrajourney';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

echo "===========================================\n";
echo "Fixing All User Passwords\n";
echo "===========================================\n\n";

$users = [
    ['email' => 'deepankumar@gmail.com', 'password' => 'Welcome@456', 'role' => 'PATIENT'],
    ['email' => 'doctor@test.com', 'password' => 'Doctor@123', 'role' => 'DOCTOR'],
    ['email' => 'testadmin@test.com', 'password' => 'Admin@123', 'role' => 'ADMIN']
];

foreach ($users as $user) {
    $hashedPassword = password_hash($user['password'], PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$hashedPassword, $user['email']]);
    
    echo "{$user['role']}: {$user['email']}\n";
    echo "  Password: {$user['password']}\n";
    echo "  ✓ Updated\n";
    echo "-------------------------------------------\n";
}

echo "\n✓ All passwords updated successfully!\n";
