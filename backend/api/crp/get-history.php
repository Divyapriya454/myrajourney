<?php
/**
 * Get CRP History API Endpoint
 * GET /api/crp/get-history.php?patient_id={id}&limit={limit}
 */

require_once '../../src/config/db.php';
require_once '../../src/middlewares/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Authenticate user
    $user = authenticate();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // Get patient ID from query parameter or use authenticated user's ID
    $patientId = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : $user['id'];
    
    // Authorization check
    if ($user['role'] === 'PATIENT' && $patientId !== $user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Patients can only view their own CRP history']);
        exit();
    }
    
    // Doctors and admins can view any patient's history
    if ($user['role'] === 'DOCTOR' || $user['role'] === 'ADMIN' || $patientId === $user['id']) {
        // Authorized to proceed
    } else {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
    
    // Get limit parameter (default 50, max 100)
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 100) : 50;
    if ($limit <= 0) $limit = 50;
    
    // Connect to database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Verify patient exists
    $patientStmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'PATIENT'");
    $patientStmt->execute([$patientId]);
    $patient = $patientStmt->fetch();
    
    if (!$patient) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
        exit();
    }
    
    // Get CRP readings history
    $historyStmt = $pdo->prepare("
        SELECT 
            id,
            patient_id,
            crp_value,
            test_date,
            notes,
            created_at,
            updated_at
        FROM crp_readings 
        WHERE patient_id = ? 
        ORDER BY test_date DESC, created_at DESC
        LIMIT ?
    ");
    $historyStmt->execute([$patientId, $limit]);
    $readings = $historyStmt->fetchAll();
    
    // Calculate trends for each reading
    for ($i = 0; $i < count($readings); $i++) {
        $current = &$readings[$i];
        
        // Add level description
        $crpValue = floatval($current['crp_value']);
        if ($crpValue < 1.0) {
            $current['level_description'] = 'Low';
            $current['level_color'] = '#4CAF50';
        } elseif ($crpValue < 3.0) {
            $current['level_description'] = 'Normal';
            $current['level_color'] = '#4CAF50';
        } elseif ($crpValue <= 10.0) {
            $current['level_description'] = 'Elevated';
            $current['level_color'] = '#FF9800';
        } else {
            $current['level_description'] = 'High';
            $current['level_color'] = '#F44336';
        }
        
        // Calculate trend compared to previous reading
        if ($i < count($readings) - 1) {
            $previous = $readings[$i + 1];
            $change = $crpValue - floatval($previous['crp_value']);
            
            if (abs($change) < 0.1) {
                $current['trend'] = 'STABLE';
                $current['trend_icon'] = '→';
                $current['trend_color'] = '#2196F3';
            } elseif ($change > 0) {
                $current['trend'] = 'WORSENING';
                $current['trend_icon'] = '↑';
                $current['trend_color'] = '#F44336';
            } else {
                $current['trend'] = 'IMPROVING';
                $current['trend_icon'] = '↓';
                $current['trend_color'] = '#4CAF50';
            }
            
            $current['change_value'] = round($change, 2);
            $current['previous_value'] = floatval($previous['crp_value']);
        } else {
            $current['trend'] = null;
            $current['trend_icon'] = null;
            $current['trend_color'] = null;
            $current['change_value'] = null;
            $current['previous_value'] = null;
        }
        
        // Format values
        $current['formatted_value'] = number_format($crpValue, 1) . ' mg/L';
        $current['formatted_date'] = date('M d, Y', strtotime($current['test_date']));
    }
    
    // Calculate summary statistics
    $summary = [];
    if (!empty($readings)) {
        $values = array_map(function($r) { return floatval($r['crp_value']); }, $readings);
        
        $summary = [
            'total_readings' => count($readings),
            'latest_value' => $values[0],
            'latest_date' => $readings[0]['test_date'],
            'average_value' => round(array_sum($values) / count($values), 2),
            'min_value' => min($values),
            'max_value' => max($values),
            'normal_readings' => count(array_filter($values, function($v) { return $v < 3.0; })),
            'elevated_readings' => count(array_filter($values, function($v) { return $v >= 3.0 && $v <= 10.0; })),
            'high_readings' => count(array_filter($values, function($v) { return $v > 10.0; }))
        ];
        
        // Overall trend over last 6 months
        $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
        $recentReadings = array_filter($readings, function($r) use ($sixMonthsAgo) {
            return $r['test_date'] >= $sixMonthsAgo;
        });
        
        if (count($recentReadings) >= 2) {
            $recentValues = array_map(function($r) { return floatval($r['crp_value']); }, $recentReadings);
            $firstHalf = array_slice($recentValues, -ceil(count($recentValues)/2));
            $secondHalf = array_slice($recentValues, 0, floor(count($recentValues)/2));
            
            $firstAvg = array_sum($firstHalf) / count($firstHalf);
            $secondAvg = array_sum($secondHalf) / count($secondHalf);
            $overallChange = $secondAvg - $firstAvg;
            
            if (abs($overallChange) < 0.5) {
                $summary['overall_trend'] = 'STABLE';
            } elseif ($overallChange > 0) {
                $summary['overall_trend'] = 'WORSENING';
            } else {
                $summary['overall_trend'] = 'IMPROVING';
            }
            
            $summary['overall_change'] = round($overallChange, 2);
        } else {
            $summary['overall_trend'] = 'INSUFFICIENT_DATA';
            $summary['overall_change'] = null;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'CRP history retrieved successfully',
        'data' => $readings,
        'summary' => $summary,
        'patient_info' => [
            'id' => $patient['id'],
            'name' => $patient['name']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get-history.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in get-history.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while retrieving CRP history']);
}
?>
