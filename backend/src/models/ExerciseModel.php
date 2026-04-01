<?php

namespace Src\Models;

use Src\Config\DB;
use PDO;

class ExerciseModel
{
    private $db;

    public function __construct()
    {
        $this->db = DB::conn();
    }

    /**
     * Get all RA exercises
     */
    public function getAllExercises()
    {
        $stmt = $this->db->prepare("
            SELECT id, name, description, category, target_joints, 
                   difficulty_level, video_url, animation_url, 
                   instructions, ra_benefits, created_at
            FROM ra_exercises
            ORDER BY category, difficulty_level
        ");
        
        $stmt->execute();
        $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON fields
        foreach ($exercises as &$exercise) {
            $exercise['target_joints'] = json_decode($exercise['target_joints'], true);
            $exercise['instructions'] = json_decode($exercise['instructions'], true);
            $exercise['ra_benefits'] = json_decode($exercise['ra_benefits'], true);
        }
        
        return $exercises;
    }

    /**
     * Get exercises by category
     */
    public function getExercisesByCategory($category)
    {
        $stmt = $this->db->prepare("
            SELECT id, name, description, category, target_joints, 
                   difficulty_level, video_url, animation_url, 
                   instructions, ra_benefits, created_at
            FROM ra_exercises
            WHERE category = :category
            ORDER BY difficulty_level
        ");
        
        $stmt->execute(['category' => $category]);
        $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON fields
        foreach ($exercises as &$exercise) {
            $exercise['target_joints'] = json_decode($exercise['target_joints'], true);
            $exercise['instructions'] = json_decode($exercise['instructions'], true);
            $exercise['ra_benefits'] = json_decode($exercise['ra_benefits'], true);
        }
        
        return $exercises;
    }

    /**
     * Get exercise by ID
     */
    public function getExerciseById($id)
    {
        $stmt = $this->db->prepare("
            SELECT id, name, description, category, target_joints, 
                   difficulty_level, video_url, animation_url, 
                   instructions, ra_benefits, created_at
            FROM ra_exercises
            WHERE id = :id
        ");
        
        $stmt->execute(['id' => $id]);
        $exercise = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exercise) {
            $exercise['target_joints'] = json_decode($exercise['target_joints'], true);
            $exercise['instructions'] = json_decode($exercise['instructions'], true);
            $exercise['ra_benefits'] = json_decode($exercise['ra_benefits'], true);
        }
        
        return $exercise;
    }

    /**
     * Create exercise assignment
     */
    public function createAssignment($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO exercise_assignments 
            (id, doctor_id, patient_id, exercise_ids, notes, assigned_date, is_active, created_at, updated_at)
            VALUES 
            (:id, :doctor_id, :patient_id, :exercise_ids, :notes, :assigned_date, 1, NOW(), NOW())
        ");
        
        return $stmt->execute([
            'id' => $data['id'],
            'doctor_id' => $data['doctor_id'],
            'patient_id' => $data['patient_id'],
            'exercise_ids' => $data['exercise_ids'],
            'notes' => $data['notes'],
            'assigned_date' => $data['assigned_date']
        ]);
    }

    /**
     * Get patient assignments
     */
    public function getPatientAssignments($patientId)
    {
        $stmt = $this->db->prepare("
            SELECT ea.id, ea.doctor_id, ea.patient_id, ea.exercise_ids, 
                   ea.notes, ea.assigned_date, ea.is_active,
                   u.name as doctor_name
            FROM exercise_assignments ea
            LEFT JOIN users u ON ea.doctor_id = u.id
            WHERE ea.patient_id = :patient_id AND ea.is_active = 1
            ORDER BY ea.assigned_date DESC
        ");
        
        $stmt->execute(['patient_id' => $patientId]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode exercise_ids JSON
        foreach ($assignments as &$assignment) {
            $assignment['exercise_ids'] = json_decode($assignment['exercise_ids'], true);
        }
        
        return $assignments;
    }

    /**
     * Get doctor assignments
     */
    public function getDoctorAssignments($doctorId)
    {
        $stmt = $this->db->prepare("
            SELECT ea.id, ea.doctor_id, ea.patient_id, ea.exercise_ids, 
                   ea.notes, ea.assigned_date, ea.is_active,
                   u.name as patient_name
            FROM exercise_assignments ea
            LEFT JOIN users u ON ea.patient_id = u.id
            WHERE ea.doctor_id = :doctor_id AND ea.is_active = 1
            ORDER BY ea.assigned_date DESC
        ");
        
        $stmt->execute(['doctor_id' => $doctorId]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode exercise_ids JSON
        foreach ($assignments as &$assignment) {
            $assignment['exercise_ids'] = json_decode($assignment['exercise_ids'], true);
        }
        
        return $assignments;
    }

    /**
     * Update assignment
     */
    public function updateAssignment($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE exercise_assignments
            SET exercise_ids = :exercise_ids,
                notes = :notes,
                updated_at = NOW()
            WHERE id = :id
        ");
        
        return $stmt->execute([
            'id' => $id,
            'exercise_ids' => $data['exercise_ids'],
            'notes' => $data['notes']
        ]);
    }

    /**
     * Delete assignment (soft delete)
     */
    public function deleteAssignment($id)
    {
        $stmt = $this->db->prepare("
            UPDATE exercise_assignments
            SET is_active = 0,
                updated_at = NOW()
            WHERE id = :id
        ");
        
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Create exercise session
     */
    public function createSession($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO exercise_sessions 
            (id, patient_id, exercise_id, start_time, session_duration, 
             overall_accuracy, completion_rate, motion_data, performance_metrics, 
             completed, created_at)
            VALUES 
            (:id, :patient_id, :exercise_id, :start_time, :session_duration,
             :overall_accuracy, :completion_rate, :motion_data, :performance_metrics,
             :completed, NOW())
        ");
        
        return $stmt->execute([
            'id' => $data['id'],
            'patient_id' => $data['patient_id'],
            'exercise_id' => $data['exercise_id'],
            'start_time' => $data['start_time'],
            'session_duration' => $data['session_duration'],
            'overall_accuracy' => $data['overall_accuracy'],
            'completion_rate' => $data['completion_rate'],
            'motion_data' => $data['motion_data'],
            'performance_metrics' => $data['performance_metrics'],
            'completed' => $data['completed']
        ]);
    }

    /**
     * Get patient sessions
     */
    public function getPatientSessions($patientId, $limit = 50)
    {
        $stmt = $this->db->prepare("
            SELECT es.*, re.name as exercise_name, re.category
            FROM exercise_sessions es
            LEFT JOIN ra_exercises re ON es.exercise_id = re.id
            WHERE es.patient_id = :patient_id
            ORDER BY es.start_time DESC
            LIMIT :limit
        ");
        
        $stmt->bindValue('patient_id', $patientId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create performance report
     */
    public function createReport($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO performance_reports 
            (id, session_id, patient_id, exercise_id, report_data, generated_at)
            VALUES 
            (:id, :session_id, :patient_id, :exercise_id, :report_data, NOW())
        ");
        
        return $stmt->execute([
            'id' => $data['id'],
            'session_id' => $data['session_id'],
            'patient_id' => $data['patient_id'],
            'exercise_id' => $data['exercise_id'],
            'report_data' => $data['report_data']
        ]);
    }

    /**
     * Get patient reports
     */
    public function getPatientReports($patientId, $startDate = null, $endDate = null)
    {
        $sql = "
            SELECT pr.*, re.name as exercise_name, re.category
            FROM performance_reports pr
            LEFT JOIN ra_exercises re ON pr.exercise_id = re.id
            WHERE pr.patient_id = :patient_id
        ";
        
        $params = ['patient_id' => $patientId];
        
        if ($startDate) {
            $sql .= " AND pr.generated_at >= :start_date";
            $params['start_date'] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND pr.generated_at <= :end_date";
            $params['end_date'] = $endDate;
        }
        
        $sql .= " ORDER BY pr.generated_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode report_data JSON
        foreach ($reports as &$report) {
            $report['report_data'] = json_decode($report['report_data'], true);
        }
        
        return $reports;
    }
}
