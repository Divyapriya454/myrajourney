-- ============================================
-- AI Features Database Schema
-- Medical Report Analysis & Flare-Up Prediction
-- ============================================

-- Table for extracted lab values from reports
CREATE TABLE IF NOT EXISTS lab_values (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    patient_id INT UNSIGNED NOT NULL,
    report_id INT UNSIGNED NOT NULL,
    test_name VARCHAR(100) NOT NULL,
    test_value DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    normal_range_min DECIMAL(10,2),
    normal_range_max DECIMAL(10,2),
    is_abnormal BOOLEAN DEFAULT FALSE,
    confidence_score DECIMAL(3,2) COMMENT '0.00 to 1.00',
    extracted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    manually_verified BOOLEAN DEFAULT FALSE,
    verified_by INT UNSIGNED NULL COMMENT 'Doctor ID who verified',
    verified_at TIMESTAMP NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    INDEX idx_patient_test (patient_id, test_name),
    INDEX idx_report (report_id),
    INDEX idx_abnormal (is_abnormal),
    INDEX idx_extracted_date (extracted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for OCR processing logs
CREATE TABLE IF NOT EXISTS ocr_processing_logs (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    report_id INT UNSIGNED NOT NULL,
    processing_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    ocr_text TEXT,
    processing_time_ms INT,
    error_message TEXT,
    retry_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    INDEX idx_status (processing_status),
    INDEX idx_report (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add columns to existing reports table (if not exists)
SET @dbname = DATABASE();
SET @tablename = "reports";
SET @columnname = "ocr_processed";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " BOOLEAN DEFAULT FALSE")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "auto_extracted";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " BOOLEAN DEFAULT FALSE")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "extraction_confidence";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " DECIMAL(3,2) DEFAULT NULL")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Table for flare-up predictions
CREATE TABLE IF NOT EXISTS flareup_predictions (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    patient_id INT UNSIGNED NOT NULL,
    prediction_date DATE NOT NULL,
    flareup_probability DECIMAL(5,4) COMMENT '0.0000 to 1.0000',
    predicted_severity ENUM('mild', 'moderate', 'severe') NULL,
    confidence_score DECIMAL(3,2),
    risk_factors JSON COMMENT 'Contributing factors',
    recommendations TEXT,
    model_version VARCHAR(20),
    alert_sent BOOLEAN DEFAULT FALSE,
    alert_sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    INDEX idx_patient_date (patient_id, prediction_date),
    INDEX idx_probability (flareup_probability),
    INDEX idx_alert_sent (alert_sent),
    UNIQUE KEY unique_patient_date (patient_id, prediction_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for actual flare-up occurrences (for model validation)
CREATE TABLE IF NOT EXISTS flareup_occurrences (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    patient_id INT UNSIGNED NOT NULL,
    occurrence_date DATE NOT NULL,
    severity ENUM('mild', 'moderate', 'severe') NOT NULL,
    symptoms TEXT,
    joints_affected VARCHAR(255),
    pain_level INT COMMENT '1-10 scale',
    prediction_id INT UNSIGNED NULL COMMENT 'Link to prediction if exists',
    reported_by ENUM('patient', 'doctor', 'system') DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (prediction_id) REFERENCES flareup_predictions(id) ON DELETE SET NULL,
    INDEX idx_patient_date (patient_id, occurrence_date),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for ML model performance metrics
CREATE TABLE IF NOT EXISTS model_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    model_type ENUM('flareup_prediction', 'report_extraction') NOT NULL,
    model_version VARCHAR(20) NOT NULL,
    accuracy DECIMAL(5,4),
    precision_score DECIMAL(5,4),
    recall DECIMAL(5,4),
    f1_score DECIMAL(5,4),
    false_positive_rate DECIMAL(5,4),
    evaluation_date DATE NOT NULL,
    sample_size INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_model_type (model_type),
    INDEX idx_version (model_version),
    INDEX idx_eval_date (evaluation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for weather data (for flare-up prediction)
CREATE TABLE IF NOT EXISTS weather_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    location VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    temperature DECIMAL(5,2),
    humidity DECIMAL(5,2),
    pressure DECIMAL(7,2) COMMENT 'Barometric pressure in hPa',
    precipitation DECIMAL(5,2),
    weather_condition VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_location_date (location, date),
    UNIQUE KEY unique_location_date (location, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for patient feature data (aggregated for ML)
CREATE TABLE IF NOT EXISTS patient_ml_features (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    patient_id INT UNSIGNED NOT NULL,
    feature_date DATE NOT NULL,
    avg_crp DECIMAL(10,2),
    avg_esr DECIMAL(10,2),
    symptom_severity_avg DECIMAL(3,2),
    medication_adherence_rate DECIMAL(3,2) COMMENT '0.00 to 1.00',
    missed_doses_count INT DEFAULT 0,
    exercise_completion_rate DECIMAL(3,2),
    days_since_last_flareup INT,
    weather_pressure DECIMAL(7,2),
    weather_humidity DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    INDEX idx_patient_date (patient_id, feature_date),
    UNIQUE KEY unique_patient_feature_date (patient_id, feature_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial model version
INSERT INTO model_metrics (model_type, model_version, evaluation_date, notes) 
VALUES ('flareup_prediction', 'v1.0.0', CURDATE(), 'Initial baseline model');

-- Create view for easy trend analysis
CREATE OR REPLACE VIEW lab_value_trends AS
SELECT 
    lv.patient_id,
    lv.test_name,
    lv.test_value,
    lv.unit,
    lv.is_abnormal,
    lv.extracted_at,
    r.created_at as report_date,
    p.id as patient_id_ref
FROM lab_values lv
JOIN reports r ON lv.report_id = r.id
JOIN patients p ON lv.patient_id = p.id
ORDER BY lv.patient_id, lv.test_name, lv.extracted_at;

-- Create view for flare-up prediction accuracy
CREATE OR REPLACE VIEW prediction_accuracy AS
SELECT 
    fp.patient_id,
    fp.prediction_date,
    fp.flareup_probability,
    fp.predicted_severity,
    fo.occurrence_date,
    fo.severity as actual_severity,
    CASE 
        WHEN fo.id IS NOT NULL THEN 'TRUE_POSITIVE'
        WHEN fo.id IS NULL AND fp.flareup_probability > 0.5 THEN 'FALSE_POSITIVE'
        ELSE 'TRUE_NEGATIVE'
    END as prediction_result,
    DATEDIFF(fo.occurrence_date, fp.prediction_date) as days_difference
FROM flareup_predictions fp
LEFT JOIN flareup_occurrences fo ON fp.patient_id = fo.patient_id 
    AND fo.occurrence_date BETWEEN fp.prediction_date AND DATE_ADD(fp.prediction_date, INTERVAL 7 DAY);
