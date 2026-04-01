<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Models\ReportModel;
use Src\Models\NotificationModel;
use Src\Utils\Response;
use Src\Utils\Upload;

class ReportController
{
    private ReportModel $reports;

    public function __construct()
    {
        $this->reports = new ReportModel();
    }

    /**
     * List reports
     */
    public function list(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));

        if ($role === 'PATIENT') {
            $result = $this->reports->listForPatient($uid, $page, $limit);
        } elseif ($role === 'DOCTOR') {
            $result = $this->reports->listForDoctor($uid, $page, $limit);
        } else {
            // Admin - can see all reports
            $result = ['items' => [], 'total' => 0]; // Implement if needed
        }

        // Fix URLs for frontend
        foreach ($result['items'] as &$item) {
            if (!empty($item['file_url'])) {
                $item['file_url'] = $this->makeFullUrl($item['file_url']);
            }
        }

        Response::json([
            'success' => true,
            'data' => $result['items'],
            'meta' => [
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * Create new report
     */
    public function create(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        // Only patients can create reports
        if ($role !== 'PATIENT') {
            Response::json(['success' => false, 'message' => 'Access denied'], 403);
            return;
        }

        $patientId = $uid; // Use authenticated user's ID
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$title) {
            Response::json(['success' => false, 'message' => 'Title is required'], 422);
            return;
        }

        $fileInfo = null;
        if (!empty($_FILES['file'])) {
            try {
                $fileInfo = Upload::saveReport($_FILES['file']);
            } catch (\Exception $e) {
                Response::json(['success' => false, 'message' => 'File upload failed: ' . $e->getMessage()], 400);
                return;
            }
        }

        try {
            $reportData = [
                'patient_id' => $patientId,
                'title' => $title,
                'description' => $description,
                'status' => 'PENDING'
            ];

            if ($fileInfo) {
                $reportData['file_url'] = $fileInfo['file_url'];
                $reportData['file_name'] = $_FILES['file']['name'] ?? null;
                $reportData['file_size'] = $fileInfo['size_bytes'];
                $reportData['mime_type'] = $fileInfo['mime_type'];
            }

            $reportId = $this->reports->create($reportData);

            // Notify doctors
            $this->notifyDoctors($patientId, $title);

            $report = $this->reports->find($reportId);
            if ($report && !empty($report['file_url'])) {
                $report['file_url'] = $this->makeFullUrl($report['file_url']);
            }

            Response::json([
                'success' => true,
                'data' => $report,
                'message' => 'Report uploaded successfully'
            ], 201);

        } catch (\Exception $e) {
            Response::json(['success' => false, 'message' => 'Failed to create report: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get single report
     */
    public function get(int $id): void
    {
        $report = $this->reports->find($id);

        if (!$report) {
            Response::json(['success' => false, 'message' => 'Report not found'], 404);
            return;
        }

        // Fix URL
        if (!empty($report['file_url'])) {
            $report['file_url'] = $this->makeFullUrl($report['file_url']);
        }

        Response::json(['success' => true, 'data' => $report]);
    }

    /**
     * Update report status (doctors only)
     */
    public function updateStatus(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        if ($role !== 'DOCTOR') {
            Response::json(['success' => false, 'message' => 'Access denied'], 403);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $reportId = (int)($input['report_id'] ?? 0);
        $status = trim($input['status'] ?? '');

        if (!$reportId || !$status) {
            Response::json(['success' => false, 'message' => 'Missing required fields'], 422);
            return;
        }

        $validStatuses = ['PENDING', 'REVIEWED', 'NORMAL', 'ABNORMAL', 'ARCHIVED'];
        if (!in_array($status, $validStatuses)) {
            Response::json(['success' => false, 'message' => 'Invalid status'], 422);
            return;
        }

        $updateData = [
            'status' => $status,
            'reviewed_by' => $uid,
            'reviewed_at' => date('Y-m-d H:i:s')
        ];

        $success = $this->reports->update($reportId, $updateData);

        if ($success) {
            Response::json(['success' => true, 'message' => 'Report status updated']);
        } else {
            Response::json(['success' => false, 'message' => 'Failed to update status'], 500);
        }
    }

    /**
     * Notify doctors about new report
     */
    private function notifyDoctors(int $patientId, string $reportTitle): void
    {
        try {
            $notif = new NotificationModel();
            $db = \Src\Config\DB::conn();
            
            // Get assigned doctor for this patient from patients table
            // Note: assigned_doctor_id stores user_id, not doctors.id
            $stmt = $db->prepare('SELECT assigned_doctor_id FROM patients WHERE user_id = ?');
            $stmt->execute([$patientId]);
            $assignedDoctorId = $stmt->fetchColumn();
            
            if (!$assignedDoctorId) {
                error_log("No assigned doctor found for patient ID: $patientId");
                return;
            }
            
            // Get patient name
            $stmtPatient = $db->prepare('SELECT name FROM users WHERE id = ?');
            $stmtPatient->execute([$patientId]);
            $patientName = $stmtPatient->fetchColumn() ?: 'Patient';
            
            // Create notification for the assigned doctor
            $notif->create(
                (int)$assignedDoctorId,
                'PATIENT_REPORT',
                'New report uploaded',
                "$patientName uploaded a new report: $reportTitle"
            );
        } catch (\Exception $e) {
            error_log("Failed to notify doctors: " . $e->getMessage());
        }
    }

    /**
     * Make full URL for file
     */
    private function makeFullUrl(string $path): string
    {
        if (strpos($path, 'http') === 0) {
            return $path; // Already full URL
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        return "$scheme://$host$path";
    }

    /**
     * Delete report
     */
    public function delete(int $id): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        // Get report to check ownership
        $report = $this->reports->find($id);

        if (!$report) {
            Response::json(['success' => false, 'message' => 'Report not found'], 404);
            return;
        }

        // Only the patient who uploaded or a doctor can delete
        if ($role === 'PATIENT' && $report['patient_id'] != $uid) {
            Response::json(['success' => false, 'message' => 'Access denied'], 403);
            return;
        }

        try {
            $db = \Src\Config\DB::conn();
            
            // Delete associated lab values
            $stmt = $db->prepare("DELETE FROM lab_values WHERE report_id = ?");
            $stmt->execute([$id]);
            
            // Delete OCR processing logs
            $stmt = $db->prepare("DELETE FROM ocr_processing_logs WHERE report_id = ?");
            $stmt->execute([$id]);
            
            // Delete report notes
            $stmt = $db->prepare("DELETE FROM report_notes WHERE report_id = ?");
            $stmt->execute([$id]);
            
            // Delete the file if it exists
            if (!empty($report['file_url'])) {
                $relativePath = ltrim($report['file_url'], '/');
                $filePath = __DIR__ . '/../../public/' . $relativePath;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            
            // Delete the report
            $success = $this->reports->delete($id);

            if ($success) {
                Response::json(['success' => true, 'message' => 'Report deleted successfully']);
            } else {
                Response::json(['success' => false, 'message' => 'Failed to delete report'], 500);
            }
        } catch (\Exception $e) {
            Response::json(['success' => false, 'message' => 'Failed to delete report: ' . $e->getMessage()], 500);
        }
    }
}