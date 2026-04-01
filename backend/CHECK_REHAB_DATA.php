<?php
require_once __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();

echo "=== CHECKING REHAB DATA ===\n\n";

// Check total plans
$stmt = $db->query('SELECT COUNT(*) as count FROM rehab_plans');
echo "Total rehab plans: " . $stmt->fetchColumn() . "\n";

// Check total exercises
$stmt = $db->query('SELECT COUNT(*) as count FROM rehab_exercises');
echo "Total exercises: " . $stmt->fetchColumn() . "\n\n";

// Check latest plans
echo "Latest 5 plans:\n";
$stmt = $db->query('SELECT id, patient_id, doctor_id, title, created_at FROM rehab_plans ORDER BY id DESC LIMIT 5');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  ID: {$row['id']}, Patient: {$row['patient_id']}, Doctor: {$row['doctor_id']}, Title: {$row['title']}\n";
    
    // Check exercises for this plan
    $stmtEx = $db->prepare('SELECT COUNT(*) FROM rehab_exercises WHERE plan_id = ? OR rehab_plan_id = ?');
    $stmtEx->execute([$row['id'], $row['id']]);
    $exCount = $stmtEx->fetchColumn();
    echo "    Exercises: $exCount\n";
}

// Test RehabModel
echo "\n=== TESTING RehabModel ===\n";
$rehabModel = new Src\Models\RehabModel();

$patient = $db->query("SELECT id FROM users WHERE role = 'PATIENT' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "Testing with patient ID: {$patient['id']}\n";

$plans = $rehabModel->plans($patient['id']);
echo "Plans returned by RehabModel: " . count($plans) . "\n";

if (count($plans) > 0) {
    echo "\nFirst plan details:\n";
    $firstPlan = $plans[0];
    echo "  ID: {$firstPlan['id']}\n";
    echo "  Title: {$firstPlan['title']}\n";
    echo "  Exercises: " . (isset($firstPlan['exercises']) ? count($firstPlan['exercises']) : 'NOT SET') . "\n";
    
    if (isset($firstPlan['exercises']) && count($firstPlan['exercises']) > 0) {
        echo "  First exercise: {$firstPlan['exercises'][0]['name']}\n";
    }
}
