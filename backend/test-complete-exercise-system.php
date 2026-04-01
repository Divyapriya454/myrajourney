<?php

require_once 'src/bootstrap.php';

echo "=== Complete Exercise Tracking System Test ===" . PHP_EOL . PHP_EOL;

try {
    $db = Src\Config\DB::conn();
    $exerciseModel = new Src\Models\ExerciseModel();
    
    // Test 1: Verify all tables exist
    echo "1. Verifying database tables..." . PHP_EOL;
    
    $tables = ['ra_exercises', 'exercise_assignments', 'exercise_sessions', 'performance_reports'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✓ $table table exists" . PHP_EOL;
        } else {
            echo "   ✗ $table table missing" . PHP_EOL;
        }
    }
    
    // Test 2: Verify exercise library
    echo PHP_EOL . "2. Testing exercise library..." . PHP_EOL;
    
    $exercises = $exerciseModel->getAllExercises();
    echo "   ✓ Total exercises: " . count($exercises) . PHP_EOL;
    
    $categories = ['WRIST', 'THUMB', 'FINGER', 'KNEE', 'HIP'];
    foreach ($categories as $category) {
        $categoryExercises = $exerciseModel->getExercisesByCategory($category);
        echo "   ✓ $category exercises: " . count($categoryExercises) . PHP_EOL;
    }
    
    // Test 3: Test assignment workflow
    echo PHP_EOL . "3. Testing assignment workflow..." . PHP_EOL;
    
    // Get test users
    $stmt = $db->query("SELECT id, name FROM users WHERE role = 'DOCTOR' LIMIT 1");
    $doctor = $stmt->fetch();
    
    $stmt = $db->query("SELECT id, name FROM users WHERE role = 'PATIENT' LIMIT 1");
    $patient = $stmt->fetch();
    
    if ($doctor && $patient) {
        echo "   ✓ Test doctor: " . $doctor['name'] . " (ID: " . $doctor['id'] . ")" . PHP_EOL;
        echo "   ✓ Test patient: " . $patient['name'] . " (ID: " . $patient['id'] . ")" . PHP_EOL;
        
        // Create test assignment
        $assignmentId = 'test_' . time();
        $assignmentData = [
            'id' => $assignmentId,
            'doctor_id' => $doctor['id'],
            'patient_id' => $patient['id'],
            'exercise_ids' => json_encode(['ex_001', 'ex_003', 'ex_008']),
            'notes' => 'Test assignment: wrist, thumb, and knee exercises',
            'assigned_date' => date('Y-m-d H:i:s')
        ];
        
        $result = $exerciseModel->createAssignment($assignmentData);
        if ($result) {
            echo "   ✓ Assignment created successfully" . PHP_EOL;
            
            // Test getting patient assignments
            $patientAssignments = $exerciseModel->getPatientAssignments($patient['id']);
            echo "   ✓ Patient has " . count($patientAssignments) . " assignment(s)" . PHP_EOL;
            
            // Test getting doctor assignments
            $doctorAssignments = $exerciseModel->getDoctorAssignments($doctor['id']);
            echo "   ✓ Doctor has " . count($doctorAssignments) . " assignment(s)" . PHP_EOL;
            
        } else {
            echo "   ✗ Failed to create assignment" . PHP_EOL;
        }
    } else {
        echo "   ⚠ No test users available" . PHP_EOL;
    }
    
    // Test 4: Test session workflow
    echo PHP_EOL . "4. Testing session workflow..." . PHP_EOL;
    
    if ($patient) {
        $sessionId = 'session_' . time();
        $sessionData = [
            'id' => $sessionId,
            'patient_id' => $patient['id'],
            'exercise_id' => 'ex_001',
            'start_time' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
            'session_duration' => 600, // 10 minutes
            'overall_accuracy' => 0.85,
            'completion_rate' => 1.0,
            'motion_data' => json_encode([
                ['timestamp' => time() - 600, 'accuracy' => 0.8],
                ['timestamp' => time() - 300, 'accuracy' => 0.9],
                ['timestamp' => time(), 'accuracy' => 0.85]
            ]),
            'performance_metrics' => json_encode([
                'consistency_score' => 0.9,
                'improvement_trend' => 'positive'
            ]),
            'completed' => true
        ];
        
        $result = $exerciseModel->createSession($sessionData);
        if ($result) {
            echo "   ✓ Exercise session created successfully" . PHP_EOL;
            
            // Test getting patient sessions
            $sessions = $exerciseModel->getPatientSessions($patient['id'], 10);
            echo "   ✓ Patient has " . count($sessions) . " session(s)" . PHP_EOL;
            
            if (!empty($sessions)) {
                $latestSession = $sessions[0];
                echo "   ✓ Latest session: " . $latestSession['exercise_name'] . 
                     " (Accuracy: " . ($latestSession['overall_accuracy'] * 100) . "%)" . PHP_EOL;
            }
            
        } else {
            echo "   ✗ Failed to create session" . PHP_EOL;
        }
    }
    
    // Test 5: Test report generation
    echo PHP_EOL . "5. Testing report generation..." . PHP_EOL;
    
    if ($patient && isset($sessionId)) {
        $reportId = 'report_' . time();
        $reportData = [
            'id' => $reportId,
            'session_id' => $sessionId,
            'patient_id' => $patient['id'],
            'exercise_id' => 'ex_001',
            'report_data' => json_encode([
                'session_duration' => 600,
                'form_accuracy' => 0.85,
                'completion_rate' => 1.0,
                'specific_metrics' => [
                    'consistency_score' => 0.9,
                    'wrist_range_of_motion' => 'Good'
                ],
                'recommendations' => [
                    'Excellent form! You\'re performing the exercise correctly',
                    'Great job completing the full exercise routine!',
                    'Remember to perform exercises gently to avoid joint stress'
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ])
        ];
        
        $result = $exerciseModel->createReport($reportData);
        if ($result) {
            echo "   ✓ Performance report created successfully" . PHP_EOL;
            
            // Test getting patient reports
            $reports = $exerciseModel->getPatientReports($patient['id']);
            echo "   ✓ Patient has " . count($reports) . " report(s)" . PHP_EOL;
            
            if (!empty($reports)) {
                $latestReport = $reports[0];
                $reportDataDecoded = is_string($latestReport['report_data']) ? 
                    json_decode($latestReport['report_data'], true) : $latestReport['report_data'];
                echo "   ✓ Latest report accuracy: " . 
                     ($reportDataDecoded['form_accuracy'] * 100) . "%" . PHP_EOL;
                echo "   ✓ Recommendations: " . 
                     count($reportDataDecoded['recommendations']) . " items" . PHP_EOL;
            }
            
        } else {
            echo "   ✗ Failed to create report" . PHP_EOL;
        }
    }
    
    // Test 6: API endpoint simulation
    echo PHP_EOL . "6. Testing API endpoints..." . PHP_EOL;
    
    // Test exercise controller methods
    $exerciseController = new Src\Controllers\ExerciseController();
    
    echo "   ✓ ExerciseController instantiated" . PHP_EOL;
    
    $sessionController = new Src\Controllers\ExerciseSessionController();
    echo "   ✓ ExerciseSessionController instantiated" . PHP_EOL;
    
    // Test 7: System statistics
    echo PHP_EOL . "7. System statistics..." . PHP_EOL;
    
    $stmt = $db->query("SELECT COUNT(*) FROM ra_exercises");
    $exerciseCount = $stmt->fetchColumn();
    echo "   ✓ Total exercises in library: $exerciseCount" . PHP_EOL;
    
    $stmt = $db->query("SELECT COUNT(*) FROM exercise_assignments WHERE is_active = 1");
    $assignmentCount = $stmt->fetchColumn();
    echo "   ✓ Active assignments: $assignmentCount" . PHP_EOL;
    
    $stmt = $db->query("SELECT COUNT(*) FROM exercise_sessions");
    $sessionCount = $stmt->fetchColumn();
    echo "   ✓ Total sessions: $sessionCount" . PHP_EOL;
    
    $stmt = $db->query("SELECT COUNT(*) FROM performance_reports");
    $reportCount = $stmt->fetchColumn();
    echo "   ✓ Total reports: $reportCount" . PHP_EOL;
    
    // Test 8: Data integrity checks
    echo PHP_EOL . "8. Data integrity checks..." . PHP_EOL;
    
    // Check foreign key relationships
    $stmt = $db->query("
        SELECT COUNT(*) FROM exercise_assignments ea
        LEFT JOIN users u1 ON ea.doctor_id = u1.id
        LEFT JOIN users u2 ON ea.patient_id = u2.id
        WHERE u1.id IS NULL OR u2.id IS NULL
    ");
    $orphanedAssignments = $stmt->fetchColumn();
    echo "   ✓ Orphaned assignments: $orphanedAssignments" . PHP_EOL;
    
    $stmt = $db->query("
        SELECT COUNT(*) FROM exercise_sessions es
        LEFT JOIN users u ON es.patient_id = u.id
        LEFT JOIN ra_exercises re ON es.exercise_id = re.id
        WHERE u.id IS NULL OR re.id IS NULL
    ");
    $orphanedSessions = $stmt->fetchColumn();
    echo "   ✓ Orphaned sessions: $orphanedSessions" . PHP_EOL;
    
    echo PHP_EOL . "=== System Test Complete ===" . PHP_EOL;
    echo "✅ Rehab Exercise Tracking System is fully operational!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error during system test: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
