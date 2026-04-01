CREATE TABLE IF NOT EXISTS settings (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNSIGNED NOT NULL,
	`key` VARCHAR(64) NOT NULL,
	`value` VARCHAR(255) NULL,
	updated_at DATETIME NOT NULL,
	UNIQUE KEY uniq_user_key (user_id, `key`),
	CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




















