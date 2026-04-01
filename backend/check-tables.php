<?php

require __DIR__ . '/src/bootstrap.php';

try {
    $db = Src\Config\DB::conn();
    
    echo "=== Checking Database Tables ===" . PHP_EOL . PHP_EOL;
    
    // Show all tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Existing tables:" . PHP_EOL;
    foreach ($tables as $table) {
        echo "  - $table" . PHP_EOL;
    }
    
    echo PHP_EOL;
    
    // Check if our specific tables exist
    $requiredTables = [
        'conversation_sessions',
        'conversation_messages', 
        'user_context_cache',
        'missed_dose_reports',
        'escalation_events'
    ];
    
    echo "Required tables status:" . PHP_EOL;
    foreach ($requiredTables as $table) {
        $exists = in_array($table, $tables);
        $status = $exists ? "✓ EXISTS" : "✗ MISSING";
        echo "  - $table: $status" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
