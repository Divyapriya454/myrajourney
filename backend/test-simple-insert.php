<?php
require __DIR__ . '/src/bootstrap.php';
use Src\Config\DB;

$db = DB::conn();

// Get test patient
$stmt = $db->query("SELECT id FROM users WHERE role = 'PATIENT' LIMIT 1");
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
$pid = $patient['id'];

echo "Testing symptom insert with zero values..." . PHP_EOL;

// Test with zero values
$stmt = $db->prepare('INSERT INTO symptom_logs (patient_id, `date`, pain_level, stiffness_level, fatigue_level, notes, created_at) VALUES (:pid, :date, :pain, :stiff, :fatigue, :notes, NOW())');
$stmt->execute([
    ':pid' => $pid,
    ':date' => '2024-12-02',
    ':pain' => 0,
    ':stiff' => 0,
    ':fatigue' => 0,
    ':notes' => 'Test with all zeros',
]);
$id = $db->lastInsertId();

echo "✓ Successfully inserted symptom with ID: $id" . PHP_EOL;
echo "  Pain: 0, Stiffness: 0, Fatigue: 0" . PHP_EOL;

// Verify
$stmt = $db->prepare('SELECT * FROM symptom_logs WHERE id = :id');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "✓ Verified in database:" . PHP_EOL;
echo "  pain_level: {$row['pain_level']}" . PHP_EOL;
echo "  stiffness_level: {$row['stiffness_level']}" . PHP_EOL;
echo "  fatigue_level: {$row['fatigue_level']}" . PHP_EOL;

// Clean up
$stmt = $db->prepare('DELETE FROM symptom_logs WHERE id = :id');
$stmt->execute([':id' => $id]);
echo "✓ Test data cleaned up" . PHP_EOL;
