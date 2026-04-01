CREATE TABLE IF NOT EXISTS symptom_logs (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	patient_id INT UNSIGNED NOT NULL,
	`date` DATE NOT NULL,
	pain_level TINYINT UNSIGNED NOT NULL,
	stiffness_level TINYINT UNSIGNED NOT NULL,
	fatigue_level TINYINT UNSIGNED NOT NULL,
	notes TEXT NULL,
	created_at DATETIME NOT NULL,
	INDEX idx_symptom_patient_date (patient_id, `date`),
	CONSTRAINT fk_symptom_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS health_metrics (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	patient_id INT UNSIGNED NOT NULL,
	metric_type VARCHAR(64) NOT NULL,
	metric_value DECIMAL(10,2) NOT NULL,
	unit VARCHAR(32) NULL,
	recorded_at DATETIME NOT NULL,
	created_at DATETIME NOT NULL,
	INDEX idx_metric_patient_time (patient_id, recorded_at),
	CONSTRAINT fk_metric_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




















