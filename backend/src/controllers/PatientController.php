<?php
declare(strict_types=1);

namespace Src\Controllers;
use PDO;


use Src\Utils\Response;
use Src\Config\DB;

class PatientController
{
    public function overviewMe(): void
        {
            $auth = $_SERVER['auth'] ?? [];
            $uid = (int)($auth['uid'] ?? 0);
            $db = DB::conn();

            // 1. Get User Details (Name)
            $userStmt = $db->prepare("SELECT name FROM users WHERE id = :uid");
            $userStmt->execute([':uid' => $uid]);
            $userName = $userStmt->fetchColumn();

            // 2. Next Appointment
            // [IMPORTANT] This query hides appointments that have passed by even 1 second
            $nextAppt = $db->prepare("SELECT * FROM appointments WHERE patient_id = :uid AND CONCAT(appointment_date, ' ', appointment_time) >= NOW() ORDER BY appointment_date ASC, appointment_time ASC LIMIT 1");
            $nextAppt->execute([':uid' => $uid]);
            $nextAppointment = $nextAppt->fetch(PDO::FETCH_ASSOC) ?: null;

            // 3. Recent Reports - Use ReportModel
            $reportModel = new \Src\Models\ReportModel();
            $recentReports = $reportModel->getRecentForPatient($uid, 5);

            // 4. Unread Notifications
            $unread = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND read_at IS NULL");
            $unread->execute([':uid' => $uid]);

            // 5. Get specific metrics for DAS28 and Pain
            // (Fetching the most recent value for each type)
            $painStmt = $db->prepare("SELECT value FROM health_metrics WHERE patient_id = :uid AND metric_type = 'pain_level' ORDER BY recorded_at DESC LIMIT 1");
            $painStmt->execute([':uid' => $uid]);
            $painLevel = (int)$painStmt->fetchColumn();

            $dasStmt = $db->prepare("SELECT value FROM health_metrics WHERE patient_id = :uid AND metric_type = 'das28' ORDER BY recorded_at DESC LIMIT 1");
            $dasStmt->execute([':uid' => $uid]);
            $dasScore = (float)$dasStmt->fetchColumn();

            Response::json([
                'success' => true,
                'data' => [
                    'patient_name' => $userName,          // Added this
                    'next_appointment' => $nextAppointment,
                    'recent_reports' => $recentReports,
                    'unread_notifications' => (int)$unread->fetchColumn(),
                    'pain_level' => $painLevel,           // Added this
                    'das28_score' => $dasScore,           // Added this
                    'latest_metrics' => []                // Placeholder if needed
                ]
            ]);
        }

    // ---------------------------------------------------------
    // List all patients (doctor/admin)
    // ---------------------------------------------------------
    public function listAll(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid  = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        $db = DB::conn();

        // ---------------------------------------------------------
        // DOCTOR: Return ONLY assigned patients
        // ---------------------------------------------------------
        if ($role === 'DOCTOR') {

            $stmt = $db->prepare("
                SELECT
                    u.id, u.name, u.email, u.phone, u.role, u.created_at,
                    p.age, p.gender, p.medical_id, p.address, p.assigned_doctor_id
                FROM users u
                LEFT JOIN patients p ON u.id = p.user_id
                WHERE u.role = 'PATIENT'
                  AND u.status = 'ACTIVE'
                  AND p.assigned_doctor_id = :doctor_id
                ORDER BY u.created_at DESC
            ");

            $stmt->execute([':doctor_id' => $uid]);

            Response::json([
                'success' => true,
                'data'    => $stmt->fetchAll()
            ]);

            return;
        }

        // ---------------------------------------------------------
        // ADMIN: Return ALL PATIENTS (NOT doctors)
        // ---------------------------------------------------------
        if ($role === 'ADMIN') {

            $stmt = $db->prepare("
                SELECT
                    u.id, u.name, u.email, u.phone, u.role, u.created_at,
                    p.age, p.gender, p.medical_id, p.address, p.assigned_doctor_id,
                    d.name AS doctor_name, doc.specialization
                FROM users u
                LEFT JOIN patients p ON u.id = p.user_id
                LEFT JOIN users d ON p.assigned_doctor_id = d.id
                LEFT JOIN doctors doc ON d.id = doc.user_id
                WHERE u.role = 'PATIENT'
                  AND u.status = 'ACTIVE'
                ORDER BY u.created_at DESC
            ");

            $stmt->execute();

            Response::json([
                'success' => true,
                'data'    => $stmt->fetchAll()
            ]);

            return;
        }

        // ---------------------------------------------------------
        // Unauthorized
        // ---------------------------------------------------------
        Response::json([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => 'Access denied'
            ]
        ], 403);
    }

    // ---------------------------------------------------------
    // Get single patient
    // ---------------------------------------------------------
    public function get(int $id): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid  = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        // Patients can only view their own profile
        if ($role === 'PATIENT' && $uid !== $id) {
            Response::json(['success' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied']], 403);
            return;
        }

        $db = DB::conn();
        $stmt = $db->prepare("
            SELECT u.*, p.* 
            FROM users u
            LEFT JOIN patients p ON u.id = p.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            Response::json(['success' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Patient not found']], 404);
            return;
        }

        Response::json(['success' => true, 'data' => $patient]);
    }

    // ---------------------------------------------------------
    // Update patient profile
    // ---------------------------------------------------------
    public function update(int $id): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid  = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        // Patients can only update their own profile
        if ($role === 'PATIENT' && $uid !== $id) {
            Response::json(['success' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied']], 403);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $db = DB::conn();

        // Check if patient record exists
        $checkStmt = $db->prepare("SELECT id FROM patients WHERE user_id = ?");
        $checkStmt->execute([$id]);
        $patientExists = $checkStmt->fetch();

        $allowedFields = ['medical_history', 'allergies', 'current_medications', 'emergency_contact', 'blood_group', 'height', 'weight'];
        $updates = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($body[$field])) {
                $updates[] = "$field = ?";
                $params[] = $body[$field];
            }
        }

        if (empty($updates)) {
            Response::json(['success' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'No valid fields to update']], 422);
            return;
        }

        $params[] = $id;

        if ($patientExists) {
            // Update existing record
            $sql = "UPDATE patients SET " . implode(', ', $updates) . " WHERE user_id = ?";
        } else {
            // Insert new record
            $sql = "INSERT INTO patients (user_id, " . implode(', ', array_map(function($u) { return explode(' = ', $u)[0]; }, $updates)) . ") VALUES (?, " . implode(', ', array_fill(0, count($updates), '?')) . ")";
            array_unshift($params, $id);
        }

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            Response::json(['success' => true, 'message' => 'Profile updated successfully']);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => ['code' => 'SERVER_ERROR', 'message' => 'Failed to update profile']], 500);
        }
    }
}
