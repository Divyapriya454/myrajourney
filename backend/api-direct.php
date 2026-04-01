<?php
// Direct API endpoint - bypasses routing
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require __DIR__ . '/../src/bootstrap.php';

use Src\Config\DB;
use Src\Utils\Jwt;

// Get action from query parameter
$action = $_GET['action'] ?? '';

// Get authorization token
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = $matches[1];
    $payload = Jwt::decode($token, $_ENV['JWT_SECRET'] ?? '');
    $userId = $payload['uid'] ?? 0;
    $userRole = $payload['role'] ?? '';
} else {
    echo json_encode(['success' => false, 'error' => ['code' => 'UNAUTHORIZED', 'message' => 'No token']]);
    exit;
}

$db = DB::conn();

// Get all users (patients and doctors) for admin
if ($action === 'get_all_users' && $userRole === 'ADMIN') {
    $stmt = $db->prepare("SELECT u.id, u.name, u.email, u.phone, u.role, u.created_at, 
        p.dob, p.gender, p.medical_id, p.address, p.assigned_doctor_id,
        d.name as doctor_name, doc.specialization
        FROM users u
        LEFT JOIN patients p ON u.id = p.id
        LEFT JOIN users d ON p.assigned_doctor_id = d.id
        LEFT JOIN doctors doc ON u.id = doc.id
        WHERE u.role IN ('PATIENT', 'DOCTOR') AND u.status = 'ACTIVE'
        ORDER BY u.role DESC, u.created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $users]);
    exit;
}

// Assign patient to doctor
if ($action === 'assign_patient' && $userRole === 'ADMIN') {
    $input = json_decode(file_get_contents('php://input'), true);
    $patientId = (int)($input['patient_id'] ?? 0);
    $doctorId = isset($input['doctor_id']) ? (int)$input['doctor_id'] : null;
    
    if ($patientId > 0) {
        $stmt = $db->prepare('UPDATE patients SET assigned_doctor_id = :doctor_id, updated_at = NOW() WHERE id = :patient_id');
        $stmt->execute([':doctor_id' => $doctorId, ':patient_id' => $patientId]);
        echo json_encode(['success' => true, 'message' => 'Patient assigned successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => ['code' => 'INVALID_ID', 'message' => 'Invalid patient ID']]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => ['code' => 'INVALID_ACTION', 'message' => 'Invalid action or unauthorized']]);
