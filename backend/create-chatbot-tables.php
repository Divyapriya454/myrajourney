<?php

require __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();

try {
    // Create conversation_sessions table
    $sql1 = "CREATE TABLE IF NOT EXISTS conversation_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(255) UNIQUE NOT NULL,
        user_id INT NOT NULL,
        start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status ENUM('active', 'ended', 'escalated') DEFAULT 'active',
        context_data JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_session_id (session_id),
        INDEX idx_user_id (user_id),
        INDEX idx_status (status)
    )";
    $db->exec($sql1);
    echo "✓ conversation_sessions table created\n";

    // Create conversation_messages table
    $sql2 = "CREATE TABLE IF NOT EXISTS conversation_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id VARCHAR(255) UNIQUE NOT NULL,
        session_id VARCHAR(255) NOT NULL,
        sender ENUM('user', 'bot') NOT NULL,
        content TEXT NOT NULL,
        intent VARCHAR(100),
        entities JSON,
        confidence DECIMAL(3,2),
        response_metadata JSON,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session_id (session_id),
        INDEX idx_sender (sender),
        INDEX idx_intent (intent),
        INDEX idx_timestamp (timestamp)
    )";
    $db->exec($sql2);
    echo "✓ conversation_messages table created\n";

    // Create user_context_cache table
    $sql3 = "CREATE TABLE IF NOT EXISTS user_context_cache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        context_type VARCHAR(50) NOT NULL,
        context_data JSON NOT NULL,
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_context (user_id, context_type),
        INDEX idx_user_id (user_id),
        INDEX idx_context_type (context_type),
        INDEX idx_expires_at (expires_at)
    )";
    $db->exec($sql3);
    echo "✓ user_context_cache table created\n";

    // Create missed_dose_reports table
    $sql4 = "CREATE TABLE IF NOT EXISTS missed_dose_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_medication_id INT NOT NULL,
        medication_name VARCHAR(255) NOT NULL,
        scheduled_time DATETIME NOT NULL,
        missed_time DATETIME NOT NULL,
        reason ENUM('forgot', 'side_effects', 'feeling_better', 'unavailable', 'other') NOT NULL,
        notes TEXT,
        doctor_id INT,
        doctor_notified BOOLEAN DEFAULT FALSE,
        reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_patient_medication (patient_medication_id),
        INDEX idx_doctor_id (doctor_id),
        INDEX idx_scheduled_time (scheduled_time),
        INDEX idx_reported_at (reported_at)
    )";
    $db->exec($sql4);
    echo "✓ missed_dose_reports table created\n";

    // Create escalation_events table
    $sql5 = "CREATE TABLE IF NOT EXISTS escalation_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(255) NOT NULL,
        message_id VARCHAR(255) NOT NULL,
        escalation_reason VARCHAR(255) NOT NULL,
        escalation_type ENUM('emergency', 'complex_medical', 'user_request', 'low_confidence') NOT NULL,
        user_message TEXT,
        bot_response TEXT,
        escalated_to VARCHAR(100),
        resolved BOOLEAN DEFAULT FALSE,
        resolved_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session_id (session_id),
        INDEX idx_escalation_type (escalation_type),
        INDEX idx_resolved (resolved)
    )";
    $db->exec($sql5);
    echo "✓ escalation_events table created\n";

    echo "\n✓ All enhanced chatbot tables created successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
