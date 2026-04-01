<?php
/**
 * Save CRP Reading API Endpoint
 * POST /api/crp/save-reading.php
 */

require_once '../../src/bootstrap.php';

use Src\Config\DB;
use Src\Middlewares\Auth;
use Src\Utils\Response;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::json(['success' => false, 'message' => 'Method not allowed'], 405);
    exit();
}

try {
    // Authenticate user
    $user = Auth::bearer();
    if (!$user) {
        Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
        exit();
    }
    
    // Only patients can save their own CRP readings
    if ($user['role'] !== 'PATIENT') {
        Response::json(['success' => false, 'message' => 'Only patients can save CRP readings'], 403);
        exit();
    }
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::json(['success' => false, 'message' => 'Invalid JSON data'], 400);
        exit();
    }
    
    // Validate required fields
    $required_fields = ['crpValue', 'testDate'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            Response::json(['success' => false, 'message' => "Missing required field: $field"], 400);
            exit();
        }
    }
    
    // Validate CRP value
    $crpValue = floatval($input['crpValue']);
    if ($crpValue < 0 || $crpValue > 500) {
        Response::json(['success' => false, 'message' => 'CRP value must be between 0 and 500 mg/L'], 400);
        exit();
    }
    
    // Validate test date
    $testDate = $input['testDate'];
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $testDate)) {
        // Try to parse different date formats
        $dateObj = DateTime::createFromFormat('M d, Y', $testDate);
        if ($dateObj) {
            $testDate = $dateObj->format('Y-m-d');
        } else {
            Response::json(['success' => false, 'message' => 'Invalid test date format'], 400);
            exit();
        }
    }
    
    // Validate date is not in the future
    if (strtotime($testDate) > time()) {
        Response::json(['success' => false, 'message' => 'Test date cannot be in the future'], 400);
        exit();
    }
    
    $notes = isset($input['notes']) ? trim($input['notes']) : null;
    
    // Connect to database
    $pdo = DB::conn();
    
    // Check if reading already exists for this date
    $checkStmt = $pdo->prepare("
        SELECT id FROM crp_readings 
        WHERE patient_id = ? AND test_date = ?
    ");
    $checkStmt->execute([$user['id'], $testDate]);
    
    if ($checkStmt->fetch()) {
        Response::json(['success' => false, 'message' => 'CRP reading already exists for this date'], 409);
        exit();
    }
    
    // Insert new CRP reading
    $insertStmt = $pdo->prepare("
        INSERT INTO crp_readings (patient_id, crp_value, test_date, notes, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    
    $insertStmt->execute([
        $user['id'],
        $crpValue,
        $testDate,
        $notes
    ]);
    
    $readingId = $pdo->lastInsertId();
    
    // Fetch the created reading
    $fetchStmt = $pdo->prepare("
        SELECT 
            id,
            patient_id,
            crp_value,
            test_date,
            notes,
            created_at,
            updated_at
        FROM crp_readings 
        WHERE id = ?
    ");
    $fetchStmt->execute([$readingId]);
    $reading = $fetchStmt->fetch();
    
    // Calculate trend if there are previous readings
    $trendStmt = $pdo->prepare("
        SELECT crp_value, test_date 
        FROM crp_readings 
        WHERE patient_id = ? AND test_date < ? 
        ORDER BY test_date DESC 
        LIMIT 1
    ");
    $trendStmt->execute([$user['id'], $testDate]);
    $previousReading = $trendStmt->fetch();
    
    $trend = null;
    if ($previousReading) {
        $change = $crpValue - $previousReading['crp_value'];
        if (abs($change) < 0.1) {
            $trend = 'STABLE';
        } elseif ($change > 0) {
            $trend = 'WORSENING';
        } else {
            $trend = 'IMPROVING';
        }
    }
    
    // Add trend information to response
    $reading['trend'] = $trend;
    $reading['previous_value'] = $previousReading ? $previousReading['crp_value'] : null;
    
    // Create notification for doctor if CRP is elevated
    if ($crpValue > 10.0) {
        // Find patient's doctor (if any)
        $doctorStmt = $pdo->prepare("
            SELECT DISTINCT doctor_id 
            FROM patient_medications 
            WHERE patient_id = ? AND doctor_id IS NOT NULL 
            LIMIT 1
        ");
        $doctorStmt->execute([$user['id']]);
        $doctorResult = $doctorStmt->fetch();
        
        if ($doctorResult) {
            $notificationStmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, priority, title, body, created_at)
                VALUES (?, 'CRP_ALERT', 'HIGH', ?, ?, NOW())
            ");
            
            $title = 'High CRP Alert';
            $body = "Patient {$user['name']} has reported a high CRP level of {$crpValue} mg/L on {$testDate}";
            
            $notificationStmt->execute([$doctorResult['doctor_id'], $title, $body]);
        }
    }
    
    Response::json([
        'success' => true,
        'message' => 'CRP reading saved successfully',
        'data' => $reading
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in save-reading.php: " . $e->getMessage());
    Response::json(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log("Error in save-reading.php: " . $e->getMessage());
    Response::json(['success' => false, 'message' => 'An error occurred while saving CRP reading'], 500);
}
?>
