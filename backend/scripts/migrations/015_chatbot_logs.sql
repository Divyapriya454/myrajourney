CREATE TABLE IF NOT EXISTS chatbot_logs (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNSIGNED NULL,
	user_message TEXT NOT NULL,
	bot_response TEXT NOT NULL,
	created_at DATETIME NOT NULL,
	INDEX idx_chatbot_user (user_id, created_at),
	CONSTRAINT fk_chatbot_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
