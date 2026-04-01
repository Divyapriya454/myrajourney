<?php
require __DIR__ . '/src/bootstrap.php';
use Src\Config\DB;

$db = DB::conn();

echo "=== Testing Report Status Update ===" . PHP_EOL . PHP_EOL;

// Find a test report
$stmt = $db->query("SELECT r.id, r.patient_id, r.title, r.status, u.name as patient_name
                    FROM reports r
                    LEFT JOIN users u ON r.patient_id = u.id
                    ORDER BY r.uploaded_at DESC
                    LIMIT 1");
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    echo "✗ No reports found in database" . PHP_EOL;
    exit(1);
}

echo "Test Report:" . PHP_EOL;
echo "  ID: {$report['id']}" . PHP_EOL;
echo "  Patient: {$report['patient_name']}" . PHP_EOL;
echo "  Title: {$report['title']}" . PHP_EOL;
echo "  Current Status: {$report['status']}" . PHP_EOL . PHP_EOL;

// Test status updates
$testStatuses = ['Normal', 'Abnormal', 'Reviewed', 'Pending'];

foreach ($testStatuses as $newStatus) {
    echo "Testing status update to: $newStatus" . PHP_EOL;
    
    $stmt = $db->prepare("UPDATE reports SET status = :status WHERE id = :id");
    $result = $stmt->execute([
        ':status' => $newStatus,
        ':id' => $report['id']
    ]);
    
    if ($result) {
        // Verify the update
        $stmt = $db->prepare("SELECT status FROM reports WHERE id = :id");
        $stmt->execute([':id' => $report['id']]);
        $currentStatus = $stmt->fetchColumn();
        
        if ($currentStatus === $newStatus) {
            echo "  ✓ Status updated successfully to: $currentStatus" . PHP_EOL;
        } else {
            echo "  ✗ Status mismatch: expected $newStatus, got $currentStatus" . PHP_EOL;
        }
    } else {
        echo "  ✗ Update failed" . PHP_EOL;
    }
    echo PHP_EOL;
}

// Restore original status
$stmt = $db->prepare("UPDATE reports SET status = :status WHERE id = :id");
$stmt->execute([
    ':status' => $report['status'],
    ':id' => $report['id']
]);

echo "✓ Restored original status: {$report['status']}" . PHP_EOL;
echo PHP_EOL . "=== Test Complete ===" . PHP_EOL;
