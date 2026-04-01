<?php

require __DIR__ . '/src/bootstrap.php';

try {
    $db = Src\Config\DB::conn();
    
    echo "=== Fixing Tables ===" . PHP_EOL;
    
    // Drop and recreate conversation_sessions table
    echo "Dropping conversation_sessions..." . PHP_EOL;
    $db->exec("DROP TABLE IF EXISTS conversation_sessions");
    
    echo "Creating conversation_sessions..." . PHP_EOL;
    $sql = "CREATE TABLE conversation_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(255) UNIQUE NOT NULL,
        user_id INT NOT NULL,
        start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status ENUM('active', 'ended', 'escalated') DEFAULT 'active',
        context_data TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_session_id (session_id),
        INDEX idx_user_id (user_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB";
    
    $db->exec($sql);
    echo "✓ conversation_sessions created" . PHP_EOL;
    
    // Test insert
    echo "Testing insert..." . PHP_EOL;
    $stmt = $db->prepare("INSERT INTO conversation_sessions (session_id, user_id) VALUES (?, ?)");
    $result = $stmt->execute(['test_' . time(), 1]);
    
    if ($result) {
        echo "✓ Insert test successful" . PHP_EOL;
    } else {
        echo "✗ Insert test failed" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
