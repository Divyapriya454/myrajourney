<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Src\Config\DB;

header('Content-Type: application/json');

$db = DB::conn();

// 1. Check Plans
$plans = $db->query("SELECT id, patient_id, title, created_at FROM rehab_plans ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// 2. Check Exercises
$exercises = $db->query("SELECT id, plan_id, name, sets, reps FROM rehab_exercises ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// 3. Check Schedule
$schedule = $db->query("SELECT * FROM exercise_schedule ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// 4. Check Today's count per patient
$todayCount = $db->query("SELECT patient_id, COUNT(*) as count FROM exercise_schedule WHERE schedule_date = CURDATE() GROUP BY patient_id")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'recent_plans' => $plans,
    'recent_exercises' => $exercises,
    'recent_schedules' => $schedule,
    'today_counts' => $todayCount
], JSON_PRETTY_PRINT);
