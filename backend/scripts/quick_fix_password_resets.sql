-- ============================================
-- Quick Fix: Just Add password_resets Table
-- Use this if you only want to add the missing table
-- ============================================

USE `myrajourney`;

-- Create password_resets table if it doesn't exist
CREATE TABLE IF NOT EXISTS password_resets (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNSIGNED NOT NULL,
	token VARCHAR(64) NOT NULL,
	expires_at DATETIME NOT NULL,
	created_at DATETIME NOT NULL,
	UNIQUE KEY uniq_token (token),
	INDEX idx_user (user_id),
	INDEX idx_expires (expires_at),
	CONSTRAINT fk_pwreset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify it was created
SELECT 'password_resets table created successfully!' AS status;
SHOW TABLES LIKE 'password_resets';
DESCRIBE password_resets;

