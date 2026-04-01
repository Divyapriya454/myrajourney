<?php

namespace Src\Controllers;

use Src\Models\ExerciseModel;
use Src\Utils\Response;
use Src\Middlewares\Auth;

class ExerciseSessionController
{
    private $exerciseModel;

    public function __construct()
    {
        $this->exerciseModel = new ExerciseModel();
    }

    /**
     * Create exercise session
     * POST /api/exercise-sessions
     */
    public function createSession()
    {
        try {
            $user = Auth::requireAuth();
            
            // Only patients can create sessions
            if ($user['role'] !== 'PATIENT') {
                Response::json([
                    'success' => false,
                    'message' => 'Only patients can create exercise sessions'
                ], 403);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (!isset($data['exercise_id']) || !isset($data['start_time'])) {
                Response::json([
                    'success' => false,
                    'message' => 'Missing required fields: exercise_id, start_time'
                ], 400);
                return;
            }
            
            $sessionData = [
                'id' => $data['id'] ?? uniqid('session_', true),
                'patient_id' => $user['id'],
                'exercise_id' => $data['exercise_id'],
                'start_time' => $data['start_time'],
                'session_duration' => $data['session_duration'] ?? null,
                'overall_accuracy' => $data['overall_accuracy'] ?? null,
                'completion_rate' => $data['completion_rate'] ?? null,
                'motion_data' => isset($data['motion_data']) ? json_encode($data['motion_data']) : null,
                'performance_metrics' => isset($data['performance_metrics']) ? json_encode($data['performance_metrics']) : null,
                'completed' => $data['completed'] ?? false
            ];
            
            $result = $this->exerciseModel->createSession($sessionData);
            
            if ($result) {
                // Generate performance report if session is completed
                if ($sessionData['completed']) {
                    $this->generatePerformanceReport($sessionData);
                }
                
                Response::json([
                    'success' => true,
                    'data' => ['session_id' => $sessionData['id']],
                    'message' => 'Exercise session created successfully'
                ], 201);
            } else {
                Response::json([
                    'success' => false,
                    'message' => 'Failed to create session'
                ], 500);
            }
            
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Failed to create session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patient sessions
     * GET /api/exercise-sessions/patient/{patientId}
     */
    public function getPatientSessions($patientId)
    {
        try {
            $user = Auth::requireAuth();
            
            // Patients can only view their own sessions, doctors can view any patient's sessions
            if ($user['role'] === 'PATIENT' && $patientId != $user['id']) {
                Response::json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
                return;
            }
            
            $limit = $_GET['limit'] ?? 50;
            $sessions = $this->exerciseModel->getPatientSessions($patientId, $limit);
            
            Response::json([
                'success' => true,
                'data' => $sessions,
                'message' => 'Sessions retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Failed to retrieve sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate performance report
     * POST /api/exercise-sessions/{sessionId}/report
     */
    public function generateReport($sessionId)
    {
        try {
            $user = Auth::requireAuth();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $reportData = [
                'session_duration' => $data['session_duration'] ?? 0,
                'form_accuracy' => $data['form_accuracy'] ?? 0,
                'completion_rate' => $data['completion_rate'] ?? 0,
                'specific_metrics' => $data['specific_metrics'] ?? [],
                'recommendations' => $this->generateRecommendations($data),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $report = [
                'id' => uniqid('report_', true),
                'session_id' => $sessionId,
                'patient_id' => $user['id'],
                'exercise_id' => $data['exercise_id'],
                'report_data' => json_encode($reportData)
            ];
            
            $result = $this->exerciseModel->createReport($report);
            
            if ($result) {
                Response::json([
                    'success' => true,
                    'data' => ['report_id' => $report['id'], 'report_data' => $reportData],
                    'message' => 'Performance report generated successfully'
                ], 201);
            } else {
                Response::json([
                    'success' => false,
                    'message' => 'Failed to generate report'
                ], 500);
            }
            
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patient reports
     * GET /api/exercise-reports/patient/{patientId}
     */
    public function getPatientReports($patientId)
    {
        try {
            $user = Auth::requireAuth();
            
            // Patients can only view their own reports, doctors can view any patient's reports
            if ($user['role'] === 'PATIENT' && $patientId != $user['id']) {
                Response::json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
                return;
            }
            
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $reports = $this->exerciseModel->getPatientReports($patientId, $startDate, $endDate);
            
            Response::json([
                'success' => true,
                'data' => $reports,
                'message' => 'Reports retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'message' => 'Failed to retrieve reports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate performance report automatically
     */
    private function generatePerformanceReport($sessionData)
    {
        try {
            $motionData = json_decode($sessionData['motion_data'], true) ?? [];
            $performanceMetrics = json_decode($sessionData['performance_metrics'], true) ?? [];
            
            $reportData = [
                'session_duration' => $sessionData['session_duration'],
                'form_accuracy' => $sessionData['overall_accuracy'],
                'completion_rate' => $sessionData['completion_rate'],
                'specific_metrics' => $performanceMetrics,
                'recommendations' => $this->generateRecommendations([
                    'form_accuracy' => $sessionData['overall_accuracy'],
                    'completion_rate' => $sessionData['completion_rate'],
                    'session_duration' => $sessionData['session_duration']
                ]),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $report = [
                'id' => uniqid('report_', true),
                'session_id' => $sessionData['id'],
                'patient_id' => $sessionData['patient_id'],
                'exercise_id' => $sessionData['exercise_id'],
                'report_data' => json_encode($reportData)
            ];
            
            $this->exerciseModel->createReport($report);
            
        } catch (\Exception $e) {
            // Log error but don't fail the session creation
            error_log("Failed to generate automatic report: " . $e->getMessage());
        }
    }

    /**
     * Generate recommendations based on performance data
     */
    private function generateRecommendations($data)
    {
        $recommendations = [];
        
        $accuracy = $data['form_accuracy'] ?? 0;
        $completionRate = $data['completion_rate'] ?? 0;
        $duration = $data['session_duration'] ?? 0;
        
        // Form accuracy recommendations
        if ($accuracy < 0.6) {
            $recommendations[] = "Focus on proper form - consider slowing down your movements";
            $recommendations[] = "Review the exercise instructions and demonstration video";
        } elseif ($accuracy < 0.8) {
            $recommendations[] = "Good progress! Try to maintain consistent form throughout the exercise";
        } else {
            $recommendations[] = "Excellent form! You're performing the exercise correctly";
        }
        
        // Completion rate recommendations
        if ($completionRate < 0.7) {
            $recommendations[] = "Try to complete more repetitions - start slowly and build up";
        } elseif ($completionRate < 0.9) {
            $recommendations[] = "Good effort! Aim to complete all recommended repetitions";
        } else {
            $recommendations[] = "Great job completing the full exercise routine!";
        }
        
        // Duration recommendations
        if ($duration < 300) { // Less than 5 minutes
            $recommendations[] = "Consider extending your exercise session for better results";
        } elseif ($duration > 1800) { // More than 30 minutes
            $recommendations[] = "Great dedication! Make sure not to overexert yourself";
        }
        
        // General RA-specific recommendations
        $recommendations[] = "Remember to perform exercises gently to avoid joint stress";
        $recommendations[] = "If you experience pain, stop and consult your doctor";
        
        return $recommendations;
    }
}
