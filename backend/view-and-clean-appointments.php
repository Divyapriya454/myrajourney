<?php
require __DIR__ . '/src/bootstrap.php';
use Src\Config\DB;

$db = DB::conn();

echo "========================================" . PHP_EOL;
echo "  Current Appointments" . PHP_EOL;
echo "========================================" . PHP_EOL . PHP_EOL;

// Show all appointments
$stmt = $db->query("SELECT a.id, a.patient_id, a.doctor_id, a.title, a.start_time, a.end_time, a.status,
                    p.name as patient_name, d.name as doctor_name
                    FROM appointments a
                    LEFT JOIN users p ON a.patient_id = p.id
                    LEFT JOIN users d ON a.doctor_id = d.id
                    ORDER BY a.start_time");

$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($appointments)) {
    echo "No appointments found." . PHP_EOL;
    exit(0);
}

echo "Found " . count($appointments) . " appointment(s):" . PHP_EOL . PHP_EOL;

foreach ($appointments as $apt) {
    echo "ID: {$apt['id']}" . PHP_EOL;
    echo "  Patient: {$apt['patient_name']} (ID: {$apt['patient_id']})" . PHP_EOL;
    echo "  Doctor: {$apt['doctor_name']} (ID: {$apt['doctor_id']})" . PHP_EOL;
    echo "  Title: {$apt['title']}" . PHP_EOL;
    echo "  Time: {$apt['start_time']} to {$apt['end_time']}" . PHP_EOL;
    echo "  Status: {$apt['status']}" . PHP_EOL;
    echo "---" . PHP_EOL;
}

// Delete 2024 appointments (old ones)
echo PHP_EOL . "Removing 2024 appointments..." . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM appointments WHERE YEAR(start_time) = 2024");
$count2024 = $stmt->fetchColumn();

if ($count2024 > 0) {
    $db->query("DELETE FROM appointments WHERE YEAR(start_time) = 2024");
    echo "✓ Deleted $count2024 appointments from 2024" . PHP_EOL;
} else {
    echo "✓ No 2024 appointments to delete" . PHP_EOL;
}

// Show remaining
echo PHP_EOL . "Remaining appointments:" . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM appointments");
$remaining = $stmt->fetchColumn();
echo "  Total: $remaining" . PHP_EOL;

$stmt = $db->query("SELECT COUNT(*) FROM appointments WHERE YEAR(start_time) = 2025");
$count2025 = $stmt->fetchColumn();
echo "  2025: $count2025" . PHP_EOL;

echo PHP_EOL . "✓ Cleanup complete!" . PHP_EOL;
