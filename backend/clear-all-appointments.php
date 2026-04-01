<?php
require __DIR__ . '/src/bootstrap.php';
use Src\Config\DB;

$db = DB::conn();

echo "========================================" . PHP_EOL;
echo "  Clearing All Appointments" . PHP_EOL;
echo "========================================" . PHP_EOL . PHP_EOL;

// Count current appointments
$stmt = $db->query("SELECT COUNT(*) FROM appointments");
$count = $stmt->fetchColumn();

echo "Current appointments: $count" . PHP_EOL . PHP_EOL;

if ($count > 0) {
    echo "Deleting all appointments..." . PHP_EOL;
    
    // Delete all appointments
    $db->query("DELETE FROM appointments");
    
    echo "✓ Deleted $count appointments" . PHP_EOL;
    
    // Reset auto-increment
    $db->query("ALTER TABLE appointments AUTO_INCREMENT = 1");
    echo "✓ Reset appointment ID counter" . PHP_EOL;
} else {
    echo "✓ No appointments to delete" . PHP_EOL;
}

// Also clean up appointment-related notifications
echo PHP_EOL . "Cleaning appointment notifications..." . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM notifications WHERE type IN ('APPOINTMENT_SCHEDULED', 'APPOINTMENT_REMINDER', 'APPOINTMENT_CANCELLED')");
$notifCount = $stmt->fetchColumn();

if ($notifCount > 0) {
    $db->query("DELETE FROM notifications WHERE type IN ('APPOINTMENT_SCHEDULED', 'APPOINTMENT_REMINDER', 'APPOINTMENT_CANCELLED')");
    echo "✓ Deleted $notifCount appointment notifications" . PHP_EOL;
} else {
    echo "✓ No appointment notifications to delete" . PHP_EOL;
}

echo PHP_EOL . "========================================" . PHP_EOL;
echo "  Cleanup Complete!" . PHP_EOL;
echo "========================================" . PHP_EOL . PHP_EOL;

// Verify
$stmt = $db->query("SELECT COUNT(*) FROM appointments");
$remaining = $stmt->fetchColumn();

echo "Verification:" . PHP_EOL;
echo "  - Appointments remaining: $remaining" . PHP_EOL;

$stmt = $db->query("SELECT COUNT(*) FROM notifications");
echo "  - Total notifications: " . $stmt->fetchColumn() . PHP_EOL;

echo PHP_EOL . "All appointments have been cleared!" . PHP_EOL;
