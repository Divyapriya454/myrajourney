<?php
require __DIR__ . '/src/bootstrap.php';
use Src\Config\DB;

$db = DB::conn();

echo "=== Notification System Test ===" . PHP_EOL . PHP_EOL;

// Check if we have doctors and patients with appointments
echo "1. Checking appointments..." . PHP_EOL;
$stmt = $db->query("SELECT a.id, a.patient_id, a.doctor_id, 
                    p.name as patient_name, d.name as doctor_name
                    FROM appointments a
                    LEFT JOIN users p ON a.patient_id = p.id
                    LEFT JOIN users d ON a.doctor_id = d.id
                    LIMIT 5");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($appointments)) {
    echo "   ✗ No appointments found!" . PHP_EOL;
    echo "   Doctors won't receive notifications without appointments." . PHP_EOL;
    exit(1);
}

echo "   ✓ Found " . count($appointments) . " appointments" . PHP_EOL;
foreach ($appointments as $apt) {
    echo "     - Patient: {$apt['patient_name']} (ID: {$apt['patient_id']}) → Doctor: {$apt['doctor_name']} (ID: {$apt['doctor_id']})" . PHP_EOL;
}

// Check notifications table
echo PHP_EOL . "2. Checking notifications table..." . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM notifications");
$count = $stmt->fetchColumn();
echo "   Total notifications: $count" . PHP_EOL;

if ($count > 0) {
    echo PHP_EOL . "   Recent notifications:" . PHP_EOL;
    $stmt = $db->query("SELECT n.id, n.user_id, u.name, u.role, n.type, n.title, n.read_at, n.created_at
                        FROM notifications n
                        LEFT JOIN users u ON n.user_id = u.id
                        ORDER BY n.created_at DESC
                        LIMIT 5");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($notifications as $notif) {
        $readStatus = $notif['read_at'] ? '✓ Read' : '✗ Unread';
        echo "     - [{$notif['id']}] {$notif['name']} ({$notif['role']}): {$notif['title']} - $readStatus" . PHP_EOL;
    }
}

// Test creating a notification
echo PHP_EOL . "3. Testing notification creation..." . PHP_EOL;
$testDoctor = $appointments[0]['doctor_id'];
$testPatient = $appointments[0]['patient_id'];

echo "   Creating test notification for doctor ID: $testDoctor" . PHP_EOL;

$stmt = $db->prepare('INSERT INTO notifications (user_id, type, title, body, created_at) 
                      VALUES (:uid, :type, :title, :body, NOW())');
$stmt->execute([
    ':uid' => $testDoctor,
    ':type' => 'TEST',
    ':title' => 'Test Notification',
    ':body' => 'This is a test notification'
]);
$testId = $db->lastInsertId();

echo "   ✓ Created notification ID: $testId" . PHP_EOL;

// Verify it can be retrieved
$stmt = $db->prepare("SELECT id, title, body, (read_at IS NULL) as is_unread FROM notifications WHERE id = :id");
$stmt->execute([':id' => $testId]);
$testNotif = $stmt->fetch(PDO::FETCH_ASSOC);

echo "   ✓ Retrieved: {$testNotif['title']}, Unread: " . ($testNotif['is_unread'] ? 'YES' : 'NO') . PHP_EOL;

// Clean up
$stmt = $db->prepare('DELETE FROM notifications WHERE id = :id');
$stmt->execute([':id' => $testId]);
echo "   ✓ Test notification cleaned up" . PHP_EOL;

// Check symptom logs
echo PHP_EOL . "4. Checking symptom logs..." . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM symptom_logs");
$symptomCount = $stmt->fetchColumn();
echo "   Total symptom logs: $symptomCount" . PHP_EOL;

if ($symptomCount > 0) {
    $stmt = $db->query("SELECT s.id, s.patient_id, u.name, s.date, s.created_at
                        FROM symptom_logs s
                        LEFT JOIN users u ON s.patient_id = u.id
                        ORDER BY s.created_at DESC
                        LIMIT 3");
    $symptoms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Recent symptom logs:" . PHP_EOL;
    foreach ($symptoms as $sym) {
        echo "     - {$sym['name']} logged symptoms on {$sym['date']}" . PHP_EOL;
        
        // Check if notification was created for this symptom
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications 
                             WHERE type = 'PATIENT_SYMPTOM' 
                             AND created_at >= :created");
        $stmt->execute([':created' => $sym['created_at']]);
        $notifCount = $stmt->fetchColumn();
        
        if ($notifCount > 0) {
            echo "       ✓ Notification created" . PHP_EOL;
        } else {
            echo "       ✗ No notification found" . PHP_EOL;
        }
    }
}

// Check reports
echo PHP_EOL . "5. Checking reports..." . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM reports");
$reportCount = $stmt->fetchColumn();
echo "   Total reports: $reportCount" . PHP_EOL;

if ($reportCount > 0) {
    $stmt = $db->query("SELECT r.id, r.patient_id, u.name, r.title, r.status, r.uploaded_at
                        FROM reports r
                        LEFT JOIN users u ON r.patient_id = u.id
                        ORDER BY r.uploaded_at DESC
                        LIMIT 3");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Recent reports:" . PHP_EOL;
    foreach ($reports as $rep) {
        echo "     - [{$rep['id']}] {$rep['name']}: {$rep['title']} (Status: {$rep['status']})" . PHP_EOL;
    }
}

echo PHP_EOL . "=== Test Complete ===" . PHP_EOL;
