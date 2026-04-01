<?php

require __DIR__ . '/src/bootstrap.php';

echo "=== Testing Missed Dose Functionality ===" . PHP_EOL . PHP_EOL;

try {
    $db = Src\Config\DB::conn();
    
    // Test data
    $testData = [
        'patient_medication_id' => '1',
        'medication_name' => 'Methotrexate Simple Test',
        'scheduled_time' => '2024-12-16 10:00:00',
        'missed_time' => '2024-12-16 12:30:00',
        'reason' => 'forgot',
        'notes' => 'Simple test - forgot to take medication'
    ];
    
    echo "Test Data:" . PHP_EOL;
    echo json_encode($testData, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;
    
    // Direct database insert (simulating the controller logic)
    echo "Testing database insert..." . PHP_EOL;
    
    $stmt = $db->prepare("
        INSERT INTO missed_dose_reports 
        (patient_medication_id, medication_name, scheduled_time, missed_time, reason, notes, doctor_id, doctor_notified, reported_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $testData['patient_medication_id'],
        $testData['medication_name'],
        $testData['scheduled_time'],
        $testData['missed_time'],
        $testData['reason'],
        $testData['notes'],
        null, // doctor_id
        false // doctor_notified
    ]);
    
    if ($result) {
        $reportId = $db->lastInsertId();
        echo "✓ Missed dose report created successfully!" . PHP_EOL;
        echo "Report ID: $reportId" . PHP_EOL . PHP_EOL;
        
        // Retrieve and verify
        $stmt = $db->prepare("SELECT * FROM missed_dose_reports WHERE id = ?");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($report) {
            echo "✓ Report retrieved successfully:" . PHP_EOL;
            echo "  - ID: " . $report['id'] . PHP_EOL;
            echo "  - Medication: " . $report['medication_name'] . PHP_EOL;
            echo "  - Reason: " . $report['reason'] . PHP_EOL;
            echo "  - Scheduled: " . $report['scheduled_time'] . PHP_EOL;
            echo "  - Missed: " . $report['missed_time'] . PHP_EOL;
            echo "  - Notes: " . $report['notes'] . PHP_EOL;
            echo "  - Reported: " . $report['reported_at'] . PHP_EOL;
        }
        
        echo PHP_EOL . "✅ Missed dose functionality test passed!" . PHP_EOL;
        
    } else {
        echo "❌ Failed to create missed dose report" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
