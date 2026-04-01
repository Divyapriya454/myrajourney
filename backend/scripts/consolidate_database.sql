-- ============================================
-- Database Consolidation Script
-- This script will:
-- 1. Drop duplicate myrajourney databases
-- 2. Create a fresh myrajourney database
-- 3. Run all migrations in order
-- 4. Ensure password_resets table exists
-- ============================================

-- Step 1: Drop duplicate databases (keep only one)
-- WARNING: This will delete all data in duplicate databases
-- Make sure to backup important data first!

DROP DATABASE IF EXISTS `myrajourney_old`;
DROP DATABASE IF EXISTS `myrajourney_backup`;

-- Step 2: Create fresh database
DROP DATABASE IF EXISTS `myrajourney`;
CREATE DATABASE `myrajourney` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `myrajourney`;

-- ============================================
-- Migration 001: Users Table
-- ============================================
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

-- ============================================
-- Migration 002: Profiles Tables
-- ============================================
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

-- ============================================
-- Migration 003: Settings Table
-- ============================================
CREATE TABLE IF NOT EXISTS user_settings (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNSIGNED NOT NULL UNIQUE,
	notifications_enabled BOOLEAN NOT NULL DEFAULT TRUE,
	email_notifications BOOLEAN NOT NULL DEFAULT TRUE,
	language VARCHAR(10) NOT NULL DEFAULT 'en',
	timezone VARCHAR(50) NOT NULL DEFAULT 'UTC',
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL,
	CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Migration 004: Appointments Table
-- ============================================
CREATE TABLE IF NOT EXISTS appointments (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	patient_id INT UNSIGNED NOT NULL,
	doctor_id INT UNSIGNED NOT NULL,
	title VARCHAR(160) NOT NULL,
	description TEXT NULL,
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

-- ============================================
-- Migration 005: Reports Tables
-- ============================================
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

-- ============================================
-- Migration 006: Medications Tables
-- ============================================
CREATE TABLE IF NOT EXISTS patient_medications (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	patient_id INT UNSIGNED NOT NULL,
	doctor_id INT UNSIGNED NOT NULL,
	medication_name VARCHAR(160) NOT NULL,
	dosage VARCHAR(80) NOT NULL,
	frequency VARCHAR(80) NOT NULL,
	start_date DATE NOT NULL,
	end_date DATE NULL,
	instructions TEXT NULL,
	is_active BOOLEAN NOT NULL DEFAULT TRUE,
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL,
	INDEX idx_patient_med (patient_id, is_active),
	CONSTRAINT fk_med_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
	CONSTRAINT fk_med_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS medication_logs (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	patient_medication_id INT UNSIGNED NOT NULL,
	taken_at DATETIME NOT NULL,
	notes TEXT NULL,
	created_at DATETIME NOT NULL,
	CONSTRAINT fk_medlog_med FOREIGN KEY (patient_medication_id) REFERENCES patient_medications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Migration 007: Rehab Tables
-- ============================================
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
	video_url VARCHAR(255) NULL,
	reps SMALLINT UNSIGNED NULL,
	sets SMALLINT UNSIGNED NULL,
	frequency_per_week TINYINT UNSIGNED NULL,
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL,
	CONSTRAINT fk_rehab_ex_plan FOREIGN KEY (rehab_plan_id) REFERENCES rehab_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Migration 008: Notifications Table
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	user_id INT UNSIGNED NOT NULL,
	type VARCHAR(50) NOT NULL,
	title VARCHAR(160) NOT NULL,
	body TEXT NULL,
	is_read BOOLEAN NOT NULL DEFAULT FALSE,
	created_at DATETIME NOT NULL,
	INDEX idx_user_read (user_id, is_read, created_at),
	CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Migration 009: Education Table
-- ============================================
CREATE TABLE IF NOT EXISTS education_articles (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	slug VARCHAR(160) NOT NULL UNIQUE,
	title VARCHAR(160) NOT NULL,
	summary TEXT NULL,
	content TEXT NOT NULL,
	category VARCHAR(80) NULL,
	author VARCHAR(120) NULL,
	published_at DATETIME NULL,
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL,
	INDEX idx_slug (slug),
	INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Migration 010: Symptoms and Metrics Tables
-- ============================================
CREATE TABLE IF NOT EXISTS symptoms (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	patient_id INT UNSIGNED NOT NULL,
	date DATE NOT NULL,
	pain_level TINYINT UNSIGNED NULL,
	description TEXT NULL,
	location VARCHAR(160) NULL,
	severity ENUM('MILD','MODERATE','SEVERE') NULL,
	created_at DATETIME NOT NULL,
	INDEX idx_patient_date (patient_id, date),
	CONSTRAINT fk_symptom_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS health_metrics (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	patient_id INT UNSIGNED NOT NULL,
	metric_type VARCHAR(50) NOT NULL,
	value DECIMAL(10,2) NOT NULL,
	unit VARCHAR(20) NULL,
	recorded_at DATETIME NOT NULL,
	notes TEXT NULL,
	created_at DATETIME NOT NULL,
	INDEX idx_patient_metric (patient_id, metric_type, recorded_at),
	CONSTRAINT fk_metric_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Migration 011: Password Resets Table
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
-- Verify all tables were created
-- ============================================
SELECT 'Database setup complete!' AS status;
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'myrajourney' 
ORDER BY TABLE_NAME;

