<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Models\CrpModel;
use Src\Utils\Response;

class CrpController
{
    private CrpModel $crp;

    public function __construct()
    {
        $this->crp = new CrpModel();
    }

    /**
     * Get CRP history for a patient
     */
    public function getHistory(int $patientId): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        // Authorization check
        if ($role === 'PATIENT' && $uid !== $patientId) {
            Response::json(['success' => false, 'message' => 'Access denied'], 403);
            return;
        }

        if ($role === 'DOCTOR') {
            // Check if doctor has access to this patient via assigned_doctor_id
            $db = \Src\Config\DB::conn();
            $stmt = $db->prepare("SELECT COUNT(*) FROM patients WHERE user_id = :patient_id AND assigned_doctor_id = :doctor_id");
            $stmt->execute([':doctor_id' => $uid, ':patient_id' => $patientId]);
            
            if ($stmt->fetchColumn() == 0) {
                // Also check appointments as fallback
                $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = :doctor_id AND patient_id = :patient_id");
                $stmt->execute([':doctor_id' => $uid, ':patient_id' => $patientId]);
                
                if ($stmt->fetchColumn() == 0) {
                    Response::json(['success' => false, 'message' => 'Access denied'], 403);
                    return;
                }
            }
        }

        $history = $this->crp->getHistory($patientId);

        // Format data for Android app
        $formattedData = array_map(function($item) {
            return [
                'id' => (int)$item['id'],
                'patientId' => (int)$item['patient_id'],
                'measurementDate' => $item['measurement_date'],
                'crpValue' => (float)$item['crp_value'],
                'measurementUnit' => $item['measurement_unit'],
                'doctorId' => $item['doctor_id'] ? (int)$item['doctor_id'] : null,
                'reportId' => $item['report_id'] ? (int)$item['report_id'] : null,
                'notes' => $item['notes'],
                'createdAt' => $item['created_at']
            ];
        }, $history);

        Response::json([
            'success' => true,
            'data' => $formattedData
        ]);
    }

    /**
     * Create new CRP measurement
     */
    public function create(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // Validate required fields
        if (!isset($body['patient_id'], $body['crp_value'], $body['measurement_date'])) {
            Response::json(['success' => false, 'message' => 'Missing required fields'], 422);
            return;
        }

        $patientId = (int)$body['patient_id'];
        $crpValue = (float)$body['crp_value'];

        // Validate CRP value range
        if ($crpValue < 0 || $crpValue > 500) {
            Response::json(['success' => false, 'message' => 'CRP value must be between 0 and 500 mg/L'], 422);
            return;
        }

        // Authorization check
        if ($role === 'DOCTOR') {
            // Check if doctor has access to this patient via assigned_doctor_id
            $db = \Src\Config\DB::conn();
            $stmt = $db->prepare("SELECT COUNT(*) FROM patients WHERE user_id = :patient_id AND assigned_doctor_id = :doctor_id");
            $stmt->execute([':doctor_id' => $uid, ':patient_id' => $patientId]);
            
            if ($stmt->fetchColumn() == 0) {
                // Also check appointments as fallback
                $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = :doctor_id AND patient_id = :patient_id");
                $stmt->execute([':doctor_id' => $uid, ':patient_id' => $patientId]);
                
                if ($stmt->fetchColumn() == 0) {
                    Response::json(['success' => false, 'message' => 'Access denied'], 403);
                    return;
                }
            }

            $body['doctor_id'] = $uid;
        } elseif ($role === 'PATIENT' && $uid !== $patientId) {
            Response::json(['success' => false, 'message' => 'Access denied'], 403);
            return;
        }

        try {
            $id = $this->crp->create($body);
            Response::json(['success' => true, 'data' => ['id' => $id]], 201);
        } catch (\Exception $e) {
            Response::json(['success' => false, 'message' => 'Failed to create CRP measurement'], 500);
        }
    }
}