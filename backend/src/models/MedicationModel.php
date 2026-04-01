<?php
declare(strict_types=1);

namespace Src\Models;

use PDO;
use Src\Config\DB;

class MedicationModel
{
    private PDO $db;
    public function __construct(){ $this->db = DB::conn(); }

    public function patientMedications(int $patientId, ?int $active, int $page, int $limit): array
    {
        $where = 'WHERE patient_id = :pid';
        $params = [':pid' => $patientId];

        if ($active !== null) {
            $where .= ' AND active = :active';
            $params[':active'] = $active;
        }

        $offset = ($page - 1) * $limit;

        $stmtTotal = $this->db->prepare("SELECT COUNT(*) FROM patient_medications $where");
        foreach ($params as $k => $v) {
            $stmtTotal->bindValue($k, $v);
        }
        $stmtTotal->execute();
        $total = (int)$stmtTotal->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT pm.*, 
                   COALESCE(pm.name_override, m.name, pm.medication_name, 'Assigned Medication') as name,
                   CAST(pm.is_morning AS UNSIGNED) as is_morning,
                   CAST(pm.is_afternoon AS UNSIGNED) as is_afternoon,
                   CAST(pm.is_night AS UNSIGNED) as is_night,
                   COALESCE(pm.instructions, '') as instructions,
                   COALESCE(pm.food_relation, '') as food_relation,
                   u.name as doctor_name,
                   d.specialization as doctor_specialization,
                   d.license_number as doctor_license
            FROM patient_medications pm
            LEFT JOIN medications m ON pm.medication_id = m.id
            LEFT JOIN users u ON pm.doctor_id = u.id
            LEFT JOIN doctors d ON u.id = d.user_id
            $where
            ORDER BY pm.created_at DESC
            LIMIT :lim OFFSET :off
        ");

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['items'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'total'=>$total];
    }

