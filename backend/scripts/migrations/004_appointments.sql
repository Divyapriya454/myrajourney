CREATE TABLE IF NOT EXISTS appointments (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	patient_id INT UNSIGNED NOT NULL,
	doctor_id INT UNSIGNED NOT NULL,
	title VARCHAR(160) NOT NULL,
	notes TEXT NULL,
	location VARCHAR(160) NULL,
	start_time DATETIME NOT NULL,
	end_time DATETIME NOT NULL,
	status ENUM('SCHEDULED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'SCHEDULED',
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL,
	INDEX idx_patient_time (patient_id, start_time),
	INDEX idx_doctor_time (doctor_id, start_time),
	CONSTRAINT fk_appt_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
	CONSTRAINT fk_appt_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




















