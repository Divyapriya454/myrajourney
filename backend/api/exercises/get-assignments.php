<?php
/**
 * Get Exercise Assignments API Endpoint
 * GET /api/exercises/get-assignments.php?patient_id={id}&active_only={true/false}
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
    $activeOnly = isset($_GET['active_only']) ? filter_var($_GET['active_only'], FILTER_VALIDATE_BOOLEAN) : true;
    
    // Authorization check
    if ($user['role'] === 'PATIENT' && $patientId !== $user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Patients can only view their own exercise assignments']);
        exit();
    }
    
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
    
    // Build query based on active_only parameter
    $whereClause = "pea.patient_id = ?";
    $params = [$patientId];
    
    if ($activeOnly) {
        $whereClause .= " AND pea.is_active = 1";
    }
    
    // Get exercise assignments with exercise details
    $assignmentsStmt = $pdo->prepare("
        SELECT 
            pea.id as assignment_id,
            pea.patient_id,
            pea.exercise_id,
            pea.assigned_date,
            pea.target_reps,
            pea.target_sets,
            pea.target_duration_minutes,
            pea.frequency_per_week,
            pea.difficulty_adjustment,
            pea.special_instructions,
            pea.is_active,
            pea.completed_date,
            pea.created_at as assigned_at,
            
            -- Exercise details
            el.name as exercise_name,
            el.category,
            el.difficulty_level,
            el.description,
            el.instructions,
            el.video_url,
            el.image_url,
            el.duration_minutes as default_duration,
            el.equipment_needed,
            el.muscle_groups,
            
            -- Doctor details
            u.name as assigned_by_doctor,
            
            -- Rehab plan details
            rp.title as rehab_plan_title
            
        FROM patient_exercise_assignments pea
        JOIN exercise_library el ON pea.exercise_id = el.id
        LEFT JOIN users u ON pea.doctor_id = u.id
        LEFT JOIN rehab_plans rp ON pea.rehab_plan_id = rp.id
        WHERE {$whereClause}
        ORDER BY pea.assigned_date DESC, pea.created_at DESC
    ");
    
    $assignmentsStmt->execute($params);
    $assignments = $assignmentsStmt->fetchAll();
    
    // Get recent session data for each assignment
    foreach ($assignments as &$assignment) {
        // Get latest session
        $latestSessionStmt = $pdo->prepare("
            SELECT 
                session_date,
                completion_status,
                completed_reps,
                completed_sets,
                actual_duration_minutes,
                difficulty_rating,
                pain_level_before,
                pain_level_after,
                notes
            FROM exercise_session_logs 
            WHERE assignment_id = ? 
            ORDER BY session_date DESC, created_at DESC 
            LIMIT 1
        ");
        $latestSessionStmt->execute([$assignment['assignment_id']]);
        $latestSession = $latestSessionStmt->fetch();
        
        $assignment['latest_session'] = $latestSession ?: null;
        
        // Get this week's sessions count
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));
        
        $weekSessionsStmt = $pdo->prepare("
            SELECT COUNT(*) as sessions_this_week
            FROM exercise_session_logs 
            WHERE assignment_id = ? 
            AND session_date BETWEEN ? AND ?
            AND completion_status IN ('COMPLETED', 'PARTIAL')
        ");
        $weekSessionsStmt->execute([$assignment['assignment_id'], $weekStart, $weekEnd]);
        $weekSessions = $weekSessionsStmt->fetch();
        
        $assignment['sessions_this_week'] = (int)$weekSessions['sessions_this_week'];
        $assignment['sessions_remaining_this_week'] = max(0, (int)$assignment['frequency_per_week'] - (int)$weekSessions['sessions_this_week']);
        
        // Calculate completion rate for last 4 weeks
        $fourWeeksAgo = date('Y-m-d', strtotime('-4 weeks'));
        
        $completionStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_sessions,
                COUNT(CASE WHEN completion_status IN ('COMPLETED', 'PARTIAL') THEN 1 END) as completed_sessions
            FROM exercise_session_logs 
            WHERE assignment_id = ? 
            AND session_date >= ?
        ");
        $completionStmt->execute([$assignment['assignment_id'], $fourWeeksAgo]);
        $completion = $completionStmt->fetch();
        
        $totalExpected = 4 * (int)$assignment['frequency_per_week']; // 4 weeks * frequency per week
        $actualCompleted = (int)$completion['completed_sessions'];
        $completionRate = $totalExpected > 0 ? round(($actualCompleted / $totalExpected) * 100, 1) : 0;
        
        $assignment['completion_rate_4weeks'] = $completionRate;
        $assignment['total_sessions_4weeks'] = (int)$completion['total_sessions'];
        
        // Parse JSON fields
        if ($assignment['muscle_groups']) {
            $assignment['muscle_groups'] = json_decode($assignment['muscle_groups'], true);
        }
        
        // Format dates
        $assignment['formatted_assigned_date'] = date('M d, Y', strtotime($assignment['assigned_date']));
        if ($assignment['completed_date']) {
            $assignment['formatted_completed_date'] = date('M d, Y', strtotime($assignment['completed_date']));
        }
        
        // Add status indicators
        if (!$assignment['is_active']) {
            $assignment['status'] = 'INACTIVE';
        } elseif ($assignment['completed_date']) {
            $assignment['status'] = 'COMPLETED';
        } elseif ($assignment['sessions_this_week'] >= $assignment['frequency_per_week']) {
            $assignment['status'] = 'WEEK_COMPLETE';
        } elseif ($assignment['sessions_this_week'] > 0) {
            $assignment['status'] = 'IN_PROGRESS';
        } else {
            $assignment['status'] = 'PENDING';
        }
    }
    
    // Calculate summary statistics
    $summary = [
        'total_assignments' => count($assignments),
        'active_assignments' => count(array_filter($assignments, function($a) { return $a['is_active']; })),
        'completed_this_week' => count(array_filter($assignments, function($a) { return $a['status'] === 'WEEK_COMPLETE'; })),
        'in_progress_this_week' => count(array_filter($assignments, function($a) { return $a['status'] === 'IN_PROGRESS'; })),
        'pending_this_week' => count(array_filter($assignments, function($a) { return $a['status'] === 'PENDING' && $a['is_active']; }))
    ];
    
    if (!empty($assignments)) {\n        $activeAssignments = array_filter($assignments, function($a) { return $a['is_active']; });\n        if (!empty($activeAssignments)) {\n            $completionRates = array_map(function($a) { return $a['completion_rate_4weeks']; }, $activeAssignments);\n            $summary['average_completion_rate'] = round(array_sum($completionRates) / count($completionRates), 1);\n        } else {\n            $summary['average_completion_rate'] = 0;\n        }\n    } else {\n        $summary['average_completion_rate'] = 0;\n    }\n    \n    echo json_encode([\n        'success' => true,\n        'message' => 'Exercise assignments retrieved successfully',\n        'data' => $assignments,\n        'summary' => $summary,\n        'patient_info' => [\n            'id' => $patient['id'],\n            'name' => $patient['name']\n        ]\n    ]);\n    \n} catch (PDOException $e) {\n    error_log(\"Database error in get-assignments.php: \" . $e->getMessage());\n    http_response_code(500);\n    echo json_encode(['success' => false, 'message' => 'Database error occurred']);\n} catch (Exception $e) {\n    error_log(\"Error in get-assignments.php: \" . $e->getMessage());\n    http_response_code(500);\n    echo json_encode(['success' => false, 'message' => 'An error occurred while retrieving exercise assignments']);\n}\n?>"
