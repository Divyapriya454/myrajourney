<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');

echo "--- Recent Plans and their Exercise/Schedule counts ---\n";
$stmt = $db->query("
    SELECT 
        rp.id, 
        rp.patient_id, 
        rp.title, 
        (SELECT COUNT(*) FROM rehab_exercises WHERE plan_id = rp.id OR rehab_plan_id = rp.id) as ex_count,
        (SELECT COUNT(*) FROM exercise_schedule WHERE rehab_plan_id = rp.id) as sched_count
    FROM rehab_plans rp 
    ORDER BY rp.id DESC 
    LIMIT 10
");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($plans as $p) {
    echo "Plan ID: {$p['id']} | Patient: {$p['patient_id']} | Title: {$p['title']} | Exercises: {$p['ex_count']} | Schedules: {$p['sched_count']}\n";
}

echo "\n--- Recent Schedule Dates for Patient 1 ---\n";
$stmt = $db->query("SELECT DISTINCT schedule_date FROM exercise_schedule WHERE patient_id = 1 ORDER BY schedule_date DESC LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Date: {$row['schedule_date']}\n";
}
?>
