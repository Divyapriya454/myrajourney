-- ============================================
-- MyRA Journey - New Features Database Migration
-- This script adds support for all the new features implemented:
-- 1. Profile Picture System
-- 2. Enhanced User Management (Admin System)
-- 3. Enhanced Notifications with Categories
-- 4. Advanced AI Chatbot Support
-- 5. Exercise Thumbnail System
-- ============================================

USE myrajourney;

-- ============================================
-- 1. PROFILE PICTURE SYSTEM
-- ============================================

-- Add profile picture support to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL COMMENT 'Profile picture filename' AFTER avatar_url;

-- ============================================
-- 2. ENHANCED USER MANAGEMENT (ADMIN SYSTEM)
-- ============================================

-- Add specialization to users table (for doctors)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS specialization VARCHAR(120) NULL COMMENT 'Doctor specialization' AFTER name;

-- Add active status (different from status enum)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'User active status for admin management' AFTER status;

-- Add age field for better user management
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS age INT NULL COMMENT 'User age' AFTER name;

-- Add gender field
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS gender ENUM('M','F','O') NULL COMMENT 'User gender' AFTER age;

-- ============================================
-- 3. ENHANCED NOTIFICATIONS WITH CATEGORIES
-- ============================================

-- Add category and color support to notifications
ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS category VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT 'Notification category' AFTER type;

ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS color VARCHAR(20) NULL COMMENT 'Notification color code' AFTER category;

ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS icon VARCHAR(50) NULL COMMENT 'Notification icon name' AFTER color;

ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS priority ENUM('LOW','NORMAL','HIGH','URGENT') NOT NULL DEFAULT 'NORMAL' COMMENT 'Notification priority' AFTER icon;

-- ============================================
-- 4. ADVANCED AI CHATBOT SUPPORT
-- ============================================

-- Create conversation sessions table
CREATE TABLE IF NOT EXISTS conversation_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    session_id VARCHAR(100) NOT NULL UNIQUE,
    started_at DATETIME NOT NULL,
    ended_at DATETIME NULL,
    message_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_user_session (user_id, started_at),
    INDEX idx_session_id (session_id),
    CONSTRAINT fk_conv_session_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create conversation messages table
