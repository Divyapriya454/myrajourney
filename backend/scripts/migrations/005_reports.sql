CREATE TABLE IF NOT EXISTS reports (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	patient_id INT UNSIGNED NOT NULL,
	doctor_id INT UNSIGNED NULL,
	title VARCHAR(160) NOT NULL,
	description TEXT NULL,
	file_url VARCHAR(255) NOT NULL,
	mime_type VARCHAR(80) NOT NULL,
	size_bytes INT UNSIGNED NOT NULL,
	uploaded_at DATETIME NOT NULL,
	INDEX idx_reports_patient (patient_id, uploaded_at),
	CONSTRAINT fk_reports_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
	CONSTRAINT fk_reports_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_notes (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	report_id INT UNSIGNED NOT NULL,
	doctor_id INT UNSIGNED NOT NULL,
	diagnosis_text TEXT NULL,
	suggestions_text TEXT NULL,
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL,
	CONSTRAINT fk_rnotes_report FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
	CONSTRAINT fk_rnotes_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




















