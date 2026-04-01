<?php
require __DIR__ . '/src/bootstrap.php';
$db = Src\Config\DB::conn();

// Find Deepan
$stmt = $db->query("SELECT id, name, role FROM users WHERE name = 'Deepan'");
$user = $stmt->fetch();

if (!$user) {
    echo "Deepan not found" . PHP_EOL;
    exit(1);
}

echo "Deepan ID: {$user['id']}" . PHP_EOL;
echo "Role: {$user['role']}" . PHP_EOL . PHP_EOL;

// Check appointments
$stmt = $db->prepare('SELECT a.doctor_id, u.name as doctor_name 
                      FROM appointments a
                      LEFT JOIN users u ON a.doctor_id = u.id
                      WHERE a.patient_id = :pid');
$stmt->execute([':pid' => $user['id']]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($appointments)) {
    echo "✗ Deepan has NO appointments with any doctor!" . PHP_EOL;
    echo "This is why doctors are not getting notified." . PHP_EOL;
} else {
    echo "✓ Deepan has appointments with:" . PHP_EOL;
    foreach ($appointments as $apt) {
        echo "  - Doctor: {$apt['doctor_name']} (ID: {$apt['doctor_id']})" . PHP_EOL;
    }
}
