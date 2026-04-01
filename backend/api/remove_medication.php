<?php
require_once '../config/database.php';
require_once '../src/middleware/AuthMiddleware.php';

header('Content-Type: application/json');

// Authenticate user
$auth = AuthMiddleware::authenticate();
if (!$auth['success']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $auth['user_id'];
$userRole = $auth['role'];

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$medicationId = $data['medication_id'] ?? null;
$action = $data['action'] ?? 'remove'; // 'remove' or 'stop'

if (!$medicationId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Medication ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Verify ownership or doctor permission
    $stmt = $db->prepare("
        SELECT pm.*, pm.patient_id, pm.doctor_id 
        FROM patient_medications pm 
        WHERE pm.id = :id
    ");
    $stmt->execute([':id' => $medicationId]);
    $medication = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$medication) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Medication not found']);
        exit;
    }
    
    // Permission check
    $canModify = false;
    if ($userRole === 'doctor' && $medication['doctor_id'] == $userId) {
        $canModify = true;
    } elseif ($userRole === 'patient' && $medication['patient_id'] == $userId) {
        // Patients can usually stop, but maybe not delete assigned meds?
        // Requirement: "Patients can request or mark as stopped"
        $canModify = true;
    }
    
    if (!$canModify) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }
    
    // Update status
    $newStatus = ($action === 'stop') ? 'stopped' : 'removed';
    
    // Note: We flag active=0 for backward compatibility with old app versions
    $stmt = $db->prepare("
        UPDATE patient_medications 
        SET status = :status, 
            removed_at = NOW(), 
            removed_by = :removed_by,
            active = 0
        WHERE id = :id
    ");
    
    $stmt->execute([
        ':status' => $newStatus,
        ':removed_by' => $userId,
        ':id' => $medicationId
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Medication ' . $newStatus . ' successfully',
        'medication_id' => $medicationId,
        'status' => $newStatus
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
