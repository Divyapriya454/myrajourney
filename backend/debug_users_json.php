<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$res = [];

$stmt = $db->query("SELECT id, name, email, role FROM users WHERE role IN ('PATIENT', 'DOCTOR')");
$res['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT * FROM patients");
$res['patients_table'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT patient_id, COUNT(*) as count FROM rehab_plans GROUP BY patient_id");
$res['rehab_counts_by_patient'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($res, JSON_PRETTY_PRINT);
?>
