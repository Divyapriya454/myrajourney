<?php
require __DIR__ . '/src/bootstrap.php';
$db = Src\Config\DB::conn();

echo "=== Cleaning Up Test Appointments ===" . PHP_EOL . PHP_EOL;

// Find all "Follow-up Consultation" appointments created by test scripts
$stmt = $db->query("SELECT id, patient_id, doctor_id, title, start_time, created_at
                    FROM appointments
                    WHERE title = 'Follow-up Consultation'
                    ORDER BY created_at DESC");
$testAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($testAppointments)) {
    echo "✓ No test appointments found to clean up" . PHP_EOL;
    exit(0);
}

echo "Found " . count($testAppointments) . " test appointment(s):" . PHP_EOL . PHP_EOL;

foreach ($testAppointments as $apt) {
    // Get patient and doctor names
    $stmt = $db->prepare("SELECT name FROM users WHERE id = :id");
    $stmt->execute([':id' => $apt['patient_id']]);
    $patientName = $stmt->fetchColumn();
    
    $stmt->execute([':id' => $apt['doctor_id']]);
    $doctorName = $stmt->fetchColumn();
    
    echo "Appointment ID: {$apt['id']}" . PHP_EOL;
    echo "  Patient: $patientName (ID: {$apt['patient_id']})" . PHP_EOL;
    echo "  Doctor: $doctorName (ID: {$apt['doctor_id']})" . PHP_EOL;
    echo "  Title: {$apt['title']}" . PHP_EOL;
    echo "  Start Time: {$apt['start_time']}" . PHP_EOL;
    echo "  Created: {$apt['created_at']}" . PHP_EOL;
    echo PHP_EOL;
}

echo "Do you want to delete these test appointments? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) === 'y') {
    $stmt = $db->prepare("DELETE FROM appointments WHERE title = 'Follow-up Consultation'");
    $stmt->execute();
    $deleted = $stmt->rowCount();
    
    echo PHP_EOL . "✓ Deleted $deleted test appointment(s)" . PHP_EOL;
    echo "Patients will now only see appointments created by doctors" . PHP_EOL;
} else {
    echo PHP_EOL . "✗ Cleanup cancelled" . PHP_EOL;
}
