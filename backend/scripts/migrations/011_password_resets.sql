CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_token (token),
  INDEX idx_user (user_id),
  CONSTRAINT fk_pwreset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


