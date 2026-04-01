<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Utils\Response;
use Src\Middlewares\Auth;
use Src\Config\DB;

class ReportNoteController
{
    /**
     * Create or update report note
     */
    public function create(): void
    {
        Auth::requireAuth();
        $auth = $_SERVER['auth'] ?? [];

        $doctorId = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        // Only doctors can add notes
        if ($role !== 'DOCTOR') {
            Response::json([
                'success' => false,
                'error'   => ['code' => 'FORBIDDEN', 'message' => 'Only doctors can add report notes']
            ], 403);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $reportId   = (int)($body['report_id'] ?? 0);
        $diagnosis  = trim((string)($body['diagnosis_text'] ?? ''));
        $suggestions = trim((string)($body['suggestions_text'] ?? ''));
        $crpValue   = isset($body['crp_value']) ? $body['crp_value'] : null;

        if (!$reportId) {
            Response::json([
                'success' => false,
                'error'   => ['code' => 'VALIDATION', 'message' => 'report_id required']
            ], 422);
            return;
        }

        // CRP validation
        if ($crpValue !== null && $crpValue !== '') {
            // Convert to float
            $crpValue = (float)$crpValue;
            
            // Validate range
            if ($crpValue < 0 || $crpValue > 500) {
                Response::json([
                    'success' => false,
                    'error' => ['code' => 'VALIDATION', 'message' => 'CRP value must be between 0 and 500 mg/L']
                ], 422);
                return;
            }
            
            // Validate decimal places (max 2)
            if (round($crpValue, 2) != $crpValue) {
                Response::json([
                    'success' => false,
                    'error' => ['code' => 'VALIDATION', 'message' => 'CRP value can have maximum 2 decimal places']
                ], 422);
                return;
            }
        } else {
            $crpValue = null;
        }

        if ($diagnosis === '' && $suggestions === '') {
            Response::json([
                'success' => false,
                'error'   => ['code' => 'VALIDATION', 'message' => 'diagnosis_text or suggestions_text required']
            ], 422);
            return;
        }

        $db = DB::conn();

        // Check report exists
        $stmt = $db->prepare("SELECT id, patient_id FROM reports WHERE id = :id");
        $stmt->execute([':id' => $reportId]);
        $report = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$report) {
            Response::json([
                'success' => false,
                'error'   => ['code' => 'NOT_FOUND', 'message' => 'Report not found']
            ], 404);
            return;
        }

        // Check if doctor already added a note for this report
        $stmt = $db->prepare("SELECT id FROM report_notes WHERE report_id = :rid AND doctor_id = :did");
        $stmt->execute([':rid' => $reportId, ':did' => $doctorId]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existing) {
            // Update existing note
            $stmt = $db->prepare("
                UPDATE report_notes
                SET diagnosis_text = :diag,
                    suggestions_text = :sugg,
                    crp_value = :crp,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':diag' => $diagnosis !== '' ? $diagnosis : null,
                ':sugg' => $suggestions !== '' ? $suggestions : null,
                ':crp'  => $crpValue,
                ':id'   => (int)$existing['id']
            ]);
            $noteId = (int)$existing['id'];

        } else {

            // Insert new note
            $stmt = $db->prepare("
                INSERT INTO report_notes
                    (report_id, doctor_id, diagnosis_text, suggestions_text, crp_value, created_at, updated_at)
                VALUES
                    (:rid, :did, :diag, :sugg, :crp, NOW(), NOW())
            ");
            $stmt->execute([
                ':rid'  => $reportId,
                ':did'  => $doctorId,
                ':diag' => $diagnosis !== '' ? $diagnosis : null,
                ':sugg' => $suggestions !== '' ? $suggestions : null,
                ':crp'  => $crpValue
            ]);
            $noteId = (int)$db->lastInsertId();
        }

        // Update report status to "Reviewed" ONLY if status is currently NULL or Pending
        try {
            $db->prepare("UPDATE reports SET status = 'Reviewed' WHERE id = :id AND (status IS NULL OR status='Pending')")
               ->execute([':id' => $reportId]);
        } catch (\Throwable $e) { /* ignore */ }

        // Retrieve updated/created note
        $stmt = $db->prepare("
            SELECT rn.*, u.name as doctor_name
            FROM report_notes rn
            LEFT JOIN users u ON rn.doctor_id = u.id
            WHERE rn.id = :id
        ");
        $stmt->execute([':id' => $noteId]);
        $note = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Send notification to patient
        try {
            $notif = new \Src\Models\NotificationModel();
            $notif->create(
                (int)$report['patient_id'],
                'REPORT_NOTE',
                'Doctor added diagnosis',
                'Your report has been reviewed.'
            );
        } catch (\Throwable $e) { /* ignore */ }

        // If CRP value is provided, save it to CRP measurements table for graphing
        if ($crpValue !== null) {
            try {
                $crpModel = new \Src\Models\CrpModel();
                $crpModel->create([
                    'patient_id' => $report['patient_id'],
                    'measurement_date' => date('Y-m-d'),
                    'crp_value' => $crpValue,
                    'measurement_unit' => 'mg/L',
                    'doctor_id' => $doctorId,
                    'report_id' => $reportId,
                    'notes' => 'CRP value from report diagnosis'
                ]);
            } catch (\Exception $e) {
                // Log error but don't fail the diagnosis save
                error_log("Failed to save CRP measurement: " . $e->getMessage());
            }
        }

        Response::json(['success' => true, 'data' => $note], 201);
    }

    /**
     * Get all notes for a report
     */
    public function get(int $reportId): void
    {
        Auth::requireAuth();
        $db = DB::conn();

        $stmt = $db->prepare("
            SELECT rn.*, u.name AS doctor_name
            FROM report_notes rn
            LEFT JOIN users u ON rn.doctor_id = u.id
            WHERE rn.report_id = :rid
            ORDER BY rn.created_at DESC
        ");
        $stmt->execute([':rid' => $reportId]);
        $notes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        Response::json(['success' => true, 'data' => $notes]);
    }
}
