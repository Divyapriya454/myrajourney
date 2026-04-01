CREATE TABLE IF NOT EXISTS patients (
	id INT UNSIGNED PRIMARY KEY,
	dob DATE NULL,
	gender ENUM('M','F','O') NULL,
	medical_id VARCHAR(64) NULL,
	address VARCHAR(255) NULL,
	emergency_contact VARCHAR(255) NULL,
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL,
	CONSTRAINT fk_patients_user FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS doctors (
	id INT UNSIGNED PRIMARY KEY,
	specialization VARCHAR(120) NULL,
	license_no VARCHAR(64) NULL,
	bio TEXT NULL,
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL,
	CONSTRAINT fk_doctors_user FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




















