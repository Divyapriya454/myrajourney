<?php
declare(strict_types=1);

namespace Src\Models;

use PDO;
use Src\Config\DB;

class ReportModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::conn();
    }

    /**
     * List reports for a patient
     */
    public function listForPatient(int $patientId, int $page = 1, int $limit = 20): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit)); // Sanitize limit
        $offset = ($page - 1) * $limit;

        // Count total
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM reports WHERE patient_id = ?");
        $countStmt->execute([$patientId]);
        $total = (int)$countStmt->fetchColumn();

        // Get data
        $stmt = $this->db->prepare("
            SELECT * FROM reports 
            WHERE patient_id = ? 
            ORDER BY uploaded_at DESC 
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute([$patientId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'total' => $total
        ];
    }

    /**
     * List reports for a doctor (all their patients)
     */
    public function listForDoctor(int $doctorId, int $page = 1, int $limit = 20): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit)); // Sanitize limit
        $offset = ($page - 1) * $limit;

        // Get patient user_ids for this doctor from patients table (assigned_doctor_id)
        // Note: assigned_doctor_id stores user_id, not doctors.id
        $patientStmt = $this->db->prepare("
            SELECT DISTINCT user_id 
            FROM patients 
            WHERE assigned_doctor_id = ?
        ");
        $patientStmt->execute([$doctorId]);
        $patientUserIds = $patientStmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($patientUserIds)) {
            return ['items' => [], 'total' => 0];
        }

        $placeholders = implode(',', array_fill(0, count($patientUserIds), '?'));

        // Count total - reports.patient_id references users.id
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM reports WHERE patient_id IN ($placeholders)");
        $countStmt->execute($patientUserIds);
        $total = (int)$countStmt->fetchColumn();

        // Get data with patient names
        $stmt = $this->db->prepare("
            SELECT r.*, u.name AS patient_name
            FROM reports r
            LEFT JOIN users u ON r.patient_id = u.id
            WHERE r.patient_id IN ($placeholders)
            ORDER BY r.uploaded_at DESC
            LIMIT $limit OFFSET $offset
        ");
        
        $stmt->execute($patientUserIds);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'total' => $total
        ];
    }

    /**
     * Create new report
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO reports (
                patient_id, title, description, file_url, file_name, 
                file_size, mime_type, status, uploaded_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            (int)$data['patient_id'],
            $data['title'],
            $data['description'] ?? null,
            $data['file_url'] ?? null,
            $data['file_name'] ?? null,
            (int)($data['file_size'] ?? 0),
            $data['mime_type'] ?? null,
            $data['status'] ?? 'PENDING'
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Find single report
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM reports WHERE id = ?");
        $stmt->execute([$id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Update report
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE reports SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Delete report
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM reports WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get recent reports for patient overview
     */
    public function getRecentForPatient(int $patientId, int $limit = 5): array
    {
        $limit = max(1, min(100, $limit)); // Sanitize limit
        $stmt = $this->db->prepare("
            SELECT id, title, status, uploaded_at
            FROM reports 
            WHERE patient_id = ? 
            ORDER BY uploaded_at DESC 
            LIMIT $limit
        ");
        $stmt->execute([$patientId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}