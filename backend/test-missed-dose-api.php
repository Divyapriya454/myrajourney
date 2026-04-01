<?php

require __DIR__ . '/src/bootstrap.php';

echo "=== Testing Missed Dose API ===" . PHP_EOL . PHP_EOL;

// Test data
$testData = [
    'patient_medication_id' => '1',
    'medication_name' => 'Methotrexate',
    'scheduled_time' => '2024-12-16 10:00:00',
    'missed_time' => '2024-12-16 12:30:00',
    'reason' => 'forgot',
    'notes' => 'Was in a meeting and forgot to take medication'
];

echo "Test Data:" . PHP_EOL;
echo json_encode($testData, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

// Simulate the API call
try {
    $db = Src\Config\DB::conn();
    
    // Insert test report
    $stmt = $db->prepare("
        INSERT INTO missed_dose_reports 
        (patient_medication_id, medication_name, scheduled_time, missed_time, reason, notes, doctor_id, doctor_notified, reported_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $testData['patient_medication_id'],
        $testData['medication_name'],
        $testData['scheduled_time'],
        $testData['missed_time'],
        $testData['reason'],
        $testData['notes'],
        null, // doctor_id
        false // doctor_notified
    ]);
    
    $reportId = $db->lastInsertId();
    
    echo "✓ Missed dose report created successfully!" . PHP_EOL;
    echo "Report ID: $reportId" . PHP_EOL . PHP_EOL;
    
    // Retrieve the report
    $stmt = $db->prepare("SELECT * FROM missed_dose_reports WHERE id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Retrieved Report:" . PHP_EOL;
    echo json_encode($report, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;
    
    // Test getting all reports
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM missed_dose_reports");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total reports in database: " . $count['count'] . PHP_EOL;
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . PHP_EOL;
}
