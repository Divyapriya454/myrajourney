<?php
require __DIR__ . '/src/bootstrap.php';
use Src\Config\DB;

$db = DB::conn();

echo "========================================" . PHP_EOL;
echo "  Cleaning Up Test Data" . PHP_EOL;
echo "========================================" . PHP_EOL . PHP_EOL;

$confirm = readline("This will delete test data. Continue? (yes/no): ");

if (strtolower(trim($confirm)) !== 'yes') {
    echo "Cleanup cancelled." . PHP_EOL;
    exit(0);
}

echo PHP_EOL . "Starting cleanup..." . PHP_EOL . PHP_EOL;

// 1. Clean up test symptom logs
echo "[1/6] Cleaning symptom logs..." . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM symptom_logs WHERE notes LIKE '%test%' OR notes LIKE '%Test%'");
$count = $stmt->fetchColumn();
if ($count > 0) {
    $db->query("DELETE FROM symptom_logs WHERE notes LIKE '%test%' OR notes LIKE '%Test%'");
    echo "  ✓ Deleted $count test symptom logs" . PHP_EOL;
} else {
    echo "  ✓ No test symptom logs found" . PHP_EOL;
}

// 2. Clean up test reports
echo PHP_EOL . "[2/6] Cleaning test reports..." . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM reports WHERE title LIKE '%test%' OR title LIKE '%Test%' OR description LIKE '%test%'");
$count = $stmt->fetchColumn();
if ($count > 0) {
    $db->query("DELETE FROM reports WHERE title LIKE '%test%' OR title LIKE '%Test%' OR description LIKE '%test%'");
    echo "  ✓ Deleted $count test reports" . PHP_EOL;
} else {
    echo "  ✓ No test reports found" . PHP_EOL;
}

// 3. Clean up test notifications
echo PHP_EOL . "[3/6] Cleaning test notifications..." . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM notifications WHERE type = 'TEST' OR title LIKE '%test%' OR title LIKE '%Test%'");
$count = $stmt->fetchColumn();
if ($count > 0) {
    $db->query("DELETE FROM notifications WHERE type = 'TEST' OR title LIKE '%test%' OR title LIKE '%Test%'");
    echo "  ✓ Deleted $count test notifications" . PHP_EOL;
} else {
    echo "  ✓ No test notifications found" . PHP_EOL;
}

// 4. Clean up old read notifications (older than 30 days)
echo PHP_EOL . "[4/6] Cleaning old read notifications..." . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM notifications WHERE read_at IS NOT NULL AND read_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
$count = $stmt->fetchColumn();
if ($count > 0) {
    $db->query("DELETE FROM notifications WHERE read_at IS NOT NULL AND read_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    echo "  ✓ Deleted $count old read notifications" . PHP_EOL;
} else {
    echo "  ✓ No old notifications to clean" . PHP_EOL;
}

// 5. Clean up test report notes
echo PHP_EOL . "[5/6] Cleaning test report notes..." . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM report_notes WHERE diagnosis_text LIKE '%test%' OR suggestions_text LIKE '%test%'");
$count = $stmt->fetchColumn();
if ($count > 0) {
    $db->query("DELETE FROM report_notes WHERE diagnosis_text LIKE '%test%' OR suggestions_text LIKE '%test%'");
    echo "  ✓ Deleted $count test report notes" . PHP_EOL;
} else {
    echo "  ✓ No test report notes found" . PHP_EOL;
}

// 6. Clean up orphaned report notes (reports that don't exist)
echo PHP_EOL . "[6/6] Cleaning orphaned report notes..." . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM report_notes WHERE report_id NOT IN (SELECT id FROM reports)");
$count = $stmt->fetchColumn();
if ($count > 0) {
    $db->query("DELETE FROM report_notes WHERE report_id NOT IN (SELECT id FROM reports)");
    echo "  ✓ Deleted $count orphaned report notes" . PHP_EOL;
} else {
    echo "  ✓ No orphaned report notes found" . PHP_EOL;
}

// Summary
echo PHP_EOL . "========================================" . PHP_EOL;
echo "  Cleanup Complete!" . PHP_EOL;
echo "========================================" . PHP_EOL . PHP_EOL;

echo "Current data summary:" . PHP_EOL;
$stmt = $db->query("SELECT COUNT(*) FROM symptom_logs");
echo "  - Symptom logs: " . $stmt->fetchColumn() . PHP_EOL;

$stmt = $db->query("SELECT COUNT(*) FROM reports");
echo "  - Reports: " . $stmt->fetchColumn() . PHP_EOL;

$stmt = $db->query("SELECT COUNT(*) FROM notifications");
echo "  - Notifications: " . $stmt->fetchColumn() . PHP_EOL;

$stmt = $db->query("SELECT COUNT(*) FROM report_notes");
echo "  - Report notes: " . $stmt->fetchColumn() . PHP_EOL;

echo PHP_EOL;
