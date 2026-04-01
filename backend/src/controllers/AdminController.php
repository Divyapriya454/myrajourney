<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Utils\Response;
use Src\Models\UserModel;
use Src\Config\DB;

class AdminController
{
    private UserModel $users;

    public function __construct()
    {
       $this->users = new UserModel();
    }

    public function createUser(): void
    {
       $auth = $_SERVER['auth'] ?? [];
       $role = $auth['role'] ?? '';
       $creatorId = (int)($auth['uid'] ?? 0);

       // ✅ CHANGE 1: Allow ADMIN and DOCTOR roles
       if ($role !== 'ADMIN' && $role !== 'DOCTOR') {
          Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Access denied']], 403);
          return;
       }

       $body = json_decode(file_get_contents('php://input'), true) ?? [];

       $email = trim(strtolower($body['email'] ?? ''));
       $password = (string)($body['password'] ?? '');
       $userRole = in_array($body['role'] ?? 'PATIENT', ['PATIENT','DOCTOR']) ? $body['role'] : 'PATIENT';
       $name = $body['name'] ?? null;
       $phone = $body['phone'] ?? $body['mobile'] ?? null;
       $profilePicture = $body['profile_picture'] ?? null; // Get profile picture base64

       // ✅ CHANGE 2: Doctors can ONLY create Patients
       if ($role === 'DOCTOR' && $userRole !== 'PATIENT') {
           Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Doctors can only register new patients.']], 403);
           return;
       }

       // Use default password if not provided or too short
       if (empty($password) || strlen($password) < 6) {
           $password = \Src\Utils\DefaultPasswords::getDefaultPassword($userRole);
       }

       if (!$email) {
          Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'Email is required']], 422);
          return;
       }

       if ($this->users->findByEmail($email)) {
          Response::json(['success'=>false,'error'=>['code'=>'EMAIL_TAKEN','message'=>'This mail ID already exists']], 409);
          return;
       }

       $db = DB::conn();
       try {
           $db->beginTransaction();

           // Process profile picture if provided
           $profilePictureFilename = null;
           if ($profilePicture && strpos($profilePicture, 'data:image') === false) {
               // It's base64 without data URI prefix
               $profilePictureFilename = $this->saveProfilePicture($profilePicture, $email);
           } elseif ($profilePicture && strpos($profilePicture, 'data:image') === 0) {
               // It's base64 with data URI prefix - extract the base64 part
               $parts = explode(',', $profilePicture);
               if (count($parts) > 1) {
                   $profilePictureFilename = $this->saveProfilePicture($parts[1], $email);
               }
           }

           $uid = $this->users->create([
              'email'=>$email,
              'password'=>password_hash($password, PASSWORD_BCRYPT),
              'role'=>$userRole,
              'name'=>$name,
              'phone'=>$phone,
              'avatar_url'=>$profilePictureFilename, // Use avatar_url to match UserModel
           ]);

           if ($userRole === 'PATIENT') {
              // ✅ CHANGE 3: Auto-assign to the creating Doctor
              if ($role === 'DOCTOR') {
                  $assignedDoctorId = $creatorId;
              } else {
                  // Admins can optionally assign a doctor ID via the request
                  $assignedDoctorId = isset($body['assigned_doctor_id']) ? (int)$body['assigned_doctor_id'] : null;
              }

              $address = $body['address'] ?? null;
              $age = $body['age'] ?? null;
              $gender = $body['gender'] ?? null;

              $stmt = $db->prepare('INSERT INTO patients (user_id, assigned_doctor_id, address, age, gender, created_at, updated_at) VALUES (:user_id, :doctor_id, :address, :age, :gender, NOW(), NOW())');
              $stmt->execute([
                  ':user_id' => $uid,
                  ':doctor_id' => $assignedDoctorId,
                  ':address' => $address,
                  ':age' => $age,
                  ':gender' => $gender
              ]);
           }

           if ($userRole === 'DOCTOR') {
              $stmt = $db->prepare('INSERT INTO doctors (user_id, specialization, created_at, updated_at) VALUES (:user_id, :specialization, NOW(), NOW())');
              $stmt->execute([
                  ':user_id' => $uid,
                  ':specialization' => $body['specialization'] ?? null
              ]);
           }

           $db->commit();

           Response::json(['success'=>true,'data'=>[
              'id'=>$uid,
              'email'=>$email,
              'role'=>$userRole,
              'name'=>$name,
           ]], 201);

       } catch (\Throwable $e) {
           $db->rollBack();
           error_log("Create User Failed: " . $e->getMessage());
           Response::json([
               'success'=>false,
               'error'=>[
                   'code'=>'CREATION_FAILED',
                   'message'=>'Failed to create user. ' . $e->getMessage()
               ]
           ], 500);
       }
    }

    public function listUsers(): void
    {
       $auth = $_SERVER['auth'] ?? [];
       $role = $auth['role'] ?? '';

       if ($role !== 'ADMIN') {
          Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Access denied']], 403);
          return;
       }

       $db = DB::conn();
       $stmt = $db->prepare("
           SELECT
               u.id, u.name, u.email, u.role, u.phone,
               IFNULL(u.avatar_url, '') AS avatar_url,
               p.age, p.gender, p.address, p.assigned_doctor_id,
               d.specialization
           FROM users u
           LEFT JOIN patients p ON u.id = p.user_id
           LEFT JOIN doctors d ON u.id = d.user_id
           ORDER BY u.created_at DESC
       ");
       $stmt->execute();
       $users = $stmt->fetchAll();

       Response::json(['success'=>true,'data'=>$users]);
    }

    public function assignPatientToDoctor(): void
    {
       $auth = $_SERVER['auth'] ?? [];
       $role = $auth['role'] ?? '';

       // Log the request
       $logFile = __DIR__ . '/../../public/assignment_log.txt';
       file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Assignment request received\n", FILE_APPEND);
       file_put_contents($logFile, "Auth role: $role\n", FILE_APPEND);

       if ($role !== 'ADMIN') {
          Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Only admins can assign patients']], 403);
          return;
       }

       $body = json_decode(file_get_contents('php://input'), true) ?? [];
       file_put_contents($logFile, "Request body: " . json_encode($body) . "\n", FILE_APPEND);
       
       $patientId = (int)($body['patient_id'] ?? 0);
       $doctorId = isset($body['doctor_id']) ? (int)$body['doctor_id'] : null;

       file_put_contents($logFile, "Patient ID: $patientId, Doctor ID: " . ($doctorId ?? 'NULL') . "\n", FILE_APPEND);

       if ($patientId <= 0) {
          file_put_contents($logFile, "ERROR: Invalid patient ID\n", FILE_APPEND);
          Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'Invalid patient ID']], 422);
          return;
       }

       $db = DB::conn();

       $stmt = $db->prepare('SELECT id FROM users WHERE id = :id AND role = "PATIENT"');
       $stmt->execute([':id'=>$patientId]);
       if (!$stmt->fetch()) {
          file_put_contents($logFile, "ERROR: Patient not found in users table\n", FILE_APPEND);
          Response::json(['success'=>false,'error'=>['code'=>'NOT_FOUND','message'=>'Patient not found']], 404);
          return;
       }

       if ($doctorId !== null) {
          $stmt = $db->prepare('SELECT id FROM users WHERE id = :id AND role = "DOCTOR"');
          $stmt->execute([':id'=>$doctorId]);
          if (!$stmt->fetch()) {
             file_put_contents($logFile, "ERROR: Doctor not found in users table\n", FILE_APPEND);
             Response::json(['success'=>false,'error'=>['code'=>'NOT_FOUND','message'=>'Doctor not found']], 404);
             return;
          }
       }

       // Check if patient entry exists in patients table
       $stmt = $db->prepare('SELECT id FROM patients WHERE user_id = :user_id');
       $stmt->execute([':user_id'=>$patientId]);
       if (!$stmt->fetch()) {
          file_put_contents($logFile, "ERROR: Patient entry not found in patients table, creating...\n", FILE_APPEND);
          $stmt = $db->prepare('INSERT INTO patients (user_id, created_at, updated_at) VALUES (:user_id, NOW(), NOW())');
          $stmt->execute([':user_id'=>$patientId]);
       }

       $stmt = $db->prepare('UPDATE patients SET assigned_doctor_id = :doctor_id, updated_at = NOW() WHERE user_id = :user_id');
       $stmt->execute([':doctor_id'=>$doctorId, ':user_id'=>$patientId]);
       
       $rowsAffected = $stmt->rowCount();
       file_put_contents($logFile, "UPDATE executed, rows affected: $rowsAffected\n", FILE_APPEND);

       // Verify the assignment
       $stmt = $db->prepare('SELECT assigned_doctor_id FROM patients WHERE user_id = :user_id');
       $stmt->execute([':user_id'=>$patientId]);
       $result = $stmt->fetch();
       file_put_contents($logFile, "Verification: assigned_doctor_id = " . ($result['assigned_doctor_id'] ?? 'NULL') . "\n\n", FILE_APPEND);

       Response::json(['success'=>true,'message'=>'Patient assigned successfully']);
    }

    public function updateUserStatus(int $userId): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $role = $auth['role'] ?? '';

        if ($role !== 'ADMIN') {
            Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Only admins can update user status']], 403);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $status = strtoupper(trim($body['status'] ?? ''));

        if (!in_array($status, ['ACTIVE', 'INACTIVE', 'SUSPENDED'])) {
            Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'Invalid status. Must be ACTIVE, INACTIVE, or SUSPENDED']], 422);
            return;
        }

        $db = DB::conn();
        $stmt = $db->prepare('SELECT id FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        if (!$stmt->fetch()) {
            Response::json(['success'=>false,'error'=>['code'=>'NOT_FOUND','message'=>'User not found']], 404);
            return;
        }

        $stmt = $db->prepare('UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $userId]);

        Response::json(['success'=>true,'message'=>'User status updated successfully']);
    }

    /**
     * Save profile picture from base64 string
     * @param string $base64Data Base64 encoded image data
     * @param string $email User email for filename
     * @return string|null Filename if successful, null otherwise
     */
    private function saveProfilePicture(string $base64Data, string $email): ?string
    {
        try {
            // Decode base64
            $imageData = base64_decode($base64Data);
            if ($imageData === false) {
                error_log("Failed to decode base64 image data");
                return null;
            }

            // Create uploads directory if it doesn't exist
            $uploadDir = __DIR__ . '/../../public/uploads/profile_pictures';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $filename = 'profile_' . md5($email . time()) . '.jpg';
            $filepath = $uploadDir . '/' . $filename;

            // Save file
            if (file_put_contents($filepath, $imageData) === false) {
                error_log("Failed to save profile picture to: " . $filepath);
                return null;
            }

            error_log("Profile picture saved successfully: " . $filename);
            return $filename;

        } catch (\Exception $e) {
            error_log("Error saving profile picture: " . $e->getMessage());
            return null;
        }
    }

    public function listDoctors(): void
    {
       $auth = $_SERVER['auth'] ?? [];
       $role = $auth['role'] ?? '';

       if ($role !== 'ADMIN') {
          Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Access denied']], 403);
          return;
       }

       $db = DB::conn();
       $stmt = $db->prepare("SELECT u.id, u.name, u.email, d.specialization
          FROM users u
          LEFT JOIN doctors d ON u.id = d.user_id
          WHERE u.role = 'DOCTOR' AND u.status = 'ACTIVE'
          ORDER BY u.name ASC");
       $stmt->execute();
       $doctors = $stmt->fetchAll();

       Response::json(['success'=>true,'data'=>$doctors]);
    }

    public function deleteUser(): void
    {
       $auth = $_SERVER['auth'] ?? [];
       $role = $auth['role'] ?? '';

       if ($role !== 'ADMIN') {
          Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Only admins can delete users']], 403);
          return;
       }

       // Get user ID from URL path
       $uri = $_SERVER['PATH_INFO'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
       if (preg_match('#^/api/v1/admin/users/(\d+)(?:/delete)?$#', $uri, $matches)) {
           $userId = (int)$matches[1];
       } else {
           Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'Invalid user ID']], 422);
           return;
       }

       if ($userId <= 0) {
          Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'Invalid user ID']], 422);
          return;
       }

       $db = DB::conn();

       // Check if user exists and get their role
       $stmt = $db->prepare('SELECT id, role, email FROM users WHERE id = :id');
       $stmt->execute([':id' => $userId]);
       $user = $stmt->fetch();

       if (!$user) {
          Response::json(['success'=>false,'error'=>['code'=>'NOT_FOUND','message'=>'User not found']], 404);
          return;
       }

       // Prevent admin from deleting themselves
       $currentUserId = (int)($auth['uid'] ?? 0);
       if ($userId === $currentUserId) {
          Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'You cannot delete your own account']], 403);
          return;
       }

       // Prevent deletion of other admin users (optional security measure)
       if ($user['role'] === 'ADMIN') {
          Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Cannot delete admin users']], 403);
          return;
       }

       try {
           $db->beginTransaction();

           // Delete related records first (due to foreign key constraints)
           if ($user['role'] === 'PATIENT') {
               // Delete patient-specific records
               $stmt = $db->prepare('DELETE FROM patients WHERE user_id = :id');
               $stmt->execute([':id' => $userId]);
               
               // Delete patient medications
               $stmt = $db->prepare('DELETE FROM patient_medications WHERE patient_id = :id');
               $stmt->execute([':id' => $userId]);
               
               // Delete patient reports
               $stmt = $db->prepare('DELETE FROM reports WHERE patient_id = :id');
               $stmt->execute([':id' => $userId]);
               
               // Delete patient symptoms
               $stmt = $db->prepare('DELETE FROM symptoms WHERE patient_id = :id');
               $stmt->execute([':id' => $userId]);
               
               // Delete patient notifications
               $stmt = $db->prepare('DELETE FROM notifications WHERE user_id = :id');
               $stmt->execute([':id' => $userId]);
               
               // Delete patient appointments
               $stmt = $db->prepare('DELETE FROM appointments WHERE patient_id = :id');
               $stmt->execute([':id' => $userId]);
               
               // Delete patient rehab assignments
               $stmt = $db->prepare('DELETE FROM patient_rehab_assignment WHERE patient_id = :id');
               $stmt->execute([':id' => $userId]);
           }

           if ($user['role'] === 'DOCTOR') {
               // Delete doctor-specific records
               $stmt = $db->prepare('DELETE FROM doctors WHERE user_id = :id');
               $stmt->execute([':id' => $userId]);
               
               // Update patients assigned to this doctor (set to null)
               $stmt = $db->prepare('UPDATE patients SET assigned_doctor_id = NULL WHERE assigned_doctor_id = :id');
               $stmt->execute([':id' => $userId]);
               
               // Delete doctor appointments
               $stmt = $db->prepare('DELETE FROM appointments WHERE doctor_id = :id');
               $stmt->execute([':id' => $userId]);
           }

           // Finally, delete the user record
           $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
           $stmt->execute([':id' => $userId]);

           $db->commit();

           Response::json([
               'success' => true,
               'message' => ucfirst(strtolower($user['role'])) . ' deleted successfully',
               'data' => [
                   'deleted_user_id' => $userId,
                   'deleted_user_email' => $user['email'],
                   'deleted_user_role' => $user['role']
               ]
           ]);

       } catch (\Throwable $e) {
           $db->rollBack();
           error_log("Delete User Failed: " . $e->getMessage());
           Response::json([
               'success' => false,
               'error' => [
                   'code' => 'DELETION_FAILED',
                   'message' => 'Failed to delete user. ' . $e->getMessage()
               ]
           ], 500);
       }
    }

    public function getUserById(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $role = $auth['role'] ?? '';

        if ($role !== 'ADMIN') {
            Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Only admins can view user details']], 403);
            return;
        }

        // Get user ID from URL path
        $uri = $_SERVER['PATH_INFO'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (preg_match('#^/api/v1/admin/users/(\d+)$#', $uri, $matches)) {
            $userId = (int)$matches[1];
        } else {
            Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'Invalid user ID']], 422);
            return;
        }

        if ($userId <= 0) {
            Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'Invalid user ID']], 422);
            return;
        }

        $db = DB::conn();

        try {
            // Get user basic info (only columns that exist in users table)
            $stmt = $db->prepare('SELECT id, name, email, role, phone, IFNULL(avatar_url, \'\') AS avatar_url, status, created_at, updated_at FROM users WHERE id = :id');
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();

            if (!$user) {
                Response::json(['success'=>false,'error'=>['code'=>'NOT_FOUND','message'=>'User not found']], 404);
                return;
            }

            // Get role-specific information
            if ($user['role'] === 'PATIENT') {
                $stmt = $db->prepare('SELECT address, age, gender, assigned_doctor_id FROM patients WHERE user_id = :user_id');
                $stmt->execute([':user_id' => $userId]);
                $patientInfo = $stmt->fetch();
                
                if ($patientInfo) {
                    $user['address'] = $patientInfo['address'];
                    $user['age'] = $patientInfo['age'];
                    $user['gender'] = $patientInfo['gender'];
                    $user['assigned_doctor_id'] = $patientInfo['assigned_doctor_id'];
                }
            } elseif ($user['role'] === 'DOCTOR') {
                $stmt = $db->prepare('SELECT specialization FROM doctors WHERE user_id = :user_id');
                $stmt->execute([':user_id' => $userId]);
                $doctorInfo = $stmt->fetch();
                
                if ($doctorInfo) {
                    $user['specialization'] = $doctorInfo['specialization'];
                }
            }

            Response::json(['success'=>true,'data'=>$user]);

        } catch (\Throwable $e) {
            error_log("Get User By ID Failed: " . $e->getMessage());
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'FETCH_FAILED',
                    'message' => 'Failed to fetch user details'
                ]
            ], 500);
        }
    }

    public function updateUser(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $role = $auth['role'] ?? '';

        if ($role !== 'ADMIN') {
            Response::json(['success'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'Only admins can update users']], 403);
            return;
        }

        // Get user ID from URL path
        $uri = $_SERVER['PATH_INFO'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (preg_match('#^/api/v1/admin/users/(\d+)$#', $uri, $matches)) {
            $userId = (int)$matches[1];
        } else {
            Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'Invalid user ID']], 422);
            return;
        }

        if ($userId <= 0) {
            Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'Invalid user ID']], 422);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $name = trim($body['name'] ?? '');
        $email = trim(strtolower($body['email'] ?? ''));
        $mobile = trim($body['mobile'] ?? '');
        $address = trim($body['address'] ?? '');
        $age = trim($body['age'] ?? '');
        $dateOfBirth = trim($body['dateOfBirth'] ?? '');
        $gender = trim($body['gender'] ?? '');
        $specialization = trim($body['specialization'] ?? ''); // For doctors
        $profilePicture = $body['profilePicture'] ?? null; // Base64 profile picture

        if (!$name || !$email) {
            Response::json(['success'=>false,'error'=>['code'=>'VALIDATION','message'=>'Name and email are required']], 422);
            return;
        }

        $db = DB::conn();

        try {
            // Check if user exists and get their role
            $stmt = $db->prepare('SELECT id, role, email FROM users WHERE id = :id');
            $stmt->execute([':id' => $userId]);
            $existingUser = $stmt->fetch();

            if (!$existingUser) {
                Response::json(['success'=>false,'error'=>['code'=>'NOT_FOUND','message'=>'User not found']], 404);
                return;
            }

            // Check if email is being changed and if new email already exists
            if ($email !== $existingUser['email']) {
                $stmt = $db->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
                $stmt->execute([':email' => $email, ':id' => $userId]);
                if ($stmt->fetch()) {
                    Response::json(['success'=>false,'error'=>['code'=>'EMAIL_TAKEN','message'=>'This mail ID already exists']], 409);
                    return;
                }
            }

            $db->beginTransaction();

            // Process profile picture if provided
            $profilePictureFilename = null;
            if ($profilePicture) {
                // Remove data URI prefix if present
                if (strpos($profilePicture, 'data:image') === 0) {
                    $parts = explode(',', $profilePicture);
                    if (count($parts) > 1) {
                        $profilePicture = $parts[1];
                    }
                }
                $profilePictureFilename = $this->saveProfilePicture($profilePicture, $email);
            }

            // Update users table
            if ($profilePictureFilename) {
                try {
                    $stmt = $db->prepare('UPDATE users SET name = :name, email = :email, phone = :phone, avatar_url = :avatar_url, updated_at = NOW() WHERE id = :id');
                    $stmt->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':phone' => $mobile,
                        ':avatar_url' => $profilePictureFilename,
                        ':id' => $userId
                    ]);
                } catch (\PDOException $e) {
                    // College server may not have avatar_url — update without it
                    $stmt = $db->prepare('UPDATE users SET name = :name, email = :email, phone = :phone, updated_at = NOW() WHERE id = :id');
                    $stmt->execute([':name' => $name, ':email' => $email, ':phone' => $mobile, ':id' => $userId]);
                }
            } else {
                $stmt = $db->prepare('UPDATE users SET name = :name, email = :email, phone = :phone, updated_at = NOW() WHERE id = :id');
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':phone' => $mobile,
                    ':id' => $userId
                ]);
            }

            // Update role-specific tables
            if ($existingUser['role'] === 'PATIENT') {
                // Update patients table using user_id
                // Convert empty age to NULL
                $ageValue = !empty($age) ? (int)$age : null;
                
                $genderValue = !empty($gender) ? strtoupper($gender) : null;
                $stmt = $db->prepare('UPDATE patients SET address = :address, age = :age, gender = :gender, updated_at = NOW() WHERE user_id = :user_id');
                $stmt->execute([
                    ':address' => $address,
                    ':age' => $ageValue,
                    ':gender' => $genderValue,
                    ':user_id' => $userId
                ]);
            } elseif ($existingUser['role'] === 'DOCTOR') {
                // Update doctors table using user_id
                $stmt = $db->prepare('UPDATE doctors SET specialization = :specialization, updated_at = NOW() WHERE user_id = :user_id');
                $stmt->execute([
                    ':specialization' => $specialization,
                    ':user_id' => $userId
                ]);
            }

            $db->commit();

            // Fetch updated user data to return
            $stmt = $db->prepare('SELECT id, name, email, role, phone, IFNULL(avatar_url, \'\') AS avatar_url, created_at, updated_at FROM users WHERE id = :id');
            $stmt->execute([':id' => $userId]);
            $updatedUser = $stmt->fetch();

            // Add role-specific data
            if ($existingUser['role'] === 'PATIENT') {
                $stmt = $db->prepare('SELECT address, age, gender, assigned_doctor_id FROM patients WHERE user_id = :user_id');
                $stmt->execute([':user_id' => $userId]);
                $patientInfo = $stmt->fetch();
                
                if ($patientInfo) {
                    $updatedUser['address'] = $patientInfo['address'];
                    $updatedUser['age'] = $patientInfo['age'];
                    $updatedUser['gender'] = $patientInfo['gender'];
                    $updatedUser['assigned_doctor_id'] = $patientInfo['assigned_doctor_id'];
                }
            } elseif ($existingUser['role'] === 'DOCTOR') {
                $stmt = $db->prepare('SELECT specialization FROM doctors WHERE user_id = :user_id');
                $stmt->execute([':user_id' => $userId]);
                $doctorInfo = $stmt->fetch();
                
                if ($doctorInfo) {
                    $updatedUser['specialization'] = $doctorInfo['specialization'];
                }
            }

            Response::json(['success'=>true,'data'=>$updatedUser,'message'=>'User updated successfully']);

        } catch (\Throwable $e) {
            $db->rollBack();
            error_log("Update User Failed: " . $e->getMessage());
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'UPDATE_FAILED',
                    'message' => 'Failed to update user. ' . $e->getMessage()
                ]
            ], 500);
        }
    }
}
