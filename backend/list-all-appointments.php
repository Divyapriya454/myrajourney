<?php
require __DIR__ . '/src/bootstrap.php';
$db = Src\Config\DB::conn();

echo "=== All Appointments in Database ===" . PHP_EOL . PHP_EOL;

$stmt = $db->query("SELECT a.id, a.patient_id, a.doctor_id, a.title, a.start_time, a.status, a.created_at,
                    p.name as patient_name, d.name as doctor_name
                    FROM appointments a
                    LEFT JOIN users p ON a.patient_id = p.id
                    LEFT JOIN users d ON a.doctor_id = d.id
                    ORDER BY a.created_at DESC");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($appointments)) {
    echo "No appointments found" . PHP_EOL;
    exit(0);
}

echo "Total appointments: " . count($appointments) . PHP_EOL . PHP_EOL;

$testCount = 0;
foreach ($appointments as $apt) {
    $isTest = ($apt['title'] === 'Follow-up Consultation') ? ' [TEST]' : '';
    if ($isTest) $testCount++;
    
    echo "ID: {$apt['id']}$isTest" . PHP_EOL;
    echo "  Patient: {$apt['patient_name']} (ID: {$apt['patient_id']})" . PHP_EOL;
    echo "  Doctor: {$apt['doctor_name']} (ID: {$apt['doctor_id']})" . PHP_EOL;
    echo "  Title: {$apt['title']}" . PHP_EOL;
    echo "  Start: {$apt['start_time']}" . PHP_EOL;
    echo "  Status: {$apt['status']}" . PHP_EOL;
    echo "  Created: {$apt['created_at']}" . PHP_EOL;
    echo PHP_EOL;
}

if ($testCount > 0) {
    echo "⚠️  Found $testCount test appointment(s) marked with [TEST]" . PHP_EOL;
    echo "Run 'php backend/cleanup-test-appointments.php' to remove them" . PHP_EOL;
}
