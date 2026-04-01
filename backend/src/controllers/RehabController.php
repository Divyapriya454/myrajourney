<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Config\DB;
use Src\Utils\Response;
use PDO;

class RehabController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::conn();
    }

    /**
     * GET /api/v1/rehabs
     * Get all available rehab exercises from the master table
     */
    public function listAll(): void
    {
        try {
            $stmt = $this->db->query("SELECT * FROM rehab_exercises ORDER BY rehab_name ASC");
            $exercises = $stmt->fetchAll();
            Response::json(['success' => true, 'data' => $exercises]);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => ['message' => $e->getMessage()]], 500);
        }
    }

    /**
     * POST /api/v1/assign-rehab
     * Doctor assigns a rehab exercise to a patient
     */
    public function assign(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['patient_id'], $input['rehab_id'], $input['doctor_id'])) {
            Response::json(['success' => false, 'error' => ['message' => 'Missing required fields']], 400);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO patient_rehab_assignment 
                (patient_id, rehab_id, assigned_by_doctor_id, sets, reps, status, assigned_date)
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([
                $input['patient_id'],
                $input['rehab_id'],
                $input['doctor_id'],
                $input['sets'] ?? 3,
                $input['reps'] ?? 10
            ]);
            Response::json(['success' => true, 'message' => 'Rehab assigned successfully']);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => ['message' => $e->getMessage()]], 500);
        }
    }

    /**
     * GET /api/v1/patient/{id}/rehabs
     * Get assigned rehab exercises for a specific patient
     */
    public function listForPatient(int $patientId): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT pra.*, re.rehab_name, re.description, re.benefits, re.category, re.video_url
                FROM patient_rehab_assignment pra
                JOIN rehab_exercises re ON pra.rehab_id = re.id
                WHERE pra.patient_id = ?
                ORDER BY pra.assigned_date DESC
            ");
            $stmt->execute([$patientId]);
            $assignments = $stmt->fetchAll();
            Response::json(['success' => true, 'data' => $assignments]);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => ['message' => $e->getMessage()]], 500);
        }
    }

    /**
     * POST /api/v1/rehab-status
     * Update rehab status to completed
     */
    public function updateStatus(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['patient_id'], $input['rehab_id'], $input['status'])) {
            Response::json(['success' => false, 'error' => ['message' => 'Missing required fields']], 400);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE patient_rehab_assignment 
                SET status = ? 
                WHERE patient_id = ? AND rehab_id = ?
            ");
            $stmt->execute([$input['status'], $input['patient_id'], $input['rehab_id']]);
            Response::json(['success' => true, 'message' => 'Status updated successfully']);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => ['message' => $e->getMessage()]], 500);
        }
    }

    /**
     * POST /api/v1/rehab-exercises/{id}/delete
     * Delete a rehab assignment
     */
    public function deleteAssignment(int $assignmentId): void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM patient_rehab_assignment WHERE id = ?");
            $stmt->execute([$assignmentId]);
            
            if ($stmt->rowCount() > 0) {
                Response::json(['success' => true, 'message' => 'Rehab assignment deleted successfully']);
            } else {
                Response::json(['success' => false, 'error' => ['message' => 'Assignment not found']], 404);
            }
        } catch (\Exception $e) {
            Response::json(['success' => false, 'error' => ['message' => $e->getMessage()]], 500);
        }
    }
}
