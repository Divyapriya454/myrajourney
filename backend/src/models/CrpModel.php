<?php
declare(strict_types=1);

namespace Src\Models;

use PDO;
use Src\Config\DB;

class CrpModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::conn();
    }

    /**
     * Get CRP history for a patient
     */
    public function getHistory(int $patientId, int $limit = 50): array
    {
        $sql = "SELECT 
                    id,
                    patient_id,
                    measurement_date,
                    crp_value,
                    measurement_unit,
                    doctor_id,
                    report_id,
                    notes,
                    created_at
                FROM crp_measurements 
                WHERE patient_id = :patient_id 
                ORDER BY measurement_date DESC 
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create new CRP measurement
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO crp_measurements 
                (patient_id, measurement_date, crp_value, measurement_unit, doctor_id, report_id, notes, created_at, updated_at)
                VALUES 
                (:patient_id, :measurement_date, :crp_value, :measurement_unit, :doctor_id, :report_id, :notes, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':patient_id' => (int)$data['patient_id'],
            ':measurement_date' => $data['measurement_date'],
            ':crp_value' => (float)$data['crp_value'],
            ':measurement_unit' => $data['measurement_unit'] ?? 'mg/L',
            ':doctor_id' => isset($data['doctor_id']) ? (int)$data['doctor_id'] : null,
            ':report_id' => isset($data['report_id']) ? (int)$data['report_id'] : null,
            ':notes' => $data['notes'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update CRP measurement
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = NOW()";
        $sql = "UPDATE crp_measurements SET " . implode(', ', $fields) . " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Get latest CRP value for a patient
     */
    public function getLatest(int $patientId): ?array
    {
        $sql = "SELECT * FROM crp_measurements 
                WHERE patient_id = :patient_id 
                ORDER BY measurement_date DESC 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':patient_id' => $patientId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}