CREATE TABLE IF NOT EXISTS conversation_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    message_type ENUM('USER','BOT') NOT NULL,
    message_content TEXT NOT NULL,
    intent_detected VARCHAR(50) NULL,
    confidence_score DECIMAL(3,2) NULL,
    response_time_ms INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_session_time (session_id, created_at),
    INDEX idx_user_messages (user_id, created_at),
    CONSTRAINT fk_conv_msg_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_msg_session FOREIGN KEY (session_id) REFERENCES conversation_sessions(session_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create patient context cache table
CREATE TABLE IF NOT EXISTS patient_context_cache (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    context_data JSON NOT NULL,
    last_updated DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX idx_user_context (user_id),
    INDEX idx_expires (expires_at),
    CONSTRAINT fk_context_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. EXERCISE THUMBNAIL SYSTEM
-- ============================================

-- Create exercise thumbnails table
CREATE TABLE IF NOT EXISTS exercise_thumbnails (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exercise_id VARCHAR(20) NOT NULL UNIQUE,
    exercise_name VARCHAR(100) NOT NULL,
    video_filename VARCHAR(100) NULL,
    thumbnail_filename VARCHAR(100) NULL,
    thumbnail_generated BOOLEAN NOT NULL DEFAULT FALSE,
    thumbnail_size_bytes INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_exercise_id (exercise_id),
    INDEX idx_generated (thumbnail_generated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default exercise data
INSERT IGNORE INTO exercise_thumbnails (exercise_id, exercise_name, video_filename, created_at, updated_at) VALUES
('ex_001', 'Wrist Flexion', 'ex_001_wrist_flexion.mp4', NOW(), NOW()),
('ex_002', 'Wrist Rotation', 'ex_002_wrist_rotation.mp4', NOW(), NOW()),
('ex_003', 'Thumb Opposition', 'ex_003_thumb_opposition.mp4', NOW(), NOW()),
('ex_004', 'Thumb Flexion', 'ex_004_thumb_flexion.mp4', NOW(), NOW()),
('ex_005', 'Finger Flexion', 'ex_005_finger_flexion.mp4', NOW(), NOW()),
('ex_006', 'Finger Extension', 'ex_006_finger_extension.mp4', NOW(), NOW()),
('ex_007', 'Finger Pinch', 'ex_007_finger_pinch.mp4', NOW(), NOW()),
('ex_008', 'Knee Flexion', 'ex_008_knee_flexion.mp4', NOW(), NOW()),
('ex_009', 'Hip Flexion', 'ex_009_hip_flexion.mp4', NOW(), NOW()),
('ex_010', 'Hip Abduction', 'ex_010_hip_abduction.mp4', NOW(), NOW());

-- ============================================
-- 6. ADMIN AUDIT TRAIL
-- ============================================

-- Create admin audit trail table
CREATE TABLE IF NOT EXISTS admin_audit_trail (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT UNSIGNED NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    target_user_id INT UNSIGNED NULL,
    action_details JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_admin_action (admin_user_id, created_at),
    INDEX idx_target_user (target_user_id),
    INDEX idx_action_type (action_type),
    CONSTRAINT fk_audit_admin FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_audit_target FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. ENHANCED MEDICATION TRACKING
-- ============================================

-- Add reminder settings to patient_medications
ALTER TABLE patient_medications 
ADD COLUMN IF NOT EXISTS reminder_enabled BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Medication reminder enabled' AFTER active;

ALTER TABLE patient_medications 
ADD COLUMN IF NOT EXISTS reminder_times JSON NULL COMMENT 'Reminder times array' AFTER reminder_enabled;

ALTER TABLE patient_medications 
ADD COLUMN IF NOT EXISTS reminder_sound VARCHAR(50) NULL COMMENT 'Reminder sound preference' AFTER reminder_times;

-- Add adherence tracking
ALTER TABLE patient_medications 
ADD COLUMN IF NOT EXISTS total_doses INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total prescribed doses' AFTER reminder_sound;

ALTER TABLE patient_medications 
ADD COLUMN IF NOT EXISTS taken_doses INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Doses taken' AFTER total_doses;

ALTER TABLE patient_medications 
ADD COLUMN IF NOT EXISTS missed_doses INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Doses missed' AFTER taken_doses;

ALTER TABLE patient_medications 
ADD COLUMN IF NOT EXISTS skipped_doses INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Doses skipped' AFTER missed_doses;

-- ============================================
-- 8. SYSTEM SETTINGS FOR ADMIN
-- ============================================

-- Create system settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type ENUM('STRING','INTEGER','BOOLEAN','JSON') NOT NULL DEFAULT 'STRING',
    description TEXT NULL,
    is_public BOOLEAN NOT NULL DEFAULT FALSE,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_setting_key (setting_key),
    INDEX idx_public (is_public),
    CONSTRAINT fk_system_settings_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default system settings
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, is_public, created_at, updated_at) VALUES
('app_name', 'MyRA Journey', 'STRING', 'Application name', TRUE, NOW(), NOW()),
('app_version', '2.0.0', 'STRING', 'Application version', TRUE, NOW(), NOW()),
('max_file_upload_size', '10485760', 'INTEGER', 'Maximum file upload size in bytes (10MB)', FALSE, NOW(), NOW()),
('password_min_length', '8', 'INTEGER', 'Minimum password length', FALSE, NOW(), NOW()),
('password_max_length', '16', 'INTEGER', 'Maximum password length', FALSE, NOW(), NOW()),
('session_timeout', '1800', 'INTEGER', 'Session timeout in seconds (30 minutes)', FALSE, NOW(), NOW()),
('enable_notifications', 'true', 'BOOLEAN', 'Enable push notifications', FALSE, NOW(), NOW()),
('enable_ai_chatbot', 'true', 'BOOLEAN', 'Enable AI chatbot functionality', FALSE, NOW(), NOW()),
('enable_exercise_tracking', 'true', 'BOOLEAN', 'Enable exercise tracking', FALSE, NOW(), NOW()),
('maintenance_mode', 'false', 'BOOLEAN', 'Application maintenance mode', FALSE, NOW(), NOW());

-- ============================================
-- 9. UPDATE EXISTING DATA
-- ============================================

-- Update existing users to have active status
UPDATE users SET active = TRUE WHERE active IS NULL;

-- Update existing notifications to have default category
UPDATE notifications SET category = 'general' WHERE category IS NULL OR category = '';

-- ============================================
-- 10. CREATE INDEXES FOR PERFORMANCE
-- ============================================

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_active ON users(active);
CREATE INDEX IF NOT EXISTS idx_users_profile_picture ON users(profile_picture);
CREATE INDEX IF NOT EXISTS idx_notifications_category ON notifications(category);
CREATE INDEX IF NOT EXISTS idx_notifications_priority ON notifications(priority);

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Show updated table structures
DESCRIBE users;
DESCRIBE notifications;
DESCRIBE patient_medications;

-- Count new tables
SELECT COUNT(*) as new_tables_count FROM information_schema.tables 
WHERE table_schema = 'myrajourney' 
AND table_name IN ('conversation_sessions', 'conversation_messages', 'patient_context_cache', 'exercise_thumbnails', 'admin_audit_trail', 'system_settings');

-- Show all tables
SHOW TABLES;

-- Verify data
SELECT COUNT(*) as exercise_count FROM exercise_thumbnails;
SELECT COUNT(*) as system_settings_count FROM system_settings;

SELECT 'Migration completed successfully!' as status;