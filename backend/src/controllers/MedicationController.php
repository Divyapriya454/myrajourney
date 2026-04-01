<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Models\MedicationModel;
use Src\Utils\Response;
use Src\Models\NotificationModel;

class MedicationController
{
    private MedicationModel $meds;
    public function __construct(){ $this->meds = new MedicationModel(); }

    public function listForPatient(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        // Determine patient_id based on role
        if ($role === 'PATIENT') {
            $pid = $uid;
        } elseif ($role === 'DOCTOR') {
            $pid = (int)($_GET['patient_id'] ?? 0);
            if (!$pid) {
                $db = \Src\Config\DB::conn();
                $stmt = $db->prepare("SELECT DISTINCT patient_id FROM appointments WHERE doctor_id = :did");
                $stmt->execute([':did' => $uid]);
                $patientIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                if (empty($patientIds)) {
                    Response::json(['success'=>true,'data'=>[],'meta'=>['total'=>0,'page'=>1,'limit'=>20]]);
                    return;
                }

                $pid = (int)($patientIds[0] ?? 0);
            }
        } else {
            $pid = (int)($_GET['patient_id'] ?? 0);
        }

        if (!$pid) {
            Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'patient_id required']],422);
            return;
        }

        $active = isset($_GET['active']) ? (int)($_GET['active'] === '1') : null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));

        $r = $this->meds->patientMedications($pid, $active, $page, $limit);

        Response::json(['success'=>true,'data'=>$r['items'],'meta'=>[
            'total'=>$r['total'],'page'=>$page,'limit'=>$limit
        ]]);
    }

    public function assign(): void
    {
        // Enhanced logging for debugging
        $logFile = __DIR__ . '/../../public/medication_debug.log';
        file_put_contents($logFile, date('[Y-m-d H:i:s] ASSIGN METHOD CALLED') . PHP_EOL, FILE_APPEND);
        
        $raw = file_get_contents('php://input');
        file_put_contents($logFile, date('[Y-m-d H:i:s] RAW INPUT: ') . $raw . PHP_EOL, FILE_APPEND);
        
        $body = json_decode($raw, true) ?? [];
        file_put_contents($logFile, date('[Y-m-d H:i:s] DECODED BODY: ') . json_encode($body) . PHP_EOL, FILE_APPEND);
        
        // Log specific fields we care about
        $criticalFields = ['instructions', 'duration', 'food_relation', 'is_morning', 'is_afternoon', 'is_night'];
        foreach ($criticalFields as $field) {
            $value = $body[$field] ?? 'NOT_SET';
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . strtoupper($field) . ': ' . $value . PHP_EOL, FILE_APPEND);
        }
        
        // Inject authenticated doctor ID if not present
        $auth = $_SERVER['auth'] ?? [];
        if (!isset($body['doctor_id']) && isset($auth['uid'])) {
            $body['doctor_id'] = (int)$auth['uid'];
        }

        if (empty($body['patient_id'])) {
            Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'Missing patient_id']],422);
            return;
        }

        try {
            // Log the data being passed to the model
            file_put_contents($logFile, date('[Y-m-d H:i:s] CALLING MODEL WITH: ') . json_encode($body) . PHP_EOL, FILE_APPEND);
            
            $id = $this->meds->assign($body);
            
            file_put_contents($logFile, date('[Y-m-d H:i:s] MODEL RETURNED ID: ') . $id . PHP_EOL, FILE_APPEND);

            try {
                $notif = new NotificationModel();
                $notif->create((int)$body['patient_id'], 'DOCTOR_MEDICATION',
                    'Medication updated', 'Your medication plan has been updated.');
            } catch (\Throwable $e) {}

            Response::json(['success'=>true,'data'=>['id'=>$id]],201);
        } catch (\Exception $e) {
            file_put_contents($logFile, date('[Y-m-d H:i:s] ERROR: ') . $e->getMessage() . PHP_EOL, FILE_APPEND);
            
            if (strpos($e->getMessage(), 'Duplicate medication') !== false) {
                Response::json(['success'=>false,'error'=>['code'=>'DUPLICATE','message'=>$e->getMessage()]],409);
            } else {
                Response::json(['success'=>false,'error'=>['code'=>'SERVER_ERROR','message'=>'Failed to assign medication']],500);
            }
        }
    }

    public function setActive(int $id): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $active = (int)(!empty($body['active']));

        $this->meds->updateActive($id, $active);

        Response::json(['success'=>true]);
    }

    public function logIntake(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        // Only patients can log their own medication intake
        if ($role !== 'PATIENT') {
            Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Only patients can log medication intake']],403);
            return;
        }

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            Response::json(['success'=>false,'error'=>['code'=>'INVALID_INPUT','message'=>'Invalid JSON input']],400);
            return;
        }

        // Validate required fields
        $requiredFields = ['patient_medication_id', 'status'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                Response::json(['success'=>false,'error'=>['code'=>'MISSING_FIELD','message'=>"Missing required field: $field"]],400);
                return;
            }
        }

        $patientMedicationId = (int)$input['patient_medication_id'];
        $status = strtoupper($input['status']); // normalize to uppercase
        $notes = $input['notes'] ?? null;
        $takenAt = $input['taken_at'] ?? date('Y-m-d H:i:s');

        // Validate status
        if (!in_array($status, ['TAKEN', 'SKIPPED', 'MISSED'])) {
            Response::json(['success'=>false,'error'=>['code'=>'INVALID_STATUS','message'=>'Status must be TAKEN, SKIPPED, or MISSED']],400);
            return;
        }

        // Verify the medication belongs to the patient
        $db = \Src\Config\DB::conn();
        $stmt = $db->prepare('SELECT patient_id, name_override, medication_name, dosage FROM patient_medications WHERE id = :id AND active = 1');
        $stmt->execute([':id' => $patientMedicationId]);
        $medication = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$medication) {
            Response::json(['success'=>false,'error'=>['code'=>'NOT_FOUND','message'=>'Medication not found or inactive']],404);
            return;
        }

        if ((int)$medication['patient_id'] !== $uid) {
            Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Cannot log intake for another patient\'s medication']],403);
            return;
        }

        // Log the medication intake
        try {
            $logData = [
                'patient_medication_id' => $patientMedicationId,
                'status' => $status,
                'taken_at' => $takenAt,
                'notes' => $notes
            ];
            
            $logId = $this->meds->logIntake($logData);

            // If it's a missed dose, notify the doctor
            if ($status === 'SKIPPED' || $status === 'MISSED') {
                $this->notifyDoctorOfMissedDose($medication, $uid, $takenAt, $notes);
            }

            Response::json([
                'success' => true,
                'message' => 'Medication intake logged successfully',
                'data' => [
                    'log_id' => $logId,
                    'status' => $status,
                    'logged_at' => $takenAt
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Error logging medication intake: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Response::json(['success'=>false,'error'=>['code'=>'DATABASE_ERROR','message'=>'Failed to log medication intake: ' . $e->getMessage()]],500);
        }
    }

    private function notifyDoctorOfMissedDose(array $medication, int $patientId, string $missedAt, ?string $notes): void
    {
        try {
            // Find the patient's doctor
            $db = \Src\Config\DB::conn();
            $stmt = $db->prepare('
                SELECT DISTINCT pm.doctor_id, u.name as doctor_name, p.name as patient_name
                FROM patient_medications pm
                JOIN users u ON pm.doctor_id = u.id
                JOIN users p ON pm.patient_id = p.id
                WHERE pm.patient_id = :patient_id AND pm.doctor_id IS NOT NULL
                LIMIT 1
            ');
            $stmt->execute([':patient_id' => $patientId]);
            $doctorInfo = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($doctorInfo) {
                $medName = $medication['name_override'] ?: $medication['medication_name'] ?: 'Medication';
                $dosage = $medication['dosage'] ? ' (' . $medication['dosage'] . ')' : '';
                $title = 'Missed Medication Dose';
                $message = "Patient {$doctorInfo['patient_name']} missed their dose of {$medName}{$dosage} at " . date('M j, Y g:i A', strtotime($missedAt));
                if ($notes) {
                    $message .= "\nPatient notes: " . $notes;
                }

                // Create notification for doctor
                $notif = new NotificationModel();
                $notif->create((int)$doctorInfo['doctor_id'], 'MISSED_MEDICATION', $title, $message);
            }
        } catch (\Exception $e) {
            error_log("Error notifying doctor of missed dose: " . $e->getMessage());
            // Don't fail the main operation if notification fails
        }
    }

    public function delete(int $id): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        // Only doctors and admins can delete medications
        if (!in_array($role, ['DOCTOR', 'ADMIN'])) {
            Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Access denied']],403);
            return;
        }

        // Check if medication exists and get patient info
        $db = \Src\Config\DB::conn();
        $stmt = $db->prepare('SELECT patient_id, name_override, medication_name, dosage FROM patient_medications WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $medication = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$medication) {
            Response::json(['success'=>false,'error'=>['code'=>'NOT_FOUND','message'=>'Medication not found']],404);
            return;
        }

        // For doctors, verify they have access to this patient
        if ($role === 'DOCTOR') {
            // Check if doctor has access through appointments OR if they assigned the medication
            $stmt = $db->prepare('
                SELECT COUNT(*) FROM (
                    SELECT 1 FROM appointments WHERE doctor_id = :did AND patient_id = :pid
                    UNION
                    SELECT 1 FROM patient_medications WHERE doctor_id = :did AND patient_id = :pid
                    UNION
                    SELECT 1 FROM doctors WHERE user_id = :did
                ) AS access_check
            ');
            $stmt->execute([':did' => $uid, ':pid' => $medication['patient_id']]);
            $hasAccess = (int)$stmt->fetchColumn() > 0;

            if (!$hasAccess) {
                Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'No access to this patient']],403);
                return;
            }
        }

        // Delete the medication
        $this->meds->delete($id);

        // Send notification to patient
        try {
            $notif = new NotificationModel();
            $medName = $medication['name_override'] ?: $medication['medication_name'] ?: 'Medication';
            $notif->create((int)$medication['patient_id'], 'DOCTOR_MEDICATION',
                'Medication removed', "Your medication '$medName' has been removed from your plan.");
        } catch (\Throwable $e) {}

        Response::json(['success'=>true,'message'=>'Medication deleted successfully']);
    }

    public function listLogs(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        // Determine patient_id based on role
        if ($role === 'PATIENT') {
            $pid = $uid;
        } elseif ($role === 'DOCTOR') {
            $pid = (int)($_GET['patient_id'] ?? 0);
            if (!$pid) {
                Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'patient_id required for doctors']],422);
                return;
            }
        } else {
            $pid = (int)($_GET['patient_id'] ?? 0);
        }

        if (!$pid) {
            Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'patient_id required']],422);
            return;
        }

        $logs = $this->meds->getMedicationLogs($pid);
        Response::json(['success'=>true,'data'=>$logs]);
    }

    public function clearAllPatientMedications(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $role = $auth['role'] ?? '';

        // Only admins can clear all medications
        if ($role !== 'ADMIN') {
            Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Admin access required']],403);
            return;
        }

        $this->meds->clearAll();

        Response::json(['success'=>true,'message'=>'All patient medications cleared successfully']);
    }

    public function getAllPatientMedications(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $role = $auth['role'] ?? '';

        // Only admins can view all medications
        if ($role !== 'ADMIN') {
            Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Admin access required']],403);
            return;
        }

        $medications = $this->meds->getAllPatientMedications();

        Response::json(['success'=>true,'data'=>$medications]);
    }

    // ⭐ FIXED SEARCH WITHOUT dosage COLUMN
    public function search(): void
    {
        $q = strtolower(trim($_GET['q'] ?? ''));

        $db = \Src\Config\DB::conn();

        // Select ONLY columns that exist in your DB
        $stmt = $db->prepare("
            SELECT id, name
            FROM medications
            WHERE LOWER(name) LIKE :q
            ORDER BY name ASC
        ");

        $stmt->execute([':q' => "%$q%"]);

        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        Response::json([
            'success' => true,
            'data' => $items
        ]);
    }

}
