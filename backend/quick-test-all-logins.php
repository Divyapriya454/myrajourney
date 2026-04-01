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
echo "Testing All User Logins\n";
echo "===========================================\n\n";

// Get all users
$stmt = $pdo->query("SELECT u.*, p.id as patient_id, d.id as doctor_id 
                     FROM users u 
                     LEFT JOIN patients p ON u.id = p.user_id 
                     LEFT JOIN doctors d ON u.id = d.user_id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    echo "User: {$user['name']} ({$user['email']})\n";
    echo "Role: {$user['role']}\n";
    echo "Patient ID: " . ($user['patient_id'] ?? 'N/A') . "\n";
    echo "Doctor ID: " . ($user['doctor_id'] ?? 'N/A') . "\n";
    echo "Status: {$user['status']}\n";
    echo "-------------------------------------------\n";
}

echo "\n===========================================\n";
echo "Testing API Login Endpoints\n";
echo "===========================================\n\n";

$testUsers = [
    ['email' => 'deepankumar@gmail.com', 'password' => 'Welcome@456', 'role' => 'PATIENT'],
    ['email' => 'doctor@test.com', 'password' => 'Doctor@123', 'role' => 'DOCTOR'],
    ['email' => 'testadmin@test.com', 'password' => 'Admin@123', 'role' => 'ADMIN']
];

foreach ($testUsers as $testUser) {
    $ch = curl_init('http://10.108.1.165:8000/api/v1/auth/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testUser));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    echo "{$testUser['role']} Login ({$testUser['email']}): ";
    if ($httpCode == 200 && isset($result['success']) && $result['success']) {
        echo "✓ SUCCESS\n";
        echo "  Token: " . substr($result['data']['token'], 0, 50) . "...\n";
    } else {
        echo "✗ FAILED (HTTP $httpCode)\n";
        echo "  Response: " . json_encode($result) . "\n";
    }
    echo "-------------------------------------------\n";
}
