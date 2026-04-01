<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/../src/bootstrap.php';

use Src\Config\DB;

$db = DB::conn();
$stmt = $db->prepare("SELECT u.id, u.name, u.email, u.role, doc.specialization 
    FROM users u 
    LEFT JOIN doctors doc ON u.id = doc.id 
    WHERE u.role IN ('PATIENT', 'DOCTOR') AND u.status = 'ACTIVE' 
    ORDER BY u.created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $users, 'count' => count($users)]);
