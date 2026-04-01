<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Config\DB;
use Src\Utils\Response;

class DoctorController
{
	public function overview(): void
	{
		$auth = $_SERVER['auth'] ?? [];
		$uid = (int)($auth['uid'] ?? 0);
		$db = DB::conn();
		$today = date('Y-m-d');
		
		// Get today's schedule
		// NOTE: appointments.doctor_id references users.id, not doctors.id
		$schedule = $db->prepare("SELECT a.*, u.name as patient_name 
			FROM appointments a 
			LEFT JOIN users u ON a.patient_id = u.id 
			WHERE a.doctor_id=:uid AND DATE(a.appointment_date)=:d 
			ORDER BY a.appointment_time ASC");
		$schedule->execute([':uid'=>$uid, ':d'=>$today]);
		
		// Count only assigned patients' reports (last 7 days)
		$reportStmt = $db->prepare("SELECT COUNT(*) FROM reports r 
			INNER JOIN patients p ON r.patient_id = p.user_id 
			WHERE p.assigned_doctor_id = :uid AND r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
		$reportStmt->execute([':uid'=>$uid]);
		$reportCount = (int)$reportStmt->fetchColumn();
		
		// Count assigned patients
		$patientStmt = $db->prepare("SELECT COUNT(*) FROM patients WHERE assigned_doctor_id = :uid");
		$patientStmt->execute([':uid'=>$uid]);
		$patientCount = (int)$patientStmt->fetchColumn();
		
		Response::json(['success'=>true,'data'=>[
			'todaySchedule'=>$schedule->fetchAll(),
			'recentReportsCount'=>$reportCount,
			'patientsCount'=>$patientCount,
		]]);
	}
}




















