<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$res = [];

$stmt = $db->query("SELECT id, patient_id, title, created_at FROM rehab_plans ORDER BY id DESC LIMIT 5");
$res['plans'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT id, plan_id, rehab_plan_id, exercise_name FROM rehab_exercises ORDER BY id DESC LIMIT 5");
$res['exercises'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT rp.id as plan_id, COUNT(re.id) as exercise_count FROM rehab_plans rp LEFT JOIN rehab_exercises re ON (re.plan_id = rp.id OR re.rehab_plan_id = rp.id) GROUP BY rp.id ORDER BY rp.id DESC LIMIT 5");
$res['counts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT id, name, role FROM users WHERE role = 'PATIENT' LIMIT 3");
$res['patients'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($res, JSON_PRETTY_PRINT);
?>
