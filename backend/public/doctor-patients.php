<?php
// Direct endpoint for doctors to get their assigned patients
// Bypasses OPcache issues

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require __DIR__ . '/../src/bootstrap.php';

use Src\Config\DB;
use Src\Utils\Jwt;

// Get authorization token
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    }
}

if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => ['code' => 'UNAUTHORIZED', 'message' => 'No token provided']]);
    exit;
}

$token = trim($matches[1]);
try {
    $payload = Jwt::decode($token, $_ENV['JWT_SECRET'] ?? '');
    $userId = (int)($payload['uid'] ?? 0);
    $userRole = $payload['role'] ?? '';
} catch (\Throwable $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => ['code' => 'INVALID_TOKEN', 'message' => 'Invalid token']]);
    exit;
}

$db = DB::conn();

// Get patients assigned to this doctor
if ($userRole === 'DOCTOR') {
    $stmt = $db->prepare("SELECT u.id, u.name, u.email, u.phone, u.role, u.created_at, 
        p.dob, p.gender, p.medical_id, p.address, p.assigned_doctor_id
        FROM users u
        LEFT JOIN patients p ON u.id = p.id
        WHERE u.role = 'PATIENT' AND u.status = 'ACTIVE' AND p.assigned_doctor_id = :doctor_id
        ORDER BY u.created_at DESC");
    $stmt->execute([':doctor_id' => $userId]);
    $patients = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $patients,
        'debug' => [
            'doctor_id' => $userId,
            'count' => count($patients),
            'role' => $userRole
        ]
    ]);
} elseif ($userRole === 'ADMIN') {
    // Admins can see all patients
    $stmt = $db->prepare("SELECT u.id, u.name, u.email, u.phone, u.role, u.created_at, 
        p.dob, p.gender, p.medical_id, p.address, p.assigned_doctor_id,
        d.name as doctor_name, doc.specialization
        FROM users u
        LEFT JOIN patients p ON u.id = p.id
        LEFT JOIN users d ON p.assigned_doctor_id = d.id
        LEFT JOIN doctors doc ON u.id = doc.id
        WHERE u.role IN ('PATIENT', 'DOCTOR') AND u.status = 'ACTIVE'
        ORDER BY u.created_at DESC");
    $stmt->execute();
    $patients = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $patients,
        'debug' => [
            'role' => 'ADMIN',
            'count' => count($patients)
        ]
    ]);
} else {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied']]);
}