    public function assign(array $d): int
    {
        // Handle name mapping for different schema versions
        $name = trim($d['name_override'] ?? $d['medication_name'] ?? '');
        $medId = isset($d['medication_id']) ? (int)$d['medication_id'] : null;
        
        // If medication_id is provided but name is missing, fetch name from medications table
        if ($medId && empty($name)) {
            $stmt = $this->db->prepare("SELECT name FROM medications WHERE id = :mid");
            $stmt->execute([':mid' => $medId]);
            $medName = $stmt->fetchColumn();
            if ($medName) {
                $name = (string)$medName;
            }
        }

        if (empty($name)) {
            throw new \Exception("Medication name is required");
        }

        $dosage = trim((string)($d['dosage'] ?? ''));
        $frequency = $d['frequency_per_day'] ?? $d['frequency'] ?? null;
        
        // Check for duplicates before inserting - only prevent exact duplicates (same name AND same dosage)
        $checkStmt = $this->db->prepare("
            SELECT id FROM patient_medications 
            WHERE patient_id = :pid 
            AND (name_override = :name OR medication_name = :name2)
            AND dosage = :dosage
            AND active = 1
        ");
        
        $checkStmt->execute([
            ':pid' => (int)$d['patient_id'],
            ':name' => $name,
            ':name2' => $name,
            ':dosage' => $dosage
        ]);
        
        if ($checkStmt->fetch()) {
            $displayDosage = empty($dosage) ? "no dosage specified" : $dosage;
            throw new \Exception("Duplicate medication: '$name' with dosage '$displayDosage' is already assigned to this patient");
        }
        
        // Prepare robust INSERT
        $sql = "INSERT INTO patient_medications 
                (patient_id, medication_id, prescribed_by, doctor_id, name_override, medication_name, dosage, instructions, duration, is_morning, is_afternoon, is_night, food_relation, frequency, frequency_per_day, start_date, end_date, active, is_active, created_at, updated_at) 
                VALUES 
                (:pid, :mid, :pb, :did, :no, :mn, :ds, :ins, :dur, :ism, :isa, :isn, :food, :fr, :fpd, :sd, :ed, 1, 1, NOW(), NOW())";
        
        $stmt = $this->db->prepare($sql);
        
        $doctorId = $d['doctor_id'] ?? null;
        
        // Extract values with proper fallbacks
        $instructions = '';
        if (isset($d['instructions']) && trim($d['instructions']) !== '') {
            $instructions = trim((string)$d['instructions']);
        } elseif (isset($d['description']) && trim($d['description']) !== '') {
            $instructions = trim((string)$d['description']);
        }
        
        $foodRelation = null;
        if (isset($d['food_relation']) && trim($d['food_relation']) !== '') {
            $foodRelation = trim((string)$d['food_relation']);
        } elseif (isset($d['foodRelation']) && trim($d['foodRelation']) !== '') {
            $foodRelation = trim((string)$d['foodRelation']);
        }
        
        // DEBUG: Log what we're about to save
        $logFile = __DIR__ . '/../../public/api_log.txt';
        file_put_contents($logFile, 
            date('[Y-m-d H:i:s] SAVING: ') . 
            "instructions=[$instructions], food=[$foodRelation], morning=[" . (int)($d['is_morning'] ?? 0) . "]" . 
            PHP_EOL, FILE_APPEND);
        
        $stmt->execute([
            ':pid' => (int)$d['patient_id'],
            ':mid' => $medId,
            ':pb'  => $doctorId, 
            ':did' => $doctorId,
            ':no'  => trim($name),
            ':mn'  => trim($name),
            ':ds'  => empty($dosage) ? '' : trim((string)$dosage),
            ':ins' => $instructions,
            ':dur' => isset($d['duration']) ? trim((string)$d['duration']) : '',
            ':ism' => (int)($d['is_morning'] ?? $d['isMorning'] ?? 0),
            ':isa' => (int)($d['is_afternoon'] ?? $d['isAfternoon'] ?? 0),
            ':isn' => (int)($d['is_night'] ?? $d['isNight'] ?? 0),
            ':food' => $foodRelation,
            ':fr'  => $frequency !== null ? trim((string)$frequency) : '',
            ':fpd' => is_numeric($frequency) ? (int)$frequency : null,
            ':sd'  => $d['start_date'] ?? date('Y-m-d'),
            ':ed'  => $d['end_date'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Convert frequency text to numeric value
     */
    private function convertFrequencyToNumeric($frequency): ?int
    {
        if ($frequency === null || $frequency === '') {
            return null;
        }
        
        // If already numeric, return as integer
        if (is_numeric($frequency)) {
            return (int)$frequency;
        }
        
        // Convert text frequencies to numbers
        $freq = strtolower(trim($frequency));
        
        // Check for specific patterns first (more specific to less specific)
        if (strpos($freq, '2x') !== false || strpos($freq, 'twice') !== false || strpos($freq, 'bid') !== false) {
            return 2;
        } elseif (strpos($freq, '3x') !== false || strpos($freq, 'three') !== false || strpos($freq, 'tid') !== false) {
            return 3;
        } elseif (strpos($freq, '4x') !== false || strpos($freq, 'four') !== false || strpos($freq, 'qid') !== false) {
            return 4;
        } elseif (strpos($freq, 'weekly') !== false || strpos($freq, 'week') !== false) {
            return 1; // Once per week (stored as 1 for weekly medications)
        } elseif (strpos($freq, 'monthly') !== false || strpos($freq, 'month') !== false) {
            return 1; // Once per month
        } elseif (strpos($freq, 'bi-weekly') !== false || strpos($freq, 'biweekly') !== false) {
            return 1; // Every two weeks
        } elseif (strpos($freq, 'daily') !== false || $freq === '1') {
            return 1;
        } else {
            // Default to 1 if we can't parse
            error_log("Could not parse frequency: " . $frequency . ", defaulting to 1");
            return 1;
        }
    }

    public function updateActive(int $id, int $active): void
    {
        $this->db->prepare("
            UPDATE patient_medications
            SET active = :a, updated_at = NOW()
            WHERE id = :id
        ")->execute([':a'=>$active, ':id'=>$id]);
    }

    public function logIntake(array $d): int
    {
        // medication_logs.medication_id references patient_medications.id
        $pmId = (int)($d['patient_medication_id'] ?? $d['medication_id'] ?? 0);
        if (!$pmId) {
            throw new \Exception("Invalid patient medication ID");
        }

        // Get patient_id from patient_medications
        $stmt = $this->db->prepare("SELECT patient_id FROM patient_medications WHERE id = :pmid");
        $stmt->execute([':pmid' => $pmId]);
        $patientId = $stmt->fetchColumn();

        if (!$patientId) {
            throw new \Exception("Invalid patient medication ID");
        }

        // Map status to ENUM values expected by DB
        $statusMap = ['taken'=>'TAKEN','missed'=>'MISSED','skipped'=>'SKIPPED'];
        $status = $statusMap[strtolower($d['status'] ?? 'taken')] ?? 'TAKEN';

        $stmt = $this->db->prepare("
            INSERT INTO medication_logs
            (patient_id, medication_id, taken_at, status, notes, created_at)
            VALUES (:pid, :mid, :taken_at, :status, :notes, NOW())
        ");

        $stmt->execute([
            ':pid'      => (int)$patientId,
            ':mid'      => $pmId,
            ':taken_at' => $d['taken_at'] ?? date('Y-m-d H:i:s'),
            ':status'   => $status,
            ':notes'    => $d['notes'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getMedicationLogs(int $patientId): array
    {
        $stmt = $this->db->prepare("
            SELECT ml.*,
                   COALESCE(pm.name_override, m.name, pm.medication_name, 'Medication') as medication_name,
                   pm.dosage
            FROM medication_logs ml
            LEFT JOIN patient_medications pm ON ml.medication_id = pm.id
            LEFT JOIN medications m ON pm.medication_id = m.id
            WHERE ml.patient_id = :pid
            ORDER BY ml.taken_at DESC
        ");

        $stmt->execute([':pid' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete(int $id): void
    {
        $this->db->prepare("DELETE FROM patient_medications WHERE id = :id")->execute([':id' => $id]);
    }

    public function clearAll(): void
    {
        $this->db->prepare("DELETE FROM patient_medications")->execute();
    }

    public function getAllPatientMedications(): array
    {
        $stmt = $this->db->prepare("
            SELECT pm.*, 
                   COALESCE(pm.name_override, m.name, pm.medication_name, 'Assigned Medication') as name,
                   u.name as patient_name,
                   d.name as doctor_name
            FROM patient_medications pm
            LEFT JOIN medications m ON pm.medication_id = m.id
            LEFT JOIN users u ON pm.patient_id = u.id
            LEFT JOIN users d ON pm.doctor_id = d.id
            ORDER BY pm.created_at DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
