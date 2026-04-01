<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Models\UserModel;
use Src\Utils\Response;

class UserController
{
    private UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    public function updateMe(): void
    {
        $auth = $_SERVER['auth'] ?? [];
        $uid = (int)($auth['uid'] ?? 0);

        error_log("UpdateMe endpoint called for user ID: $uid");

        if ($uid <= 0) {
            error_log("UpdateMe: Unauthorized - no valid user ID");
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
        error_log("UpdateMe: Request body keys: " . json_encode(array_keys($body)));

        // Check for duplicate email if email is being changed
        if (!empty($body['email'])) {
            $existingUser = $this->users->findByEmail($body['email']);
            if ($existingUser && (int)$existingUser['id'] !== $uid) {
                error_log("UpdateMe: Duplicate email detected: " . $body['email']);
                Response::json([
                    'success' => false,
                    'message' => 'This mail ID already exists',
                    'error' => [
                        'code' => 'DUPLICATE_EMAIL',
                        'message' => 'This mail ID already exists'
                    ]
                ], 400);
                return;
            }
        }

        try {
            $this->users->updateMe($uid, $body);
            $user = $this->users->findById($uid);

            error_log("UpdateMe: Profile updated successfully for user ID: $uid");
            Response::json([
                'success' => true,
                'data' => ['user' => $user],
                'message' => 'Profile updated successfully'
            ]);
        } catch (\PDOException $e) {
            error_log("UpdateMe: PDO Exception - " . $e->getMessage());
            
            // Check for duplicate email error
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                Response::json([
                    'success' => false,
                    'message' => 'This mail ID already exists',
                    'error' => [
                        'code' => 'DUPLICATE_EMAIL',
                        'message' => 'This mail ID already exists'
                    ]
                ], 400);
            } else {
                Response::json([
                    'success' => false,
                    'message' => 'Failed to update profile: ' . $e->getMessage(),
                    'error' => [
                        'code' => 'UPDATE_FAILED',
                        'message' => 'Failed to update profile'
                    ]
                ], 500);
            }
        } catch (\Exception $e) {
            error_log("UpdateMe: General Exception - " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'An error occurred while updating profile'
                ]
            ], 500);
        }
    }
}
