<?php
/**
 * Database Update Script for New Features
 * Adds tables for CRP tracking, enhanced notifications, and rehab exercise tracking
 */

// Database configuration - using direct values for simplicity
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'myrajourney');
define('DB_USER', 'root');
define('DB_PASS', '');

header('Content-Type: application/json');

try {
    // Read the SQL update script
    $sqlFile = __DIR__ . '/scripts/update_database_new_features.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL update file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("Failed to read SQL update file");
    }
    
    // Connect to database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^\s*--/', $stmt) && 
                   !preg_match('/^\s*\/\*/', $stmt);
        }
    );
    
    $results = [];
    $successCount = 0;
    $errorCount = 0;
    
    // Execute each statement
    foreach ($statements as $index => $statement) {
        try {
            // Skip comments and empty statements
            if (empty(trim($statement))) {
                continue;
            }
            
            $stmt = $pdo->prepare($statement);
            $executed = $stmt->execute();
            
            $results[] = [
                'statement_index' => $index + 1,
                'success' => true,
                'affected_rows' => $stmt->rowCount(),
                'statement_preview' => substr(trim($statement), 0, 100) . '...'
            ];
            
            $successCount++;
            
        } catch (PDOException $e) {
            $results[] = [
                'statement_index' => $index + 1,
                'success' => false,
                'error' => $e->getMessage(),
                'statement_preview' => substr(trim($statement), 0, 100) . '...'
            ];
            
            $errorCount++;
            
            // Continue with other statements even if one fails
            continue;
        }
    }
    
    // Get final table count
    try {
        $tableCountStmt = $pdo->query("
            SELECT COUNT(*) as total_tables 
            FROM information_schema.tables 
            WHERE table_schema = 'myrajourney'
        ");
        $tableCount = $tableCountStmt->fetch()['total_tables'];
    } catch (Exception $e) {
        $tableCount = 0;
    }
    
    // Get new tables created
    try {
        $newTablesStmt = $pdo->query("
            SELECT TABLE_NAME, TABLE_COMMENT 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = 'myrajourney' 
            AND TABLE_NAME IN (
                'crp_readings', 'crp_trends', 'medication_schedules', 'notification_preferences',
                'notification_actions', 'notification_delivery', 'exercise_library', 
                'patient_exercise_assignments', 'exercise_session_logs', 'exercise_progress',
                'chatbot_conversations', 'chatbot_knowledge_base'
            )
            ORDER BY TABLE_NAME
        ");
        $newTables = $newTablesStmt->fetchAll();
    } catch (Exception $e) {
        $newTables = [];
    }
    
    // Get sample data counts
    try {
        $sampleDataStmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM exercise_library) as exercise_library_count,
                (SELECT COUNT(*) FROM chatbot_knowledge_base) as chatbot_kb_count,
                (SELECT COUNT(*) FROM notification_preferences) as notification_prefs_count
        ");
        $sampleData = $sampleDataStmt->fetch();
    } catch (Exception $e) {
        $sampleData = [
            'exercise_library_count' => 0,
            'chatbot_kb_count' => 0,
            'notification_prefs_count' => 0
        ];
    }
    
    // Response
    echo json_encode([
        'success' => true,
        'message' => 'Database update completed successfully',
        'summary' => [
            'total_statements_executed' => count($statements),
            'successful_statements' => $successCount,
            'failed_statements' => $errorCount,
            'total_tables_in_database' => $tableCount,
            'new_tables_created' => count($newTables),
            'sample_data_inserted' => $sampleData
        ],
        'new_tables' => $newTables,
        'execution_details' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
