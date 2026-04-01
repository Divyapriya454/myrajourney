<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Models\AppointmentModel;
use Src\Models\NotificationModel;
use Src\Utils\Response;

class AppointmentController
{
    private AppointmentModel $appts;

    public function __construct() {
        $this->appts = new AppointmentModel();
    }

    /**
     * List Appointments
     */
    public function list(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid  = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));

        $filters = [];

        if ($role === 'PATIENT') {
            $filters['patient_id'] = $uid;
        }
        elseif ($role === 'DOCTOR') {
            $filters['doctor_id'] = $uid;
        }
        else {
            if (!empty($_GET['patient_id'])) {
                $filters['patient_id'] = (int)$_GET['patient_id'];
            }
            if (!empty($_GET['doctor_id'])) {
                $filters['doctor_id'] = (int)$_GET['doctor_id'];
            }
        }

        $result = $this->appts->list($filters, $page, $limit);

        // Format for mobile
        foreach ($result['items'] as &$a) {
            // Handle both old (start_time/end_time) and new (appointment_date/appointment_time) formats
            if (isset($a['start_time'])) {
                $start = strtotime($a['start_time']);
                $end   = isset($a['end_time']) ? strtotime($a['end_time']) : null;
            } else {
                // Use appointment_date and appointment_time
                $dateTime = $a['appointment_date'] . ' ' . $a['appointment_time'];
                $start = strtotime($dateTime);
                $end = null; // No end time in new format
            }

            $a['formatted_date']      = date('M d, Y', $start);
            $a['formatted_time_slot'] = date('h:i A', $start) .
                                        ($end ? ' - ' . date('h:i A', $end) : '');

            $a['appointment_type'] = $a['appointment_type'] ?? $a['title'];
            $a['reason']           = $a['reason'] ?? $a['description'];
        }

        Response::json([
            'success' => true,
            'data'    => $result['items'],
            'meta'    => [
                'total' => $result['total'],
                'page'  => $page,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * Create Appointment
     */
    public function create(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid  = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // auto-set for patients and doctors
        if ($role === 'PATIENT') {
            $body['patient_id'] = $uid;
            // Auto-assign doctor if not provided
            if (empty($body['doctor_id'])) {
                try {
                    $db = \Src\Config\DB::conn();
                    $stmt = $db->prepare('SELECT assigned_doctor_id FROM patients WHERE user_id = :uid');
                    $stmt->execute([':uid' => $uid]);
                    $assignedDoc = $stmt->fetchColumn();
                    if ($assignedDoc) { $body['doctor_id'] = (int)$assignedDoc; }
                } catch (\Throwable $e) {}
            }
            // Fallback: use first available doctor
            if (empty($body['doctor_id'])) {
                try {
                    $db = \Src\Config\DB::conn();
                    $stmt = $db->query('SELECT user_id FROM doctors LIMIT 1');
                    $docUserId = $stmt->fetchColumn();
                    if ($docUserId) { $body['doctor_id'] = (int)$docUserId; }
                } catch (\Throwable $e) {}
            }
        } elseif ($role === 'DOCTOR') {
            $body['doctor_id'] = $uid;
        }

        // Check required fields - support both formats
        if (empty($body['patient_id']) || empty($body['doctor_id'])) {
            Response::json([
                'success'=>false,
                'error'=>[
                    'code'=>'VALIDATION',
                    'message'=>'Missing patient_id or doctor_id'
                ]
            ], 422);
            return;
        }

        // Support both start_time/end_time AND appointment_date/appointment_time
        if (empty($body['start_time']) && empty($body['appointment_date'])) {
            Response::json([
                'success'=>false,
                'error'=>[
                    'code'=>'VALIDATION',
                    'message'=>'Missing start_time or appointment_date'
                ]
            ], 422);
            return;
        }

        // Set default title if not provided
        if (empty($body['title'])) {
            $body['title'] = 'Appointment';
        }

        // convert Android input: "reason" → description
        if (isset($body['reason']) && !isset($body['description'])) {
            $body['description'] = $body['reason'];
        }

        $id   = $this->appts->create($body);
        $item = $this->appts->find($id);

        // Push notification
        try {
            $notif = new NotificationModel();
            if ($role === 'DOCTOR') {
                $notif->create((int)$body['patient_id'], 'APPOINTMENT',
                    'New appointment scheduled', $body['title']);
            } else {
                $notif->create((int)$body['doctor_id'], 'APPOINTMENT',
                    'New appointment request', $body['title']);
            }
        } catch (\Throwable $e) {}

        // Format - handle both column formats
        if ($item) {
            // Create datetime from appointment_date and appointment_time
            $appointmentDateTime = $item['appointment_date'] . ' ' . $item['appointment_time'];
            $start = strtotime($appointmentDateTime);

            $item['formatted_date']      = date('M d, Y', $start);
            $item['formatted_time_slot'] = date('h:i A', $start);
            $item['appointment_type']    = $item['title'];
            $item['reason']              = $item['description'];
            
            // Add start_time and end_time for compatibility
            $item['start_time'] = $appointmentDateTime;
            $item['end_time'] = date('Y-m-d H:i:s', $start + 3600); // Default 1 hour duration
        }

        Response::json(['success'=>true,'data'=>$item], 201);
    }

    /**
     * Get Appointment
     */
    public function get(int $id): void
    {
        $item = $this->appts->find($id);

        if (!$item) {
            Response::json([
                'success'=>false,
                'error'=>[
                    'code'=>'NOT_FOUND',
                    'message'=>'Not found'
                ]
            ], 404);
            return;
        }

        $start = strtotime($item['start_time'] ?? ($item['appointment_date'] . ' ' . ($item['appointment_time'] ?? '00:00:00')));
        $end   = isset($item['end_time']) ? strtotime($item['end_time']) : false;

        $item['formatted_date']      = date('M d, Y', $start);
        $item['formatted_time_slot'] = date('h:i A', $start) .
                                       ($end ? ' - ' . date('h:i A', $end) : '');
        $item['appointment_type']    = $item['title'];
        $item['reason']              = $item['description'];

        Response::json(['success'=>true,'data'=>$item]);
    }

    /**
     * Update Appointment (Reschedule)
     */
    public function update(int $id): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid  = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        $item = $this->appts->find($id);

        if (!$item) {
            Response::json([
                'success'=>false,
                'error'=>[
                    'code'=>'NOT_FOUND',
                    'message'=>'Appointment not found'
                ]
            ], 404);
            return;
        }

        // Authorization: Only doctor who created it can update
        if ($role === 'DOCTOR' && (int)$item['doctor_id'] !== $uid) {
            Response::json([
                'success'=>false,
                'error'=>[
                    'code'=>'FORBIDDEN',
                    'message'=>'You can only update your own appointments'
                ]
            ], 403);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // Update allowed fields
        $updateData = [];
        
        if (isset($body['appointment_date'])) {
            $updateData['appointment_date'] = $body['appointment_date'];
        }
        
        if (isset($body['appointment_time'])) {
            $updateData['appointment_time'] = $body['appointment_time'];
        }
        
        if (isset($body['title'])) {
            $updateData['title'] = $body['title'];
        }
        
        if (isset($body['description'])) {
            $updateData['description'] = $body['description'];
        }
        
        if (isset($body['status'])) {
            $updateData['status'] = $body['status'];
        }

        if (empty($updateData)) {
            Response::json([
                'success'=>false,
                'error'=>[
                    'code'=>'VALIDATION',
                    'message'=>'No fields to update'
                ]
            ], 422);
            return;
        }

        $success = $this->appts->update($id, $updateData);

        if ($success) {
            $updated = $this->appts->find($id);
            
            // Send notification to patient
            try {
                $notif = new NotificationModel();
                $notif->create((int)$item['patient_id'], 'APPOINTMENT',
                    'Appointment rescheduled', 'Your appointment has been rescheduled');
            } catch (\Throwable $e) {}

            Response::json(['success'=>true,'data'=>$updated,'message'=>'Appointment updated successfully']);
        } else {
            Response::json([
                'success'=>false,
                'error'=>[
                    'code'=>'UPDATE_FAILED',
                    'message'=>'Failed to update appointment'
                ]
            ], 500);
        }
    }

    /**
     * Delete Appointment (Cancel)
     */
    public function delete(int $id): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid  = (int)($auth['uid'] ?? 0);
        $role = $auth['role'] ?? '';

        $item = $this->appts->find($id);

        if (!$item) {
            Response::json([
                'success'=>false,
                'error'=>[
                    'code'=>'NOT_FOUND',
                    'message'=>'Appointment not found'
                ]
            ], 404);
            return;
        }

        // Authorization: Only doctor who created it can delete
        if ($role === 'DOCTOR' && (int)$item['doctor_id'] !== $uid) {
            Response::json([
                'success'=>false,
                'error'=>[
                    'code'=>'FORBIDDEN',
                    'message'=>'You can only delete your own appointments'
                ]
            ], 403);
            return;
        }

        $success = $this->appts->delete($id);

        if ($success) {
            // Send notification to patient
            try {
                $notif = new NotificationModel();
                $notif->create((int)$item['patient_id'], 'APPOINTMENT',
                    'Appointment cancelled', 'Your appointment has been cancelled');
            } catch (\Throwable $e) {}

            Response::json(['success'=>true,'message'=>'Appointment deleted successfully']);
        } else {
            Response::json([
                'success'=>false,
                'error'=>[
                    'code'=>'DELETE_FAILED',
                    'message'=>'Failed to delete appointment'
                ]
            ], 500);
        }
    }
}