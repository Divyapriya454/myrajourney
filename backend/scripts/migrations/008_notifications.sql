CREATE TABLE IF NOT EXISTS notifications (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNSIGNED NOT NULL,
	type VARCHAR(64) NOT NULL,
	title VARCHAR(160) NOT NULL,
	body TEXT NULL,
	read_at DATETIME NULL,
	created_at DATETIME NOT NULL,
	INDEX idx_notif_user (user_id, created_at),
	CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




















