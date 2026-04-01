<?php
require_once __DIR__ . '/src/bootstrap.php';
use Src\Config\DB;

$db = DB::conn();
$patientId = 1;

// 1. Fetch Plans
$stmt = $db->prepare("SELECT * FROM rehab_plans WHERE patient_id = ? ORDER BY id DESC");
$stmt->execute([$patientId]);
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($plans as &$plan) {
    // 2. Fetch Exercises for each plan
    $stmtEx = $db->prepare("SELECT * FROM rehab_exercises WHERE plan_id = ? OR rehab_plan_id = ?");
    $stmtEx->execute([$plan['id'], $plan['id']]);
    $plan['exercises'] = $stmtEx->fetchAll(PDO::FETCH_ASSOC);
}

// 3. Fetch Today's Schedule
$today = date('Y-m-d');
$stmtSched = $db->prepare("
    SELECT es.*, re.name as exercise_name, rp.title as plan_title
    FROM exercise_schedule es
    JOIN rehab_exercises re ON es.exercise_id = re.id
    JOIN rehab_plans rp ON es.rehab_plan_id = rp.id
    WHERE es.patient_id = ? AND es.schedule_date = ?
");
$stmtSched->execute([$patientId, $today]);
$schedule = $stmtSched->fetchAll(PDO::FETCH_ASSOC);

$result = [
    'patient_id' => $patientId,
    'current_date' => $today,
    'plans' => $plans,
    'today_schedule' => $schedule
];

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
?>
