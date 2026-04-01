-- ============================================
-- Update Existing Database Script
-- Use this if you want to keep existing data
-- and just add missing tables/columns
-- ============================================

USE `myrajourney`;

-- ============================================
-- Ensure password_resets table exists
-- ============================================
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

-- ============================================
-- Ensure all other tables exist (if not already)
-- ============================================

-- Users table (should already exist)
CREATE TABLE IF NOT EXISTS users (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(190) NOT NULL UNIQUE,
	phone VARCHAR(32) NULL,
	password_hash VARCHAR(255) NOT NULL,
	role ENUM('PATIENT','DOCTOR','ADMIN') NOT NULL DEFAULT 'PATIENT',
	name VARCHAR(120) NULL,
	avatar_url VARCHAR(255) NULL,
	status ENUM('ACTIVE','SUSPENDED') NOT NULL DEFAULT 'ACTIVE',
	last_login_at DATETIME NULL,
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profiles
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

-- Settings
CREATE TABLE IF NOT EXISTS settings (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNSIGNED NOT NULL,
	`key` VARCHAR(64) NOT NULL,
	`value` VARCHAR(255) NULL,
	updated_at DATETIME NOT NULL,
	UNIQUE KEY uniq_user_key (user_id, `key`),
	CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Appointments
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

-- Reports
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

-- Medications
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

-- Rehab
CREATE TABLE IF NOT EXISTS rehab_plans (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	patient_id INT UNSIGNED NOT NULL,
	title VARCHAR(160) NOT NULL,
	description TEXT NULL,
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL,
	CONSTRAINT fk_rehab_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rehab_exercises (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	rehab_plan_id INT UNSIGNED NOT NULL,
	name VARCHAR(160) NOT NULL,
	description TEXT NULL,
	reps SMALLINT UNSIGNED NULL,
	sets SMALLINT UNSIGNED NULL,
	frequency_per_week TINYINT UNSIGNED NULL,
	CONSTRAINT fk_rehab_ex_plan FOREIGN KEY (rehab_plan_id) REFERENCES rehab_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
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

-- Education
CREATE TABLE IF NOT EXISTS education_articles (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	slug VARCHAR(160) NOT NULL UNIQUE,
	title VARCHAR(160) NOT NULL,
	category ENUM('WHAT_IS_RA','MANAGEMENT','NUTRITION','LIFESTYLE') NOT NULL,
	content_html MEDIUMTEXT NOT NULL,
	cover_image_url VARCHAR(255) NULL,
	published_at DATETIME NULL,
	updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Symptoms and Metrics
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

-- ============================================
-- Verify tables
-- ============================================
SELECT 'All tables updated!' AS status;
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'myrajourney' 
ORDER BY TABLE_NAME;

