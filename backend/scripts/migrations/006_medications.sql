CREATE TABLE IF NOT EXISTS medications (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(160) NOT NULL,
	strength VARCHAR(80) NULL,
	form VARCHAR(80) NULL,
	instructions TEXT NULL,
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_medications (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	patient_id INT UNSIGNED NOT NULL,
	medication_id INT UNSIGNED NULL,
	name_override VARCHAR(160) NULL,
	dosage VARCHAR(80) NULL,
	frequency_per_day TINYINT UNSIGNED NULL,
	start_date DATE NULL,
	end_date DATE NULL,
	active TINYINT(1) NOT NULL DEFAULT 1,
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL,
	CONSTRAINT fk_pmed_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
	CONSTRAINT fk_pmed_med FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS medication_logs (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	patient_medication_id INT UNSIGNED NOT NULL,
	taken_at DATETIME NOT NULL,
	status ENUM('TAKEN','SKIPPED') NOT NULL,
	created_at DATETIME NOT NULL,
	CONSTRAINT fk_mlog_pmed FOREIGN KEY (patient_medication_id) REFERENCES patient_medications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




















