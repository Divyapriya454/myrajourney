<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Utils\Response;
use Src\Models\UserModel;
use Src\Utils\Jwt;

class AuthController
{
    private UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    // REGISTRATION
    public function register(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $name = trim($body['name'] ?? '');
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        $phone = trim($body['phone'] ?? '');
        $role = strtoupper($body['role'] ?? 'PATIENT');
        
        // Validate required fields
        if (!$name || !$email) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION',
                    'message' => 'Name and email are required'
                ]
            ], 422);
            return;
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION',
                    'message' => 'Invalid email format'
                ]
            ], 422);
            return;
        }
        
        if (!in_array($role, ['PATIENT', 'DOCTOR', 'ADMIN'], true)) {
            $role = 'PATIENT';
        }
        
        // Use default password if not provided
        if (empty($password)) {
            $password = \Src\Utils\DefaultPasswords::getDefaultPassword($role);
        }
        
        try {
            $userId = $this->users->create([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'phone' => $phone,
                'role' => $role,
                'status' => 'ACTIVE'
            ]);
            
            $db = \Src\Config\DB::conn();
            if ($role === 'PATIENT') {
                $stmt = $db->prepare("INSERT INTO patients (user_id) VALUES (?)");
                $stmt->execute([$userId]);
            } elseif ($role === 'DOCTOR') {
                $stmt = $db->prepare("INSERT INTO doctors (user_id) VALUES (?)");
                $stmt->execute([$userId]);
            }
            
            Response::json([
                'success' => true,
                'message' => 'Registration successful. Please login.',
                'data' => [
                    'user_id' => $userId
                ]
            ], 201);
            
        } catch (\Exception $e) {
            error_log("Registration Error: " . $e->getMessage());
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                Response::json([
                    'success' => false,
                    'error' => [
                        'code' => 'DUPLICATE_EMAIL',
                        'message' => 'Email already exists'
                    ]
                ], 409);
            } else {
                Response::json([
                    'success' => false,
                    'error' => [
                        'code' => 'SERVER_ERROR',
                        'message' => 'Registration failed: ' . $e->getMessage()
                    ]
                ], 500);
            }
        }
    }

    // LOGIN
    public function login(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $email = trim(strtolower($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        $user = $this->users->findByEmail($email);

        // CASE 1: Email does NOT exist → must return 404
        if (!$user) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Invalid username/password'
                ]
            ], 401);
            return;
        }

        // CASE 2: Email exists but password wrong → 401
        // Handle both 'password' and 'password_hash' column names
        $passwordHash = $user['password'] ?? $user['password_hash'] ?? '';
        
        if (!$passwordHash || !password_verify($password, $passwordHash)) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Invalid username/password'
                ]
            ], 401);
            return;
        }

        // CASE 3: Account not active
        if ($user['status'] !== 'ACTIVE') {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'ACCOUNT_SUSPENDED',
                    'message' => 'Your account has been suspended. Contact administrator.'
                ]
            ], 403);
            return;
        }

        // LOGIN SUCCESS
        $token = $this->issueToken((int)$user['id'], $user['role']);

        // Try to update last login time, but don't let it block the login process
        try {
            $this->users->setLastLogin((int)$user['id']);
        } catch (\Exception $e) {
            // Log the error but continue with login
            error_log("Warning: Could not update last login time: " . $e->getMessage());
        }

        unset($user['password_hash']);

        Response::json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $user
            ]
        ]);
    }

    // GET CURRENT USER
    public function me(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);
        $user = $this->users->findById($uid);

        if (!$user) {
            Response::json(['success' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'User not found']], 404);
            return;
        }

        // Enrich with patient age if applicable (address is now in users table for all roles)
        if (($user['role'] ?? '') === 'PATIENT') {
            try {
                $db = \Src\Config\DB::conn();
                $stmt = $db->prepare('SELECT age FROM patients WHERE user_id = :uid');
                $stmt->execute([':uid' => $uid]);
                $patient = $stmt->fetch();
                if ($patient) {
                    $user['age'] = $patient['age'];
                    // Only override address from patients if users.address is empty
                    if (empty($user['address'])) {
                        $stmt2 = $db->prepare('SELECT address FROM patients WHERE user_id = :uid');
                        $stmt2->execute([':uid' => $uid]);
                        $p2 = $stmt2->fetch();
                        if ($p2 && !empty($p2['address'])) {
                            $user['address'] = $p2['address'];
                        }
                    }
                }
            } catch (\Throwable $e) {
                error_log("me(): failed to fetch patient data: " . $e->getMessage());
            }
        }

        Response::json([
            'success' => true,
            'data' => ['user' => $user]
        ]);
    }

    public function deleteAccount(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);

        if ($uid <= 0) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Unauthorized'
                ]
            ], 401);
            return;
        }

        $db = \Src\Config\DB::conn();

        try {
            $stmt = $db->prepare('SELECT id, role FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $uid]);
            $user = $stmt->fetch();

            if (!$user) {
                Response::json([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'User not found'
                    ]
                ], 404);
                return;
            }

            $db->beginTransaction();

            $this->deleteUserRelations($db, $uid, (string)($user['role'] ?? ''));

            $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute([':id' => $uid]);

            $db->commit();

            Response::json([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log('Delete Account Error: ' . $e->getMessage());
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'DELETE_FAILED',
                    'message' => 'Failed to delete account'
                ]
            ], 500);
        }
    }

    private function deleteUserRelations(\PDO $db, int $userId, string $role): void
    {
        $deleteIfTableExists = function (string $table, string $column) use ($db, $userId): void {
            try {
                $check = $db->prepare('SHOW TABLES LIKE :table');
                $check->execute([':table' => $table]);
                if (!$check->fetchColumn()) {
                    return;
                }

                $stmt = $db->prepare("DELETE FROM `$table` WHERE `$column` = :user_id");
                $stmt->execute([':user_id' => $userId]);
            } catch (\Throwable $e) {
                error_log("Delete relation skipped for {$table}.{$column}: " . $e->getMessage());
            }
        };

        $updateIfTableExists = function (string $table, string $column) use ($db, $userId): void {
            try {
                $check = $db->prepare('SHOW TABLES LIKE :table');
                $check->execute([':table' => $table]);
                if (!$check->fetchColumn()) {
                    return;
                }

                $stmt = $db->prepare("UPDATE `$table` SET `$column` = NULL WHERE `$column` = :user_id");
                $stmt->execute([':user_id' => $userId]);
            } catch (\Throwable $e) {
                error_log("Update relation skipped for {$table}.{$column}: " . $e->getMessage());
            }
        };

        $deleteIfTableExists('notifications', 'user_id');
        $deleteIfTableExists('settings', 'user_id');
        $deleteIfTableExists('chatbot_conversations', 'user_id');

        if ($role === 'PATIENT') {
            $deleteIfTableExists('patients', 'user_id');
            $deleteIfTableExists('appointments', 'patient_id');
            $deleteIfTableExists('patient_medications', 'patient_id');
            $deleteIfTableExists('medication_logs', 'patient_id');
            $deleteIfTableExists('symptoms', 'patient_id');
            $deleteIfTableExists('health_metrics', 'patient_id');
            $deleteIfTableExists('crp_measurements', 'patient_id');
            $deleteIfTableExists('reports', 'patient_id');
            $deleteIfTableExists('patient_rehab_assignment', 'patient_id');
        } elseif ($role === 'DOCTOR') {
            $deleteIfTableExists('doctors', 'user_id');
            $deleteIfTableExists('appointments', 'doctor_id');
            $deleteIfTableExists('patient_medications', 'doctor_id');
            $deleteIfTableExists('patient_medications', 'prescribed_by');
            $deleteIfTableExists('crp_measurements', 'doctor_id');
            $deleteIfTableExists('reports', 'doctor_id');
            $updateIfTableExists('patients', 'assigned_doctor_id');
        }
    }

    private function issueToken(int $uid, string $role): string
    {
        $ttl = (int)($_ENV['JWT_TTL_SECONDS'] ?? 604800);

        $payload = [
            'uid' => $uid,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + $ttl
        ];

        return Jwt::encode($payload, $_ENV['JWT_SECRET'] ?? '');
    }

    // FORGOT PASSWORD - ENABLED FOR DIRECT RESET
    public function forgotPassword(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $email = trim(strtolower($body['email'] ?? ''));

        // Validate email
        if (!$email) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION',
                    'message' => 'Email is required'
                ]
            ], 422);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION',
                    'message' => 'Invalid email format'
                ]
            ], 422);
            return;
        }

        // Check if user exists
        $user = $this->users->findByEmail($email);
        if (!$user) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'EMAIL_NOT_FOUND',
                    'message' => 'Email not found'
                ]
            ], 404);
            return;
        }

        // For this app, we'll provide instructions to use the reset password flow
        Response::json([
            'success' => true,
            'message' => 'Password reset instructions sent',
            'data' => [
                'message' => 'Use the reset password option with your email and new password',
                'resetToken' => 'direct_reset_available',
                'expiresAt' => time() + 3600 // 1 hour
            ]
        ]);
    }

    // RESET PASSWORD — EMAIL ONLY
    public function resetPassword(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $email = trim(strtolower($body['email'] ?? ''));
        $newPass = (string)($body['password'] ?? '');

        // Email required
        if (!$email) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION',
                    'message' => 'Email is required'
                ]
            ], 422);
            return;
        }

        // Email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION',
                    'message' => 'Invalid email format'
                ]
            ], 422);
            return;
        }

        // Password rule
        if (strlen($newPass) < 8) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION',
                    'message' => 'Password must be at least 8 characters long'
                ]
            ], 422);
            return;
        }

        // Check email exists
        $user = $this->users->findByEmail($email);
        if (!$user) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_EMAIL',
                    'message' => 'Email not found'
                ]
            ], 404);
            return;
        }

        // Update password
        $this->users->updatePassword(
            (int)$user['id'],
            password_hash($newPass, PASSWORD_BCRYPT)
        );

        Response::json([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    }

    // CHANGE PASSWORD (LOGGED USERS)
    public function changePassword(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);

        if ($uid <= 0) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required'
                ]
            ], 401);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $oldPassword = (string)($body['old_password'] ?? $body['current_password'] ?? '');
        $newPassword = (string)($body['new_password'] ?? '');

        // Validation
        if (!$oldPassword || !$newPassword) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION',
                    'message' => 'Old and new passwords required'
                ]
            ], 422);
            return;
        }

        if (strlen($newPassword) < 6) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION',
                    'message' => 'New password must be at least 6 characters long'
                ]
            ], 422);
            return;
        }

        $user = $this->users->findById($uid);
        // findById doesn't return password — fetch it separately
        $db = \Src\Config\DB::conn();
        $stmt = $db->prepare('SELECT password FROM users WHERE id = :id');
        $stmt->execute([':id' => $uid]);
        $pwRow = $stmt->fetch();
        $storedHash = $pwRow['password'] ?? '';

        if (!$user || !password_verify($oldPassword, $storedHash)) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_PASSWORD',
                    'message' => 'Incorrect current password'
                ]
            ], 401);
            return;
        }

        $this->users->updatePassword(
            $uid,
            password_hash($newPassword, PASSWORD_BCRYPT)
        );

        Response::json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }
}
