<?php
/**
 * Update Notification Preferences API Endpoint
 * POST /api/notifications/update-preferences.php
 */

require_once '../../src/config/db.php';
require_once '../../src/middlewares/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Authenticate user
    $user = authenticate();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit();
    }
    
    // Validate notification type
    $validTypes = ['MEDICATION', 'APPOINTMENT', 'EXERCISE', 'CRP_REMINDER', 'GENERAL'];
    if (!isset($input['notification_type']) || !in_array($input['notification_type'], $validTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid notification type']);
        exit();
    }
    
    $notificationType = $input['notification_type'];
    
    // Connect to database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Prepare update data with defaults
    $updateData = [
        'enabled' => isset($input['enabled']) ? (bool)$input['enabled'] : true,
        'sound_enabled' => isset($input['sound_enabled']) ? (bool)$input['sound_enabled'] : true,
        'vibration_enabled' => isset($input['vibration_enabled']) ? (bool)$input['vibration_enabled'] : true,
        'led_enabled' => isset($input['led_enabled']) ? (bool)$input['led_enabled'] : false,
        'snooze_duration_minutes' => isset($input['snooze_duration_minutes']) ? max(0, min(120, (int)$input['snooze_duration_minutes'])) : 10,
        'max_snooze_count' => isset($input['max_snooze_count']) ? max(0, min(10, (int)$input['max_snooze_count'])) : 3,
        'reminder_advance_minutes' => isset($input['reminder_advance_minutes']) ? max(0, min(1440, (int)$input['reminder_advance_minutes'])) : 15
    ];\n    \n    // Check if preference already exists\n    $checkStmt = $pdo->prepare(\"\n        SELECT id FROM notification_preferences \n        WHERE user_id = ? AND notification_type = ?\n    \");\n    $checkStmt->execute([$user['id'], $notificationType]);\n    $exists = $checkStmt->fetch();\n    \n    if ($exists) {\n        // Update existing preference\n        $updateStmt = $pdo->prepare(\"\n            UPDATE notification_preferences \n            SET enabled = ?, sound_enabled = ?, vibration_enabled = ?, led_enabled = ?,\n                snooze_duration_minutes = ?, max_snooze_count = ?, reminder_advance_minutes = ?,\n                updated_at = NOW()\n            WHERE user_id = ? AND notification_type = ?\n        \");\n        \n        $updateStmt->execute([\n            $updateData['enabled'],\n            $updateData['sound_enabled'],\n            $updateData['vibration_enabled'],\n            $updateData['led_enabled'],\n            $updateData['snooze_duration_minutes'],\n            $updateData['max_snooze_count'],\n            $updateData['reminder_advance_minutes'],\n            $user['id'],\n            $notificationType\n        ]);\n        \n        $message = 'Notification preferences updated successfully';\n    } else {\n        // Insert new preference\n        $insertStmt = $pdo->prepare(\"\n            INSERT INTO notification_preferences \n            (user_id, notification_type, enabled, sound_enabled, vibration_enabled, led_enabled,\n             snooze_duration_minutes, max_snooze_count, reminder_advance_minutes, created_at, updated_at)\n            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())\n        \");\n        \n        $insertStmt->execute([\n            $user['id'],\n            $notificationType,\n            $updateData['enabled'],\n            $updateData['sound_enabled'],\n            $updateData['vibration_enabled'],\n            $updateData['led_enabled'],\n            $updateData['snooze_duration_minutes'],\n            $updateData['max_snooze_count'],\n            $updateData['reminder_advance_minutes']\n        ]);\n        \n        $message = 'Notification preferences created successfully';\n    }\n    \n    // Fetch updated preference\n    $fetchStmt = $pdo->prepare(\"\n        SELECT \n            notification_type,\n            enabled,\n            sound_enabled,\n            vibration_enabled,\n            led_enabled,\n            snooze_duration_minutes,\n            max_snooze_count,\n            reminder_advance_minutes,\n            updated_at\n        FROM notification_preferences \n        WHERE user_id = ? AND notification_type = ?\n    \");\n    $fetchStmt->execute([$user['id'], $notificationType]);\n    $preference = $fetchStmt->fetch();\n    \n    // Format response\n    $formattedPref = [\n        'notification_type' => $preference['notification_type'],\n        'enabled' => (bool)$preference['enabled'],\n        'sound_enabled' => (bool)$preference['sound_enabled'],\n        'vibration_enabled' => (bool)$preference['vibration_enabled'],\n        'led_enabled' => (bool)$preference['led_enabled'],\n        'snooze_duration_minutes' => (int)$preference['snooze_duration_minutes'],\n        'max_snooze_count' => (int)$preference['max_snooze_count'],\n        'reminder_advance_minutes' => (int)$preference['reminder_advance_minutes'],\n        'updated_at' => $preference['updated_at']\n    ];\n    \n    echo json_encode([\n        'success' => true,\n        'message' => $message,\n        'data' => $formattedPref\n    ]);\n    \n} catch (PDOException $e) {\n    error_log(\"Database error in update-preferences.php: \" . $e->getMessage());\n    http_response_code(500);\n    echo json_encode(['success' => false, 'message' => 'Database error occurred']);\n} catch (Exception $e) {\n    error_log(\"Error in update-preferences.php: \" . $e->getMessage());\n    http_response_code(500);\n    echo json_encode(['success' => false, 'message' => 'An error occurred while updating notification preferences']);\n}\n?>"
