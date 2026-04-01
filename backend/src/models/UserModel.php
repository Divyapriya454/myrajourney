<?php
declare(strict_types=1);

namespace Src\Models;

use PDO;
use Src\Config\DB;

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::conn();
    }

    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));

        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        // Try with avatar_url first; fall back without it if column doesn't exist
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO users
                    (email, phone, password, role, name, avatar_url, status, created_at, updated_at)
                 VALUES
                    (:email, :phone, :password, :role, :name, :avatar_url, :status, NOW(), NOW())'
            );
            $stmt->execute([
                ':email'      => strtolower(trim($data['email'])),
                ':phone'      => $data['phone'] ?? null,
                ':password'   => $data['password'] ?? $data['password_hash'] ?? null,
                ':role'       => $data['role'],
                ':name'       => $data['name'] ?? null,
                ':avatar_url' => $data['avatar_url'] ?? null,
                ':status'     => 'ACTIVE',
            ]);
        } catch (\PDOException $e) {
            // College server may not have avatar_url column — retry without it
            if (strpos($e->getMessage(), 'avatar_url') !== false || strpos($e->getMessage(), '1054') !== false) {
                $stmt = $this->db->prepare(
                    'INSERT INTO users
                        (email, phone, password, role, name, status, created_at, updated_at)
                     VALUES
                        (:email, :phone, :password, :role, :name, :status, NOW(), NOW())'
                );
                $stmt->execute([
                    ':email'    => strtolower(trim($data['email'])),
                    ':phone'    => $data['phone'] ?? null,
                    ':password' => $data['password'] ?? $data['password_hash'] ?? null,
                    ':role'     => $data['role'],
                    ':name'     => $data['name'] ?? null,
                    ':status'   => 'ACTIVE',
                ]);
            } else {
                throw $e;
            }
        }

        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT id, email, phone, role, name, avatar_url, status,
                        date_of_birth, address, gender, last_login_at, created_at, updated_at
                 FROM users WHERE id = :id'
            );
            $stmt->execute([':id' => $id]);
        } catch (\PDOException $e) {
            // Fallback for servers missing some columns
            $stmt = $this->db->prepare(
                'SELECT id, email, phone, role, name, status, created_at, updated_at
                 FROM users WHERE id = :id'
            );
            $stmt->execute([':id' => $id]);
        }

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setLastLogin(int $id): void
    {
        try {
            $stmt = $this->db->prepare(
                'UPDATE users SET last_login_at = NOW() WHERE id = :id'
            );
            $stmt->execute([':id' => $id]);
        } catch (\PDOException $e) {
            // Log the error but don't fail the login process
            error_log("Failed to update last login time for user $id: " . $e->getMessage());
        }
    }

    public function updateMe(int $id, array $data): void
    {
        error_log("UpdateMe called for user ID: $id");
        error_log("UpdateMe data: " . json_encode(array_keys($data)));
        
        // Get current user email for comparison
        $currentUser = $this->findById($id);
        $currentEmail = $currentUser['email'] ?? '';
        
        // Handle profile picture if provided
        $avatarUrl = null;
        if (!empty($data['profilePicture'])) {
            error_log("Processing profile picture upload");
            $emailForFilename = !empty($data['email']) ? $data['email'] : $currentEmail;
            $avatarUrl = $this->saveProfilePicture($data['profilePicture'], $emailForFilename);
            if ($avatarUrl) {
                error_log("Profile picture saved: $avatarUrl");
            } else {
                error_log("Failed to save profile picture");
            }
        }
        
        // Prepare email value - use current email if not provided
        $emailToUpdate = !empty($data['email']) ? strtolower(trim($data['email'])) : $currentEmail;
        
        $dobValue = !empty($data['date_of_birth']) ? $data['date_of_birth'] : null;

        // Update users table (address stored here for all roles)
        if ($avatarUrl) {
            $stmt = $this->db->prepare(
                'UPDATE users
                 SET name = :name, phone = :phone, email = :email,
                     date_of_birth = :dob, address = :address, avatar_url = :avatar_url,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                ':name'       => $data['name'] ?? null,
                ':phone'      => $data['phone'] ?? null,
                ':email'      => $emailToUpdate,
                ':dob'        => $dobValue,
                ':address'    => $data['address'] ?? null,
                ':avatar_url' => $avatarUrl,
                ':id'         => $id
            ]);
            error_log("Users table updated with avatar_url");
        } else {
            $stmt = $this->db->prepare(
                'UPDATE users
                 SET name = :name, phone = :phone, email = :email,
                     date_of_birth = :dob, address = :address,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                ':name'    => $data['name'] ?? null,
                ':phone'   => $data['phone'] ?? null,
                ':email'   => $emailToUpdate,
                ':dob'     => $dobValue,
                ':address' => $data['address'] ?? null,
                ':id'      => $id
            ]);
            error_log("Users table updated without avatar_url");
        }

        // Also sync address/age to patients table if patient record exists
        if (!empty($data['address']) || !empty($data['age'])) {
            $checkStmt = $this->db->prepare('SELECT id FROM patients WHERE user_id = :user_id');
            $checkStmt->execute([':user_id' => $id]);
            if ($checkStmt->fetch()) {
                $stmt = $this->db->prepare(
                    'UPDATE patients SET address = :address, age = :age WHERE user_id = :user_id'
                );
                $stmt->execute([
                    ':address' => $data['address'] ?? null,
                    ':age'     => !empty($data['age']) ? (int)$data['age'] : null,
                    ':user_id' => $id
                ]);
                error_log("Patients table synced");
            }
        }
        
        error_log("UpdateMe completed successfully");
    }
    
    private function saveProfilePicture(string $base64Data, string $email): ?string
    {
        try {
            // Remove data URI prefix if present
            if (strpos($base64Data, 'data:image') === 0) {
                $parts = explode(',', $base64Data);
                if (count($parts) > 1) {
                    $base64Data = $parts[1];
                }
            }
            
            // Decode base64
            $imageData = base64_decode($base64Data);
            if ($imageData === false) {
                error_log("Failed to decode base64 image data");
                return null;
            }
            
            // Create directory if it doesn't exist
            $uploadDir = __DIR__ . '/../../public/uploads/profile_pictures';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $filename = 'profile_' . md5($email . time()) . '.jpg';
            $filepath = $uploadDir . '/' . $filename;
            
            // Save file
            if (file_put_contents($filepath, $imageData) === false) {
                error_log("Failed to save profile picture to: $filepath");
                return null;
            }
            
            return $filename;
        } catch (\Exception $e) {
            error_log("Error saving profile picture: " . $e->getMessage());
            return null;
        }
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET password = :password, updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            ':password' => $passwordHash,
            ':id'       => $id
        ]);
    }
}
