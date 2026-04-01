<?php
declare(strict_types=1);

namespace Src\Models;

use PDO;
use Src\Config\DB;

class AppointmentModel
{
    private PDO $db;

    public function __construct() {
        $this->db = DB::conn();
    }

    /**
     * List appointments with filters
     */
    public function list(array $filters, int $page = 1, int $limit = 20): array
    {
        $w = [];
        $p = [];

        if (!empty($filters['patient_id'])) {
            $w[] = 'a.patient_id = :pid';
            $p[':pid'] = (int)$filters['patient_id'];
        }
        if (!empty($filters['doctor_id'])) {
            $w[] = 'a.doctor_id = :did';
            $p[':did'] = (int)$filters['doctor_id'];
        }
        
        // Filter out past appointments (only show today and future)
        $w[] = 'a.appointment_date >= CURDATE()';

        $where = $w ? ('WHERE ' . implode(' AND ', $w)) : '';

        // Count query
        $countSql  = "SELECT COUNT(*) FROM appointments a $where";
        $stmtCount = $this->db->prepare($countSql);
        foreach ($p as $k => $v) {
            $stmtCount->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmtCount->execute();
        $total = (int)$stmtCount->fetchColumn();

        // Main list query
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT
                a.*,
                pu.name AS patient_name,
                du.name AS doctor_name,

                a.title       AS appointment_type,
                a.description AS reason

            FROM appointments a
            LEFT JOIN users pu ON a.patient_id = pu.id
            LEFT JOIN users du ON a.doctor_id = du.id
            $where
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
            LIMIT :lim OFFSET :off
        ";

        $stmt = $this->db->prepare($sql);

        foreach ($p as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        }

        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total
        ];
    }

    /**
     * Create appointment
     */
    public function create(array $d): int
    {
        // Parse start_time if provided, otherwise use appointment_date and appointment_time
        if (isset($d['start_time'])) {
            $startDateTime = new \DateTime($d['start_time']);
            $appointmentDate = $startDateTime->format('Y-m-d');
            $appointmentTime = $startDateTime->format('H:i:s');
        } else {
            $appointmentDate = $d['appointment_date'] ?? date('Y-m-d');
            $appointmentTime = $d['appointment_time'] ?? date('H:i:s');
        }

        $stmt = $this->db->prepare("
            INSERT INTO appointments
                (patient_id, doctor_id, title, description, location, type, appointment_date, appointment_time, status, created_at, updated_at)
            VALUES
                (:patient_id, :doctor_id, :title, :description, :location, :type, :appointment_date, :appointment_time, 'SCHEDULED', NOW(), NOW())
        ");

        $stmt->execute([
            ':patient_id' => (int)$d['patient_id'],
            ':doctor_id'  => (int)$d['doctor_id'],
            ':title'      => $d['title'] ?? 'Appointment',
            ':description'=> $d['description'] ?? null,
            ':location'   => $d['location'] ?? null,
            ':type'       => $d['type'] ?? 'CONSULTATION',
            ':appointment_date' => $appointmentDate,
            ':appointment_time' => $appointmentTime
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Find appointment
     */
    public function find(int $id): ?array
    {
        $sql = "
            SELECT
                a.*,
                pu.name AS patient_name,
                du.name AS doctor_name,
                a.title       AS appointment_type,
                a.description AS reason
            FROM appointments a
            LEFT JOIN users pu ON a.patient_id = pu.id
            LEFT JOIN users du ON a.doctor_id = du.id
            WHERE a.id = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Update appointment
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
        $sql = "UPDATE appointments SET " . implode(', ', $fields) . " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete appointment
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM appointments WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
