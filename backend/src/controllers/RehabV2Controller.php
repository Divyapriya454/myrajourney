<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Config\DB;
use Src\Utils\Response;
use PDO;

class RehabV2Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::conn();
    }

    /**
     * Doctor assigns an exercise to a patient
     */
    public function assign(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (empty($body['patient_id']) || empty($body['exercise_name'])) {
            Response::json(['success' => false, 'message' => 'Missing patient_id or exercise_name'], 422);
            return;
        }

        $auth = $_SERVER['auth'] ?? [];
        $doctorId = (int)($body['doctor_id'] ?? $auth['uid'] ?? 0);

        if ($doctorId === 0) {
            Response::json(['success' => false, 'message' => 'Unauthorized or missing doctor_id'], 401);
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO rehab_assignments_v2 
            (patient_id, doctor_id, exercise_name, description, sets, reps, video_url) 
            VALUES (:pid, :did, :ename, :desc, :sets, :reps, :vurl)
        ");

        $success = $stmt->execute([
            ':pid'   => (int)$body['patient_id'],
            ':did'   => $doctorId,
            ':ename' => $body['exercise_name'],
            ':desc'  => $body['description'] ?? null,
            ':sets'  => (int)($body['sets'] ?? 3),
            ':reps'  => (int)($body['reps'] ?? 10),
            ':vurl'  => $body['video_url'] ?? null
        ]);

        if ($success) {
            Response::json(['success' => true, 'message' => 'Exercise assigned successfully', 'id' => $this->db->lastInsertId()]);
        } else {
            Response::json(['success' => false, 'message' => 'Failed to assign exercise'], 500);
        }
    }

    /**
     * List exercises for a patient
     */
    public function listForPatient(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        if ($role === 'PATIENT') {
            $pid = $uid;
        } else {
            $pid = (int)($_GET['patient_id'] ?? 0);
        }

        if ($pid === 0) {
            Response::json(['success' => false, 'message' => 'patient_id required'], 422);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT
                    pra.id,
                    pra.patient_id,
                    pra.rehab_id,
                    pra.assigned_by_doctor_id,
                    pra.sets,
                    pra.reps,
                    pra.status,
                    pra.assigned_date,
                    pra.updated_at,
                    COALESCE(re.rehab_name, re.name, CONCAT('Exercise #', pra.rehab_id)) AS rehab_name,
                    COALESCE(re.description, '') AS description,
                    COALESCE(re.benefits, '') AS benefits,
                    COALESCE(re.category, '') AS category,
                    COALESCE(re.video_url, '') AS video_url
                FROM patient_rehab_assignment pra
                LEFT JOIN rehab_exercises re ON re.id = pra.rehab_id
                WHERE pra.patient_id = :pid
                ORDER BY assigned_date DESC
            ");
            $stmt->execute([':pid' => $pid]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Response::json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            Response::json(['success' => true, 'data' => []]);
        }
    }

    /**
     * Mark an exercise as completed
     */
    public function complete(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE rehab_assignments_v2 
            SET status = 'COMPLETED', completed_at = NOW() 
            WHERE id = :id
        ");
        $success = $stmt->execute([':id' => $id]);

        if ($success) {
            Response::json(['success' => true, 'message' => 'Exercise updated']);
        } else {
            Response::json(['success' => false, 'message' => 'Failed to update'], 500);
        }
    }
}
