<?php
require __DIR__ . '/src/bootstrap.php';
use Src\Config\DB;

$db = DB::conn();

echo "========================================" . PHP_EOL;
echo "  Cleaning Appointments & Notifications" . PHP_EOL;
echo "========================================" . PHP_EOL . PHP_EOL;

// 1. Remove duplicate appointments
echo "[1/4] Removing duplicate appointments..." . PHP_EOL;

// Find duplicates (same patient, doctor, and date)
$stmt = $db->query("
    SELECT patient_id, doctor_id, DATE(start_time) as appt_date, COUNT(*) as count, GROUP_CONCAT(id) as ids
    FROM appointments
    GROUP BY patient_id, doctor_id, DATE(start_time)
    HAVING count > 1
");

$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
$deletedCount = 0;

foreach ($duplicates as $dup) {
    $ids = explode(',', $dup['ids']);
    // Keep the first one, delete the rest
    array_shift($ids);
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("DELETE FROM appointments WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $deletedCount += count($ids);
    }
}

echo "  ✓ Deleted $deletedCount duplicate appointments" . PHP_EOL;

// 2. Remove old/past appointments (older than 30 days)
echo PHP_EOL . "[2/4] Removing old past appointments..." . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM appointments WHERE start_time < DATE_SUB(NOW(), INTERVAL 30 DAY)");
$count = $stmt->fetchColumn();

if ($count > 0) {
    $db->query("DELETE FROM appointments WHERE start_time < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    echo "  ✓ Deleted $count old appointments" . PHP_EOL;
} else {
    echo "  ✓ No old appointments to clean" . PHP_EOL;
}

// 3. Remove duplicate appointment notifications
echo PHP_EOL . "[3/4] Removing duplicate appointment notifications..." . PHP_EOL;

// Find duplicate notifications (same user, type, and created within 1 minute)
$stmt = $db->query("
    SELECT user_id, type, title, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as time_group, 
           COUNT(*) as count, GROUP_CONCAT(id) as ids
    FROM notifications
    WHERE type IN ('APPOINTMENT_SCHEDULED', 'APPOINTMENT_REMINDER', 'APPOINTMENT_CANCELLED')
    GROUP BY user_id, type, title, time_group
    HAVING count > 1
");

$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
$deletedCount = 0;

foreach ($duplicates as $dup) {
    $ids = explode(',', $dup['ids']);
    // Keep the first one, delete the rest
    array_shift($ids);
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("DELETE FROM notifications WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $deletedCount += count($ids);
    }
}

echo "  ✓ Deleted $deletedCount duplicate notifications" . PHP_EOL;

// 4. Remove duplicate symptom/report notifications
echo PHP_EOL . "[4/4] Removing duplicate symptom/report notifications..." . PHP_EOL;

$stmt = $db->query("
    SELECT user_id, type, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as time_group, 
           COUNT(*) as count, GROUP_CONCAT(id) as ids
    FROM notifications
    WHERE type IN ('PATIENT_SYMPTOM', 'PATIENT_REPORT')
    GROUP BY user_id, type, time_group
    HAVING count > 1
");

$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
$deletedCount = 0;

foreach ($duplicates as $dup) {
    $ids = explode(',', $dup['ids']);
    // Keep the first one, delete the rest
    array_shift($ids);
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("DELETE FROM notifications WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $deletedCount += count($ids);
    }
}

echo "  ✓ Deleted $deletedCount duplicate symptom/report notifications" . PHP_EOL;

// Summary
echo PHP_EOL . "========================================" . PHP_EOL;
echo "  Cleanup Complete!" . PHP_EOL;
echo "========================================" . PHP_EOL . PHP_EOL;

echo "Current data summary:" . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM appointments");
echo "  - Appointments: " . $stmt->fetchColumn() . PHP_EOL;

$stmt = $db->query("SELECT COUNT(*) FROM appointments WHERE start_time >= NOW()");
echo "  - Future appointments: " . $stmt->fetchColumn() . PHP_EOL;

$stmt = $db->query("SELECT COUNT(*) FROM notifications");
echo "  - Notifications: " . $stmt->fetchColumn() . PHP_EOL;

$stmt = $db->query("SELECT COUNT(*) FROM notifications WHERE read_at IS NULL");
echo "  - Unread notifications: " . $stmt->fetchColumn() . PHP_EOL;

echo PHP_EOL;
