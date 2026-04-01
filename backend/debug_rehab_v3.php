<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');

function query($db, $sql, $label) {
    echo "\n--- $label ---\n";
    $stmt = $db->query($sql);
    if (!$stmt) {
        echo "Error: " . print_r($db->errorInfo(), true) . "\n";
        return;
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "No data.\n";
        return;
    }
    foreach ($rows as $row) {
        print_r($row);
    }
}

query($db, "SELECT id, patient_id, title, created_at FROM rehab_plans ORDER BY id DESC LIMIT 5", "RECENT PLANS");
query($db, "SELECT id, plan_id, rehab_plan_id, exercise_name FROM rehab_exercises ORDER BY id DESC LIMIT 5", "RECENT EXERCISES");
query($db, "SELECT rp.id as plan_id, COUNT(re.id) as exercise_count FROM rehab_plans rp LEFT JOIN rehab_exercises re ON (re.plan_id = rp.id OR re.rehab_plan_id = rp.id) GROUP BY rp.id ORDER BY rp.id DESC LIMIT 5", "EXERCISE COUNTS PER PLAN");
query($db, "SELECT id, patient_id, schedule_date FROM exercise_schedule ORDER BY id DESC LIMIT 5", "RECENT SCHEDULES");
?>
