<?php
/**
 * Log Exercise Session API Endpoint
 * POST /api/exercises/log-session.php
 */

require_once '../../src/config/db.php';
require_once '../../src/middlewares/auth.php';

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
    
    // Only patients can log their own exercise sessions
    if ($user['role'] !== 'PATIENT') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only patients can log exercise sessions']);
        exit();
    }
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit();
    }
    
    // Validate required fields
    $required_fields = ['assignment_id', 'session_date', 'completion_status'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || (is_string($input[$field]) && empty(trim($input[$field])))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit();
        }
    }
    
    $assignmentId = intval($input['assignment_id']);
    $sessionDate = $input['session_date'];
    $completionStatus = $input['completion_status'];
    
    // Validate completion status
    $validStatuses = ['COMPLETED', 'PARTIAL', 'SKIPPED', 'UNABLE'];
    if (!in_array($completionStatus, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid completion status']);
        exit();
    }
    
    // Validate session date
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sessionDate)) {
        // Try to parse different date formats
        $dateObj = DateTime::createFromFormat('M d, Y', $sessionDate);
        if ($dateObj) {
            $sessionDate = $dateObj->format('Y-m-d');
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid session date format']);
            exit();
        }
    }
    
    // Validate date is not in the future
    if (strtotime($sessionDate) > time()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Session date cannot be in the future']);
        exit();
    }
    
    // Connect to database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Verify assignment belongs to the authenticated patient
    $assignmentStmt = $pdo->prepare("
        SELECT pea.id, pea.patient_id, pea.target_reps, pea.target_sets, pea.target_duration_minutes,\n               el.name as exercise_name\n        FROM patient_exercise_assignments pea\n        JOIN exercise_library el ON pea.exercise_id = el.id\n        WHERE pea.id = ? AND pea.patient_id = ? AND pea.is_active = 1\n    \");\n    $assignmentStmt->execute([$assignmentId, $user['id']]);\n    $assignment = $assignmentStmt->fetch();\n    \n    if (!$assignment) {\n        http_response_code(404);\n        echo json_encode(['success' => false, 'message' => 'Exercise assignment not found or not accessible']);\n        exit();\n    }\n    \n    // Check if session already exists for this date\n    $existingStmt = $pdo->prepare(\"\n        SELECT id FROM exercise_session_logs \n        WHERE assignment_id = ? AND session_date = ?\n    \");\n    $existingStmt->execute([$assignmentId, $sessionDate]);\n    \n    if ($existingStmt->fetch()) {\n        http_response_code(409);\n        echo json_encode(['success' => false, 'message' => 'Exercise session already logged for this date']);\n        exit();\n    }\n    \n    // Prepare session data\n    $sessionData = [\n        'assignment_id' => $assignmentId,\n        'patient_id' => $user['id'],\n        'session_date' => $sessionDate,\n        'completion_status' => $completionStatus,\n        'start_time' => isset($input['start_time']) ? $input['start_time'] : null,\n        'end_time' => isset($input['end_time']) ? $input['end_time'] : null,\n        'completed_reps' => isset($input['completed_reps']) ? max(0, intval($input['completed_reps'])) : null,\n        'completed_sets' => isset($input['completed_sets']) ? max(0, intval($input['completed_sets'])) : null,\n        'actual_duration_minutes' => isset($input['actual_duration_minutes']) ? max(0, intval($input['actual_duration_minutes'])) : null,\n        'difficulty_rating' => isset($input['difficulty_rating']) ? max(1, min(5, intval($input['difficulty_rating']))) : null,\n        'pain_level_before' => isset($input['pain_level_before']) ? max(0, min(10, intval($input['pain_level_before']))) : null,\n        'pain_level_after' => isset($input['pain_level_after']) ? max(0, min(10, intval($input['pain_level_after']))) : null,\n        'energy_level' => isset($input['energy_level']) ? max(1, min(5, intval($input['energy_level']))) : null,\n        'notes' => isset($input['notes']) ? trim($input['notes']) : null\n    ];\n    \n    // Insert session log\n    $insertStmt = $pdo->prepare(\"\n        INSERT INTO exercise_session_logs \n        (patient_id, assignment_id, session_date, start_time, end_time, completed_reps, \n         completed_sets, actual_duration_minutes, difficulty_rating, pain_level_before, \n         pain_level_after, energy_level, completion_status, notes, created_at)\n        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())\n    \");\n    \n    $insertStmt->execute([\n        $sessionData['patient_id'],\n        $sessionData['assignment_id'],\n        $sessionData['session_date'],\n        $sessionData['start_time'],\n        $sessionData['end_time'],\n        $sessionData['completed_reps'],\n        $sessionData['completed_sets'],\n        $sessionData['actual_duration_minutes'],\n        $sessionData['difficulty_rating'],\n        $sessionData['pain_level_before'],\n        $sessionData['pain_level_after'],\n        $sessionData['energy_level'],\n        $sessionData['completion_status'],\n        $sessionData['notes']\n    ]);\n    \n    $sessionId = $pdo->lastInsertId();\n    \n    // Fetch the created session\n    $fetchStmt = $pdo->prepare(\"\n        SELECT \n            esl.*,\n            el.name as exercise_name,\n            el.category as exercise_category\n        FROM exercise_session_logs esl\n        JOIN patient_exercise_assignments pea ON esl.assignment_id = pea.id\n        JOIN exercise_library el ON pea.exercise_id = el.id\n        WHERE esl.id = ?\n    \");\n    $fetchStmt->execute([$sessionId]);\n    $session = $fetchStmt->fetch();\n    \n    // Calculate pain reduction if both before and after are provided\n    if ($session['pain_level_before'] !== null && $session['pain_level_after'] !== null) {\n        $session['pain_reduction'] = $session['pain_level_before'] - $session['pain_level_after'];\n    } else {\n        $session['pain_reduction'] = null;\n    }\n    \n    // Add performance indicators\n    $session['performance_indicators'] = [];\n    \n    if ($session['completed_reps'] && $assignment['target_reps']) {\n        $repsPercentage = round(($session['completed_reps'] / $assignment['target_reps']) * 100, 1);\n        $session['performance_indicators']['reps_percentage'] = $repsPercentage;\n    }\n    \n    if ($session['completed_sets'] && $assignment['target_sets']) {\n        $setsPercentage = round(($session['completed_sets'] / $assignment['target_sets']) * 100, 1);\n        $session['performance_indicators']['sets_percentage'] = $setsPercentage;\n    }\n    \n    if ($session['actual_duration_minutes'] && $assignment['target_duration_minutes']) {\n        $durationPercentage = round(($session['actual_duration_minutes'] / $assignment['target_duration_minutes']) * 100, 1);\n        $session['performance_indicators']['duration_percentage'] = $durationPercentage;\n    }\n    \n    // Create notification for successful completion\n    if ($completionStatus === 'COMPLETED') {\n        $notificationStmt = $pdo->prepare(\"\n            INSERT INTO notifications (user_id, type, priority, title, body, created_at)\n            VALUES (?, 'EXERCISE', 'NORMAL', ?, ?, NOW())\n        \");\n        \n        $title = 'Exercise Completed! 🎉';\n        $body = \"Great job completing {$assignment['exercise_name']}! Keep up the excellent work.\";\n        \n        $notificationStmt->execute([$user['id'], $title, $body]);\n    }\n    \n    // Update weekly progress (this could be moved to a background job)\n    updateWeeklyProgress($pdo, $user['id'], $assignmentId, $sessionDate);\n    \n    echo json_encode([\n        'success' => true,\n        'message' => 'Exercise session logged successfully',\n        'data' => $session\n    ]);\n    \n} catch (PDOException $e) {\n    error_log(\"Database error in log-session.php: \" . $e->getMessage());\n    http_response_code(500);\n    echo json_encode(['success' => false, 'message' => 'Database error occurred']);\n} catch (Exception $e) {\n    error_log(\"Error in log-session.php: \" . $e->getMessage());\n    http_response_code(500);\n    echo json_encode(['success' => false, 'message' => 'An error occurred while logging exercise session']);\n}\n\n/**\n * Update weekly progress for the exercise assignment\n */\nfunction updateWeeklyProgress($pdo, $patientId, $assignmentId, $sessionDate) {\n    try {\n        // Calculate week start and end dates\n        $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($sessionDate)));\n        $weekEnd = date('Y-m-d', strtotime('sunday this week', strtotime($sessionDate)));\n        \n        // Get assignment frequency\n        $freqStmt = $pdo->prepare(\"SELECT frequency_per_week FROM patient_exercise_assignments WHERE id = ?\");\n        $freqStmt->execute([$assignmentId]);\n        $assignment = $freqStmt->fetch();\n        \n        if (!$assignment) return;\n        \n        $plannedSessions = $assignment['frequency_per_week'];\n        \n        // Count completed sessions for this week\n        $sessionStmt = $pdo->prepare(\"\n            SELECT \n                COUNT(*) as sessions_completed,\n                AVG(difficulty_rating) as avg_difficulty_rating,\n                AVG(CASE WHEN pain_level_before IS NOT NULL AND pain_level_after IS NOT NULL \n                         THEN pain_level_before - pain_level_after END) as avg_pain_reduction,\n                AVG(actual_duration_minutes) as avg_duration_minutes\n            FROM exercise_session_logs \n            WHERE assignment_id = ? \n            AND session_date BETWEEN ? AND ?\n            AND completion_status IN ('COMPLETED', 'PARTIAL')\n        \");\n        $sessionStmt->execute([$assignmentId, $weekStart, $weekEnd]);\n        $stats = $sessionStmt->fetch();\n        \n        $completedSessions = (int)$stats['sessions_completed'];\n        $completionRate = $plannedSessions > 0 ? round(($completedSessions / $plannedSessions) * 100, 2) : 0;\n        \n        // Determine progress trend\n        $progressTrend = null;\n        if ($completionRate >= 80) {\n            $progressTrend = 'IMPROVING';\n        } elseif ($completionRate >= 50) {\n            $progressTrend = 'STABLE';\n        } else {\n            $progressTrend = 'DECLINING';\n        }\n        \n        $needsAdjustment = $completionRate < 50 || ($stats['avg_difficulty_rating'] && $stats['avg_difficulty_rating'] > 4);\n        \n        // Insert or update weekly progress\n        $progressStmt = $pdo->prepare(\"\n            INSERT INTO exercise_progress \n            (patient_id, assignment_id, week_start_date, week_end_date, sessions_completed, \n             sessions_planned, completion_rate, avg_difficulty_rating, avg_pain_reduction, \n             avg_duration_minutes, progress_trend, needs_adjustment, calculated_at)\n            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())\n            ON DUPLICATE KEY UPDATE\n            sessions_completed = VALUES(sessions_completed),\n            completion_rate = VALUES(completion_rate),\n            avg_difficulty_rating = VALUES(avg_difficulty_rating),\n            avg_pain_reduction = VALUES(avg_pain_reduction),\n            avg_duration_minutes = VALUES(avg_duration_minutes),\n            progress_trend = VALUES(progress_trend),\n            needs_adjustment = VALUES(needs_adjustment),\n            calculated_at = NOW()\n        \");\n        \n        $progressStmt->execute([\n            $patientId,\n            $assignmentId,\n            $weekStart,\n            $weekEnd,\n            $completedSessions,\n            $plannedSessions,\n            $completionRate,\n            $stats['avg_difficulty_rating'],\n            $stats['avg_pain_reduction'],\n            $stats['avg_duration_minutes'],\n            $progressTrend,\n            $needsAdjustment\n        ]);\n        \n    } catch (Exception $e) {\n        error_log(\"Error updating weekly progress: \" . $e->getMessage());\n        // Don't throw - this is a background operation\n    }\n}\n?>"
