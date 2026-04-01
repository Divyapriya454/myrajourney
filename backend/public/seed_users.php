<?php
require __DIR__ . '/../src/bootstrap.php';

use Src\Config\DB;

echo "Seeding Database Users...\n";

// 1. Define the password for everyone
$password = 'password123';
$hash = password_hash($password, PASSWORD_BCRYPT);

// 2. Define Users (Admin, Doctor, Patient)
$users = [
    [
        'name' => 'System Admin',
        'email' => 'dmin@myrajourney.com',
        'role' => 'ADMIN',
        'phone' => '2111111111'
    ],
    [
        'name' => 'Dr. Smith',
        'email' => 'octor@myrajourney.com',
        'role' => 'DOCTOR',
        'phone' => '3222222222'
    ],
    [
        'name' => 'John Doe',
        'email' => 'atient@myrajourney.com',
        'role' => 'PATIENT',
        'phone' => '3633333333'
    ]
];

try {
    $pdo = DB::conn();

    foreach ($users as $user) {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$user['email']]);
        if ($stmt->fetch()) {
            echo "Skipping: {$user['email']} (Already exists)\n";
            continue;
        }

        // Insert User
        $sql = "INSERT INTO users (name, email, password_hash, role, phone, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'ACTIVE', NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user['name'],
            $user['email'],
            $hash,
            $user['role'],
            $user['phone']
        ]);

        echo "Created: {$user['role']} - {$user['email']} (Password: $password)\n";
    }
    echo "\n✅ Seeding Complete!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
