<?php
require __DIR__ . '/src/bootstrap.php';
$db = Src\Config\DB::conn();

echo "=== Assigning Deepan to Doctor Vinoth ===" . PHP_EOL . PHP_EOL;

// Find Deepan
$stmt = $db->query("SELECT id, name, email, role FROM users WHERE name = 'Deepan' AND role = 'PATIENT'");
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    echo "✗ Deepan not found" . PHP_EOL;
    exit(1);
}

echo "Patient: {$patient['name']} (ID: {$patient['id']}, Email: {$patient['email']})" . PHP_EOL;

// Find Doctor Vinoth
$stmt = $db->query("SELECT id, name, email, role FROM users WHERE name LIKE '%Vinoth%' OR name LIKE '%vinoth%'");
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    echo "✗ Doctor Vinoth not found" . PHP_EOL;
    echo PHP_EOL . "Available doctors:" . PHP_EOL;
    $stmt = $db->query("SELECT id, name, email FROM users WHERE role = 'DOCTOR'");
    while ($doc = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$doc['name']} (ID: {$doc['id']}, Email: {$doc['email']})" . PHP_EOL;
    }
    exit(1);
}

echo "Doctor: {$doctor['name']} (ID: {$doctor['id']}, Email: {$doctor['email']})" . PHP_EOL . PHP_EOL;

// Check if appointment already exists
$stmt = $db->prepare("SELECT id FROM appointments WHERE patient_id = :pid AND doctor_id = :did");
$stmt->execute([':pid' => $patient['id'], ':did' => $doctor['id']]);
$existing = $stmt->fetch();

if ($existing) {
    echo "✓ Appointment already exists (ID: {$existing['id']})" . PHP_EOL;
} else {
    // Create appointment
    $stmt = $db->prepare("INSERT INTO appointments 
                         (patient_id, doctor_id, title, description, start_time, end_time, status, created_at, updated_at)
                         VALUES (:pid, :did, :title, :desc, :start, :end, 'SCHEDULED', NOW(), NOW())");
    
    $startTime = date('Y-m-d H:i:s', strtotime('+7 days 10:00'));
    $endTime = date('Y-m-d H:i:s', strtotime('+7 days 11:00'));
    
    $stmt->execute([
        ':pid' => $patient['id'],
        ':did' => $doctor['id'],
        ':title' => 'Follow-up Consultation',
        ':desc' => 'Regular check-up appointment',
        ':start' => $startTime,
        ':end' => $endTime
    ]);
    
    $appointmentId = $db->lastInsertId();
    echo "✓ Created appointment (ID: $appointmentId)" . PHP_EOL;
    echo "  Date/Time: $startTime" . PHP_EOL;
}

echo PHP_EOL . "=== Assignment Complete ===" . PHP_EOL;
echo "Now when Deepan logs symptoms or uploads reports," . PHP_EOL;
echo "Doctor {$doctor['name']} will receive notifications!" . PHP_EOL;
