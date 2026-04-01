<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');

$stmt = $db->query("SELECT * FROM rehab_plans ORDER BY id DESC LIMIT 1");
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if ($plan) {
    echo "PLAN ID: " . $plan['id'] . "\n";
    echo "TITLE: " . $plan['title'] . "\n";
    echo "START DATE: " . $plan['start_date'] . "\n";
    
    $stmtEx = $db->prepare("SELECT * FROM rehab_exercises WHERE plan_id = ? OR rehab_plan_id = ?");
    $stmtEx->execute([$plan['id'], $plan['id']]);
    $exercises = $stmtEx->fetchAll(PDO::FETCH_ASSOC);
    
    echo "EXERCISES COUNT: " . count($exercises) . "\n";
    echo json_encode($exercises, JSON_PRETTY_PRINT);
} else {
    echo "No plans found.";
}
?>
