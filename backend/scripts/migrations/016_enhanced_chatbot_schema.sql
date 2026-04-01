-- Enhanced Chatbot Database Schema
-- This migration creates tables for the dynamic chatbot system

-- Conversation Sessions Table
CREATE TABLE IF NOT EXISTS conversation_sessions (
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
);

-- Enhanced Messages Table (extends existing chatbot_logs)
CREATE TABLE IF NOT EXISTS conversation_messages (
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
);

-- User Context Cache Table
CREATE TABLE IF NOT EXISTS user_context_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    context_type VARCHAR(50) NOT NULL,
    context_data JSON NOT NULL,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_context (user_id, context_type),
    INDEX idx_user_id (user_id),
    INDEX idx_context_type (context_type),
    INDEX idx_expires_at (expires_at)
);

-- Intent Classification Logs
CREATE TABLE IF NOT EXISTS intent_classification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id VARCHAR(255) NOT NULL,
    user_message TEXT NOT NULL,
    detected_intent VARCHAR(100),
    confidence_score DECIMAL(3,2),
    entities JSON,
    processing_time_ms INT,
    model_version VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_message_id (message_id),
    INDEX idx_intent (detected_intent),
    INDEX idx_confidence (confidence_score)
);

-- Escalation Events Table
CREATE TABLE IF NOT EXISTS escalation_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    message_id VARCHAR(255) NOT NULL,
    escalation_reason VARCHAR(255) NOT NULL,
    escalation_type ENUM('emergency', 'complex_medical', 'user_request', 'low_confidence') NOT NULL,
    user_message TEXT,
    bot_response TEXT,
    escalated_to VARCHAR(100), -- doctor_id, support_team, etc.
    resolved BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_escalation_type (escalation_type),
    INDEX idx_resolved (resolved)
);

-- User Interaction Analytics
CREATE TABLE IF NOT EXISTS user_interaction_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    interaction_type VARCHAR(50) NOT NULL, -- message_sent, navigation_clicked, action_taken
    interaction_data JSON,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_interaction_type (interaction_type),
    INDEX idx_timestamp (timestamp)
);

-- Response Quality Feedback
CREATE TABLE IF NOT EXISTS response_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    feedback_type ENUM('helpful', 'not_helpful', 'incorrect', 'inappropriate') NOT NULL,
    feedback_text TEXT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_message_id (message_id),
    INDEX idx_user_id (user_id),
    INDEX idx_feedback_type (feedback_type)
);

-- Missed Dose Reports Table (for medication notifications integration)
CREATE TABLE IF NOT EXISTS missed_dose_reports (
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
);

-- Update existing chatbot_logs table to be compatible (if it exists)
-- Add indexes for better performance
ALTER TABLE chatbot_logs 
ADD INDEX IF NOT EXISTS idx_user_id (user_id),
ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- Create views for easy data access
CREATE OR REPLACE VIEW active_conversations AS
SELECT 
    cs.session_id,
    cs.user_id,
    u.name as user_name,
    cs.start_time,
    cs.last_activity,
    COUNT(cm.id) as message_count,
    MAX(cm.timestamp) as last_message_time
FROM conversation_sessions cs
LEFT JOIN users u ON cs.user_id = u.id
LEFT JOIN conversation_messages cm ON cs.session_id = cm.session_id
WHERE cs.status = 'active'
GROUP BY cs.session_id, cs.user_id, u.name, cs.start_time, cs.last_activity;

CREATE OR REPLACE VIEW conversation_summary AS
SELECT 
    cs.session_id,
    cs.user_id,
    cs.status,
    cs.start_time,
    cs.last_activity,
    COUNT(cm.id) as total_messages,
    COUNT(CASE WHEN cm.sender = 'user' THEN 1 END) as user_messages,
    COUNT(CASE WHEN cm.sender = 'bot' THEN 1 END) as bot_messages,
    AVG(cm.confidence) as avg_confidence,
    COUNT(CASE WHEN ee.id IS NOT NULL THEN 1 END) as escalation_count
FROM conversation_sessions cs
LEFT JOIN conversation_messages cm ON cs.session_id = cm.session_id
LEFT JOIN escalation_events ee ON cs.session_id = ee.session_id
GROUP BY cs.session_id, cs.user_id, cs.status, cs.start_time, cs.last_activity;