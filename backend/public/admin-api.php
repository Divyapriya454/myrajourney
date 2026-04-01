<?php
// Admin API - Direct endpoint for admin operations
// This bypasses the complex routing system

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Bootstrap
require_once __DIR__ . '/../src/bootstrap.php';

use Src\Config\DB;
use Src\Utils\Jwt;

// Get authorization
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (empty($authHeader) && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? '';
}

if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = $matches[1];
    try {
        $payload = Jwt::decode($token, $_ENV['JWT_SECRET'] ?? '');
        $userId = $payload['uid'] ?? 0;
        $userRole = $payload['role'] ?? '';
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => ['code' => 'INVALID_TOKEN', 'message' => 'Invalid token']]);
        exit;
    }
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => ['code' => 'NO_TOKEN', 'message' => 'No authorization token']]);
    exit;
}

// Only allow admin
if ($userRole !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Admin access required']]);
    exit;
}

$db = DB::conn();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// GET all users (patients and doctors)
if ($method === 'GET' && $action === 'users') {
    $stmt = $db->prepare("
        SELECT 
            u.id, 
            u.name, 
            u.email, 
            u.phone, 
            u.role, 
            u.created_at,
            p.assigned_doctor_id,
            doc.specialization
        FROM users u
        LEFT JOIN patients p ON u.id = p.id
        LEFT JOIN doctors doc ON u.id = doc.id
        WHERE u.role IN ('PATIENT', 'DOCTOR') 
        AND u.status = 'ACTIVE'
        ORDER BY u.role DESC, u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $users]);
    exit;
}

// POST assign patient to doctor
if ($method === 'POST' && $action === 'assign') {
    $input = json_decode(file_get_contents('php://input'), true);
    $patientId = (int)($input['patient_id'] ?? 0);
    $doctorId = isset($input['doctor_id']) ? (int)$input['doctor_id'] : null;
    
    if ($patientId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => ['code' => 'INVALID_ID', 'message' => 'Invalid patient ID']]);
        exit;
    }
    
    // Verify patient exists
    $stmt = $db->prepare('SELECT id FROM users WHERE id = :id AND role = "PATIENT"');
    $stmt->execute([':id' => $patientId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Patient not found']]);
        exit;
    }
    
    // Verify doctor exists if provided
    if ($doctorId !== null) {
        $stmt = $db->prepare('SELECT id FROM users WHERE id = :id AND role = "DOCTOR"');
        $stmt->execute([':id' => $doctorId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Doctor not found']]);
            exit;
        }
    }
    
    // Update assignment
    $stmt = $db->prepare('UPDATE patients SET assigned_doctor_id = :doctor_id, updated_at = NOW() WHERE id = :patient_id');
    $stmt->execute([':doctor_id' => $doctorId, ':patient_id' => $patientId]);
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Patient assigned successfully']);
    exit;
}

// Invalid action
http_response_code(400);
echo json_encode(['success' => false, 'error' => ['code' => 'INVALID_ACTION', 'message' => 'Invalid action']]);
