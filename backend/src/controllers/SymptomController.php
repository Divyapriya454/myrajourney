<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Models\SymptomModel;
use Src\Utils\Response;
use Src\Models\NotificationModel;

class SymptomController
{
	private SymptomModel $sym;
	public function __construct(){ $this->sym = new SymptomModel(); }

	public function list(): void
	{
		$auth = $_SERVER['auth'] ?? [];
		$uid = (int)($auth['uid'] ?? 0);
		$role = $auth['role'] ?? '';
		
		// Determine patient_id based on role
		if ($role === 'PATIENT') {
			$pid = $uid; // Patient sees their own symptoms
		} elseif ($role === 'DOCTOR') {
			// Doctor can see any patient's symptoms if patient_id is provided
			$pid = (int)($_GET['patient_id'] ?? 0);
			if (!$pid) {
				// If no patient_id, get all patients from doctor's appointments
				$db = \Src\Config\DB::conn();
				$stmt = $db->prepare("SELECT DISTINCT patient_id FROM appointments WHERE doctor_id = :did");
				$stmt->execute([':did' => $uid]);
				$patientIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
				if (empty($patientIds)) {
					Response::json(['success'=>true,'data'=>[]]);
					return;
				}
				// Return first patient's symptoms (can be enhanced to return all)
				$pid = (int)($patientIds[0] ?? 0);
			}
		} else {
			// Admin or explicit patient_id
			$pid = (int)($_GET['patient_id'] ?? 0);
		}
		
		if (!$pid) {
			Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'patient_id required']],422);
			return;
		}
		
		$from=$_GET['from'] ?? null;
		$to=$_GET['to'] ?? null;
		$data=$this->sym->list($pid,$from,$to);
		Response::json(['success'=>true,'data'=>$data]);
	}

	public function create(): void
	{
		$auth = $_SERVER['auth'] ?? [];
		$uid = (int)($auth['uid'] ?? 0);
		$role = $auth['role'] ?? '';
		
		$body=json_decode(file_get_contents('php://input'), true) ?? [];
		
		// Auto-set patient_id for PATIENT role
		if ($role === 'PATIENT') {
			$body['patient_id'] = $uid;
		}
		
		// Default optional fields so app doesn't need to send all
		$body['stiffness_level'] = $body['stiffness_level'] ?? $body['pain_level'] ?? 0;
		$body['fatigue_level']   = $body['fatigue_level']   ?? $body['pain_level'] ?? 0;
		$body['date']            = $body['date'] ?? $body['logged_at'] ?? date('Y-m-d');

		// Only patient_id and date are truly required
		foreach(['patient_id','date'] as $k) {
			if (empty($body[$k]) && $body[$k] !== 0) {
				Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>"Missing $k"]],422);
				return;
			}
		}
		
		// Validate joint_count if provided
		if (isset($body['joint_count']) && $body['joint_count'] !== null && $body['joint_count'] !== '') {
			$jointCount = (int)$body['joint_count'];
			if ($jointCount < 0 || $jointCount > 10) {
				Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'Joint count must be between 0 and 10']],422);
				return;
			}
		}
		
		try {
			$id=$this->sym->create($body);
			// Notify the patient's doctors
			try {
				$notif = new NotificationModel();
				$db = \Src\Config\DB::conn();
				
				// Get patient name for better notification message
				$stmt = $db->prepare('SELECT name FROM users WHERE id = :pid');
				$stmt->execute([':pid'=>(int)$body['patient_id']]);
				$patientName = $stmt->fetchColumn() ?: 'Patient';
				
				// Get assigned doctor for this patient from patients table
				// Note: assigned_doctor_id stores user_id, not doctors.id
				$stmt = $db->prepare('SELECT assigned_doctor_id FROM patients WHERE user_id = :pid');
				$stmt->execute([':pid'=>(int)$body['patient_id']]);
				$assignedDoctorId = $stmt->fetchColumn();
				
				if ($assignedDoctorId) {
					// Create detailed notification for the assigned doctor
					$title = 'New Symptom Log from ' . $patientName;
					$message = $patientName . ' has updated their symptom log with pain level ' . 
					          ($body['pain_level'] ?? 'N/A') . '/10, stiffness ' . 
					          ($body['stiffness_level'] ?? 'N/A') . '/10, and fatigue ' . 
					          ($body['fatigue_level'] ?? 'N/A') . '/10.';
					
					if (isset($body['joint_count']) && $body['joint_count'] > 0) {
						$message .= ' Affected joints: ' . $body['joint_count'];
					}
					
					$notif->create((int)$assignedDoctorId, 'PATIENT_SYMPTOM', $title, $message);
					
					// Log for debugging
					error_log("Created symptom notification for patient {$body['patient_id']} to doctor $assignedDoctorId");
				} else {
					error_log("No assigned doctor found for patient {$body['patient_id']}");
				}
				
			} catch (\Throwable $e) { 
				// Log the error for debugging
				error_log("Failed to create symptom notification: " . $e->getMessage());
				error_log("Stack trace: " . $e->getTraceAsString());
			}
			Response::json(['success'=>true,'data'=>['id'=>$id]],201);
		} catch (\InvalidArgumentException $e) {
			Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>$e->getMessage()]],422);
		}
	}
}






