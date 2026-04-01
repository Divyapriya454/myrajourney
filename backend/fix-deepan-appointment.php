<?php
require __DIR__ . '/src/bootstrap.php';
$db = Src\Config\DB::conn();

echo "=== Creating Appointment for Deepan ===" . PHP_EOL . PHP_EOL;

// Find Deepan
$stmt = $db->query("SELECT id, name FROM users WHERE name = 'Deepan' AND role = 'PATIENT'");
$patient = $stmt->fetch();

if (!$patient) {
    echo "✗ Deepan not found" . PHP_EOL;
    exit(1);
}

echo "Patient: {$patient['name']} (ID: {$patient['id']})" . PHP_EOL;

// Find a doctor
$stmt = $db->query("SELECT id, name FROM users WHERE role = 'DOCTOR' LIMIT 1");
$doctor = $stmt->fetch();

if (!$doctor) {
    echo "✗ No doctor found in database" . PHP_EOL;
    exit(1);
}

echo "Doctor: {$doctor['name']} (ID: {$doctor['id']})" . PHP_EOL . PHP_EOL;

// Check if appointment already exists
$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = :pid AND doctor_id = :did");
$stmt->execute([':pid' => $patient['id'], ':did' => $doctor['id']]);
$exists = $stmt->fetchColumn();

if ($exists) {
    echo "✓ Appointment already exists" . PHP_EOL;
} else {
    // Create appointment
    $stmt = $db->prepare("INSERT INTO appointments 
                         (patient_id, doctor_id, title, start_time, end_time, status, created_at, updated_at)
                         VALUES (:pid, :did, :title, :start, :end, 'SCHEDULED', NOW(), NOW())");
    
    $startTime = date('Y-m-d H:i:s', strtotime('+7 days 10:00'));
    $endTime = date('Y-m-d H:i:s', strtotime('+7 days 11:00'));
    
    $stmt->execute([
        ':pid' => $patient['id'],
        ':did' => $doctor['id'],
        ':title' => 'Follow-up Consultation',
        ':start' => $startTime,
        ':end' => $endTime
    ]);
    
    echo "✓ Created appointment for $startTime" . PHP_EOL;
}

echo PHP_EOL . "Now when Deepan logs symptoms or uploads reports," . PHP_EOL;
echo "Doctor {$doctor['name']} will receive notifications!" . PHP_EOL;
