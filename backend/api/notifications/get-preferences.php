<?php
/**
 * Get Notification Preferences API Endpoint
 * GET /api/notifications/get-preferences.php
 */

require_once '../../src/config/db.php';
require_once '../../src/middlewares/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    
    // Connect to database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Get user's notification preferences
    $prefsStmt = $pdo->prepare("
        SELECT 
            notification_type,
            enabled,
            sound_enabled,
            vibration_enabled,
            led_enabled,
            snooze_duration_minutes,
            max_snooze_count,
            reminder_advance_minutes,
            created_at,
            updated_at
        FROM notification_preferences 
        WHERE user_id = ?
        ORDER BY notification_type
    ");
    $prefsStmt->execute([$user['id']]);
    $preferences = $prefsStmt->fetchAll();
    
    // If no preferences exist, create default ones
    if (empty($preferences)) {
        $defaultPrefs = [
            'MEDICATION' => [
                'enabled' => true,
                'sound_enabled' => true,
                'vibration_enabled' => true,
                'led_enabled' => true,
                'snooze_duration_minutes' => 10,
                'max_snooze_count' => 3,
                'reminder_advance_minutes' => 15
            ],
            'APPOINTMENT' => [
                'enabled' => true,
                'sound_enabled' => true,
                'vibration_enabled' => true,
                'led_enabled' => false,
                'snooze_duration_minutes' => 15,
                'max_snooze_count' => 2,
                'reminder_advance_minutes' => 60
            ],
            'EXERCISE' => [
                'enabled' => true,
                'sound_enabled' => false,
                'vibration_enabled' => true,
                'led_enabled' => false,
                'snooze_duration_minutes' => 30,
                'max_snooze_count' => 2,
                'reminder_advance_minutes' => 0
            ],
            'CRP_REMINDER' => [
                'enabled' => true,
                'sound_enabled' => false,
                'vibration_enabled' => false,
                'led_enabled' => false,
                'snooze_duration_minutes' => 60,
                'max_snooze_count' => 1,
                'reminder_advance_minutes' => 0
            ],
            'GENERAL' => [
                'enabled' => true,
                'sound_enabled' => false,
                'vibration_enabled' => false,
                'led_enabled' => false,
                'snooze_duration_minutes' => 0,
                'max_snooze_count' => 0,
                'reminder_advance_minutes' => 0
            ]
        ];
        
        // Insert default preferences
        $insertStmt = $pdo->prepare("
            INSERT INTO notification_preferences 
            (user_id, notification_type, enabled, sound_enabled, vibration_enabled, led_enabled, 
             snooze_duration_minutes, max_snooze_count, reminder_advance_minutes, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        foreach ($defaultPrefs as $type => $prefs) {
            $insertStmt->execute([
                $user['id'],
                $type,
                $prefs['enabled'],
                $prefs['sound_enabled'],
                $prefs['vibration_enabled'],
                $prefs['led_enabled'],
                $prefs['snooze_duration_minutes'],
                $prefs['max_snooze_count'],
                $prefs['reminder_advance_minutes']
            ]);
        }
        
        // Fetch the newly created preferences
        $prefsStmt->execute([$user['id']]);
        $preferences = $prefsStmt->fetchAll();
    }
    
    // Format preferences for response
    $formattedPrefs = [];
    foreach ($preferences as $pref) {
        $formattedPrefs[$pref['notification_type']] = [
            'enabled' => (bool)$pref['enabled'],
            'sound_enabled' => (bool)$pref['sound_enabled'],
            'vibration_enabled' => (bool)$pref['vibration_enabled'],
            'led_enabled' => (bool)$pref['led_enabled'],
            'snooze_duration_minutes' => (int)$pref['snooze_duration_minutes'],
            'max_snooze_count' => (int)$pref['max_snooze_count'],
            'reminder_advance_minutes' => (int)$pref['reminder_advance_minutes'],
            'updated_at' => $pref['updated_at']
        ];
    }
    
    // Get notification statistics
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_notifications,
            COUNT(CASE WHEN is_read = 0 THEN 1 END) as unread_count,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_count,
            COUNT(CASE WHEN type = 'MEDICATION' THEN 1 END) as medication_count,
            COUNT(CASE WHEN type = 'APPOINTMENT' THEN 1 END) as appointment_count,
            COUNT(CASE WHEN type = 'EXERCISE' THEN 1 END) as exercise_count
        FROM notifications 
        WHERE user_id = ?
    ");
    $statsStmt->execute([$user['id']]);
    $stats = $statsStmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Notification preferences retrieved successfully',
        'data' => $formattedPrefs,
        'statistics' => $stats
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get-preferences.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in get-preferences.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while retrieving notification preferences']);
}
?>
