-- ============================================
-- MyRA Journey Database Update Script
-- Adding new features: CRP Progress Tracking, Enhanced Notifications, Rehab Exercise Tracking
-- ============================================

USE myrajourney;

-- ============================================
-- 1. CRP PROGRESS TRACKING TABLES
-- ============================================

-- CRP readings table for tracking C-Reactive Protein levels
CREATE TABLE IF NOT EXISTS crp_readings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    crp_value DECIMAL(6,2) NOT NULL COMMENT 'CRP value in mg/L',
    test_date DATE NOT NULL COMMENT 'Date when the test was performed',
    notes TEXT NULL COMMENT 'Optional notes about the reading',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_crp_patient_date (patient_id, test_date DESC),
    INDEX idx_crp_date (test_date DESC),
    CONSTRAINT fk_crp_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='CRP test results for inflammation tracking';

-- CRP trends table for storing calculated trend data
CREATE TABLE IF NOT EXISTS crp_trends (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    average_crp DECIMAL(6,2) NOT NULL,
    trend_direction ENUM('IMPROVING', 'STABLE', 'WORSENING') NOT NULL,
    trend_percentage DECIMAL(5,2) NULL COMMENT 'Percentage change from previous period',
    calculated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_trend_patient_period (patient_id, period_end DESC),
    CONSTRAINT fk_trend_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Calculated CRP trends over time periods';

-- ============================================
-- 2. ENHANCED MEDICATION NOTIFICATIONS
-- ============================================

-- Medication schedules table for detailed scheduling
CREATE TABLE IF NOT EXISTS medication_schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_medication_id INT UNSIGNED NOT NULL,
    time_of_day TIME NOT NULL COMMENT 'Scheduled time (e.g., 08:00, 14:00, 20:00)',
    days_of_week VARCHAR(7) NOT NULL DEFAULT '1111111' COMMENT 'Binary string: Mon-Sun (1=active, 0=inactive)',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_schedule_medication (patient_medication_id),
    INDEX idx_schedule_time (time_of_day),
    CONSTRAINT fk_schedule_pmed FOREIGN KEY (patient_medication_id) REFERENCES patient_medications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Detailed medication scheduling information';

-- Notification preferences table
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    notification_type ENUM('MEDICATION', 'APPOINTMENT', 'EXERCISE', 'CRP_REMINDER', 'GENERAL') NOT NULL,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    sound_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    vibration_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    led_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    snooze_duration_minutes TINYINT UNSIGNED NOT NULL DEFAULT 10,
    max_snooze_count TINYINT UNSIGNED NOT NULL DEFAULT 3,
    reminder_advance_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uniq_user_type (user_id, notification_type),
    CONSTRAINT fk_notif_pref_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User notification preferences by type';

-- Enhanced notifications table (extending existing notifications)
CREATE TABLE IF NOT EXISTS notification_actions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id INT UNSIGNED NOT NULL,
    action_type ENUM('TAKEN', 'SKIPPED', 'SNOOZED', 'DISMISSED', 'COMPLETED') NOT NULL,
    action_data JSON NULL COMMENT 'Additional action-specific data',
    performed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_action_notification (notification_id),
    INDEX idx_action_type_time (action_type, performed_at),
    CONSTRAINT fk_action_notification FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User actions performed on notifications';

-- Notification delivery tracking
CREATE TABLE IF NOT EXISTS notification_delivery (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id INT UNSIGNED NOT NULL,
    delivery_method ENUM('PUSH', 'SMS', 'EMAIL', 'IN_APP') NOT NULL,
    delivery_status ENUM('PENDING', 'SENT', 'DELIVERED', 'FAILED') NOT NULL DEFAULT 'PENDING',
    delivery_attempt TINYINT UNSIGNED NOT NULL DEFAULT 1,
    delivered_at DATETIME NULL,
    error_message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_delivery_notification (notification_id),
    INDEX idx_delivery_status (delivery_status, created_at),
    CONSTRAINT fk_delivery_notification FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Notification delivery tracking across different channels';

-- ============================================
-- 3. ENHANCED REHAB EXERCISE TRACKING
-- ============================================

-- Exercise library table
CREATE TABLE IF NOT EXISTS exercise_library (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    category ENUM('STRENGTH', 'FLEXIBILITY', 'CARDIO', 'BALANCE', 'RANGE_OF_MOTION') NOT NULL,
    difficulty_level ENUM('BEGINNER', 'INTERMEDIATE', 'ADVANCED') NOT NULL DEFAULT 'BEGINNER',
    description TEXT NULL,
    instructions TEXT NULL,
    video_url VARCHAR(255) NULL,
    image_url VARCHAR(255) NULL,
    duration_minutes SMALLINT UNSIGNED NULL,
    equipment_needed VARCHAR(255) NULL,
    muscle_groups JSON NULL COMMENT 'Array of targeted muscle groups',
    contraindications TEXT NULL COMMENT 'When this exercise should be avoided',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_exercise_category (category),
    INDEX idx_exercise_difficulty (difficulty_level),
    INDEX idx_exercise_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Library of available rehabilitation exercises';

-- Patient exercise assignments (replaces/enhances rehab_exercises)
CREATE TABLE IF NOT EXISTS patient_exercise_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    exercise_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NULL COMMENT 'Doctor who assigned the exercise',
    rehab_plan_id INT UNSIGNED NULL COMMENT 'Optional link to rehab plan',
    
    -- Assignment details
    assigned_date DATE NOT NULL,
    target_reps SMALLINT UNSIGNED NULL,
    target_sets SMALLINT UNSIGNED NULL,
    target_duration_minutes SMALLINT UNSIGNED NULL,
    frequency_per_week TINYINT UNSIGNED NOT NULL DEFAULT 3,
    
    -- Progress tracking
    difficulty_adjustment ENUM('EASIER', 'SAME', 'HARDER') NOT NULL DEFAULT 'SAME',
    special_instructions TEXT NULL,
    
    -- Status
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    completed_date DATE NULL,
    
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_assignment_patient (patient_id, is_active),
    INDEX idx_assignment_exercise (exercise_id),
    INDEX idx_assignment_doctor (doctor_id),
    INDEX idx_assignment_plan (rehab_plan_id),
    
    CONSTRAINT fk_assignment_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_exercise FOREIGN KEY (exercise_id) REFERENCES exercise_library(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_assignment_plan FOREIGN KEY (rehab_plan_id) REFERENCES rehab_plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Exercise assignments for patients';

-- Exercise session logs
CREATE TABLE IF NOT EXISTS exercise_session_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    assignment_id INT UNSIGNED NOT NULL,
    
    -- Session details
    session_date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    
    -- Performance data
    completed_reps SMALLINT UNSIGNED NULL,
    completed_sets SMALLINT UNSIGNED NULL,
    actual_duration_minutes SMALLINT UNSIGNED NULL,
    
    -- Feedback
    difficulty_rating TINYINT UNSIGNED NULL COMMENT '1-5 scale (1=too easy, 5=too hard)',
    pain_level_before TINYINT UNSIGNED NULL COMMENT '0-10 scale',
    pain_level_after TINYINT UNSIGNED NULL COMMENT '0-10 scale',
    energy_level TINYINT UNSIGNED NULL COMMENT '1-5 scale',
    
    -- Status and notes
    completion_status ENUM('COMPLETED', 'PARTIAL', 'SKIPPED', 'UNABLE') NOT NULL,
    notes TEXT NULL,
    
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_session_patient_date (patient_id, session_date DESC),
    INDEX idx_session_assignment (assignment_id, session_date DESC),
    INDEX idx_session_date (session_date DESC),
    
    CONSTRAINT fk_session_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_session_assignment FOREIGN KEY (assignment_id) REFERENCES patient_exercise_assignments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Individual exercise session logs';

-- Exercise progress tracking
CREATE TABLE IF NOT EXISTS exercise_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    assignment_id INT UNSIGNED NOT NULL,
    
    -- Progress period
    week_start_date DATE NOT NULL,
    week_end_date DATE NOT NULL,
    
    -- Aggregated metrics
    sessions_completed TINYINT UNSIGNED NOT NULL DEFAULT 0,
    sessions_planned TINYINT UNSIGNED NOT NULL DEFAULT 0,
    completion_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Percentage (0-100)',
    
    -- Performance trends
    avg_difficulty_rating DECIMAL(3,2) NULL,
    avg_pain_reduction DECIMAL(3,2) NULL COMMENT 'Average pain level reduction per session',
    avg_duration_minutes DECIMAL(5,2) NULL,
    
    -- Progress indicators
    progress_trend ENUM('IMPROVING', 'STABLE', 'DECLINING') NULL,
    needs_adjustment BOOLEAN NOT NULL DEFAULT FALSE,
    
    calculated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_progress_patient_week (patient_id, week_start_date DESC),
    INDEX idx_progress_assignment (assignment_id, week_start_date DESC),
    
    CONSTRAINT fk_progress_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_progress_assignment FOREIGN KEY (assignment_id) REFERENCES patient_exercise_assignments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Weekly exercise progress summaries';

-- ============================================
-- 4. ENHANCED CHATBOT RESPONSES
-- ============================================

-- Chatbot conversation history
CREATE TABLE IF NOT EXISTS chatbot_conversations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    session_id VARCHAR(64) NOT NULL COMMENT 'Unique session identifier',
    message_type ENUM('USER', 'BOT') NOT NULL,
    message_text TEXT NOT NULL,
    intent_detected VARCHAR(100) NULL COMMENT 'Detected user intent',
    confidence_score DECIMAL(3,2) NULL COMMENT 'Intent confidence (0-1)',
    response_time_ms SMALLINT UNSIGNED NULL COMMENT 'Bot response time in milliseconds',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_chat_user_session (user_id, session_id, created_at),
    INDEX idx_chat_session (session_id, created_at),
    INDEX idx_chat_intent (intent_detected),
    
    CONSTRAINT fk_chat_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Chatbot conversation history for analysis and improvement';

-- Chatbot knowledge base
CREATE TABLE IF NOT EXISTS chatbot_knowledge_base (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    intent VARCHAR(100) NOT NULL,
    keywords JSON NOT NULL COMMENT 'Array of keywords that trigger this response',
    response_template TEXT NOT NULL COMMENT 'Response template with placeholders',
    context_required JSON NULL COMMENT 'Required context data for personalized responses',
    priority TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Response priority (1=highest)',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    usage_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_kb_category (category),
    INDEX idx_kb_intent (intent),
    INDEX idx_kb_active (is_active, priority),
    
    UNIQUE KEY uniq_category_intent (category, intent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Dynamic chatbot knowledge base for responses';

-- ============================================
-- 5. UPDATE EXISTING TABLES
-- ============================================

-- Add new columns to existing notifications table
ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS priority ENUM('LOW', 'NORMAL', 'HIGH', 'URGENT') NOT NULL DEFAULT 'NORMAL' AFTER type,
ADD COLUMN IF NOT EXISTS action_data JSON NULL COMMENT 'Action buttons and data' AFTER body,
ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL COMMENT 'When notification expires' AFTER read_at,
ADD COLUMN IF NOT EXISTS snooze_count TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER expires_at,
ADD COLUMN IF NOT EXISTS snoozed_until DATETIME NULL AFTER snooze_count;

-- Add indexes for new notification columns
ALTER TABLE notifications 
ADD INDEX IF NOT EXISTS idx_notif_priority (priority, created_at),
ADD INDEX IF NOT EXISTS idx_notif_expires (expires_at),
ADD INDEX IF NOT EXISTS idx_notif_snoozed (snoozed_until);

-- Add new columns to existing patient_medications table for enhanced scheduling
ALTER TABLE patient_medications
ADD COLUMN IF NOT EXISTS reminder_enabled BOOLEAN NOT NULL DEFAULT TRUE AFTER is_active,
ADD COLUMN IF NOT EXISTS reminder_times JSON NULL COMMENT 'Array of reminder times' AFTER reminder_enabled,
ADD COLUMN IF NOT EXISTS adherence_rate DECIMAL(5,2) NULL COMMENT 'Calculated adherence percentage' AFTER reminder_times;

-- Add index for adherence tracking
ALTER TABLE patient_medications
ADD INDEX IF NOT EXISTS idx_pmed_adherence (patient_id, adherence_rate);

-- ============================================
-- 6. INSERT DEFAULT DATA
-- ============================================

-- Insert default notification preferences for existing users
INSERT IGNORE INTO notification_preferences (user_id, notification_type, enabled, sound_enabled, vibration_enabled, led_enabled)
SELECT 
    id as user_id,
    'MEDICATION' as notification_type,
    TRUE as enabled,
    TRUE as sound_enabled,
    TRUE as vibration_enabled,
    TRUE as led_enabled
FROM users 
WHERE role = 'PATIENT';

INSERT IGNORE INTO notification_preferences (user_id, notification_type, enabled, sound_enabled, vibration_enabled, led_enabled)
SELECT 
    id as user_id,
    'APPOINTMENT' as notification_type,
    TRUE as enabled,
    TRUE as sound_enabled,
    TRUE as vibration_enabled,
    FALSE as led_enabled
FROM users 
WHERE role = 'PATIENT';

INSERT IGNORE INTO notification_preferences (user_id, notification_type, enabled, sound_enabled, vibration_enabled, led_enabled)
SELECT 
    id as user_id,
    'EXERCISE' as notification_type,
    TRUE as enabled,
    FALSE as sound_enabled,
    TRUE as vibration_enabled,
    FALSE as led_enabled
FROM users 
WHERE role = 'PATIENT';

-- Insert sample exercises into exercise library
INSERT IGNORE INTO exercise_library (name, category, difficulty_level, description, instructions, duration_minutes, equipment_needed) VALUES
('Gentle Neck Rolls', 'FLEXIBILITY', 'BEGINNER', 'Gentle neck mobility exercise to reduce stiffness', 'Slowly roll your head in a circular motion, 5 times each direction', 5, 'None'),
('Shoulder Blade Squeezes', 'STRENGTH', 'BEGINNER', 'Strengthen upper back muscles and improve posture', 'Squeeze shoulder blades together, hold for 5 seconds, repeat 10 times', 8, 'None'),
('Wrist Circles', 'FLEXIBILITY', 'BEGINNER', 'Improve wrist mobility and reduce stiffness', 'Make small circles with your wrists, 10 times each direction', 3, 'None'),
('Wall Push-ups', 'STRENGTH', 'BEGINNER', 'Modified push-ups to build upper body strength', 'Stand arm''s length from wall, push against wall and return to start', 10, 'Wall'),
('Seated Spinal Twist', 'FLEXIBILITY', 'BEGINNER', 'Improve spinal mobility and reduce back stiffness', 'Sit tall, rotate torso left and right, hold for 15 seconds each side', 8, 'Chair'),
('Ankle Pumps', 'FLEXIBILITY', 'BEGINNER', 'Improve circulation and ankle mobility', 'Point and flex feet alternately, 20 repetitions', 5, 'None'),
('Modified Planks', 'STRENGTH', 'INTERMEDIATE', 'Core strengthening exercise', 'Hold plank position on knees, maintain straight line from head to knees', 12, 'Exercise mat'),
('Resistance Band Pulls', 'STRENGTH', 'INTERMEDIATE', 'Upper body strengthening with resistance', 'Pull resistance band apart at chest level, control the return', 15, 'Resistance band');

-- Insert sample chatbot knowledge base entries
INSERT IGNORE INTO chatbot_knowledge_base (category, intent, keywords, response_template, priority) VALUES
('MEDICATION', 'medication_reminder', '["medication", "pills", "medicine", "dose", "take"]', 'It''s time to take your {medication_name}. The prescribed dose is {dosage}. Would you like me to mark this as taken?', 1),
('SYMPTOMS', 'pain_inquiry', '["pain", "hurt", "ache", "sore"]', 'I understand you''re experiencing pain. On a scale of 1-10, how would you rate your current pain level? This will help track your symptoms.', 1),
('EXERCISE', 'exercise_reminder', '["exercise", "workout", "rehab", "physical therapy"]', 'Time for your rehabilitation exercises! Today you have {exercise_count} exercises scheduled. Shall we start with {first_exercise}?', 1),
('APPOINTMENT', 'appointment_inquiry', '["appointment", "doctor", "visit", "checkup"]', 'Your next appointment is on {appointment_date} at {appointment_time} with Dr. {doctor_name}. Would you like me to set a reminder?', 1),
('GENERAL', 'greeting', '["hello", "hi", "hey", "good morning", "good afternoon"]', 'Hello! I''m here to help you manage your rheumatoid arthritis journey. How can I assist you today?', 1),
('GENERAL', 'help', '["help", "what can you do", "commands", "options"]', 'I can help you with: 📋 Medication reminders, 🏥 Appointment scheduling, 💪 Exercise tracking, 📊 Symptom logging, and 📚 RA education. What would you like to know about?', 1);

-- ============================================
-- 7. VERIFICATION QUERIES
-- ============================================

-- Show all new tables created
SELECT TABLE_NAME, TABLE_COMMENT 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'myrajourney' 
AND TABLE_NAME IN (
    'crp_readings', 'crp_trends', 'medication_schedules', 'notification_preferences',
    'notification_actions', 'notification_delivery', 'exercise_library', 
    'patient_exercise_assignments', 'exercise_session_logs', 'exercise_progress',
    'chatbot_conversations', 'chatbot_knowledge_base'
);

-- Count total tables (should be 30+ now)
SELECT COUNT(*) as total_tables 
FROM information_schema.tables 
WHERE table_schema = 'myrajourney';

-- Show sample data counts
SELECT 
    (SELECT COUNT(*) FROM exercise_library) as exercise_library_count,
    (SELECT COUNT(*) FROM chatbot_knowledge_base) as chatbot_kb_count,
    (SELECT COUNT(*) FROM notification_preferences) as notification_prefs_count;

COMMIT;