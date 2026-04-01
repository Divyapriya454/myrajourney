<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

// ============================================================
// AUTO-MIGRATION: always runs (idempotent) so a fresh local DB
// can self-heal without any manual import step.
// ============================================================
try {
    $_db = \Src\Config\DB::conn();

        // Helper: add column if missing
        $addCol = function(string $table, string $col, string $def) use ($_db): void {
            try {
                if (!$_db->query("SHOW COLUMNS FROM `$table` LIKE '$col'")->fetch()) {
                    $_db->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
                }
            } catch (\Throwable $e) {}
        };

        // Base tables required by the mobile app.
        $_db->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL UNIQUE,
            phone VARCHAR(32) NULL,
            password VARCHAR(255) NULL,
            password_hash VARCHAR(255) NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'PATIENT',
            name VARCHAR(255) NULL,
            avatar_url VARCHAR(500) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
            date_of_birth DATE NULL,
            address TEXT NULL,
            gender VARCHAR(10) NULL,
            last_login_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        $_db->exec("CREATE TABLE IF NOT EXISTS patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            assigned_doctor_id INT NULL,
            age INT NULL,
            gender VARCHAR(10) NULL,
            address TEXT NULL,
            medical_id VARCHAR(50) NULL,
            blood_group VARCHAR(10) NULL,
            height DECIMAL(5,2) NULL,
            weight DECIMAL(5,2) NULL,
            medical_history TEXT NULL,
            allergies TEXT NULL,
            emergency_contact VARCHAR(255) NULL,
            current_medications TEXT NULL,
            profile_picture VARCHAR(500) NULL,
            profile_image_url VARCHAR(500) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_patients_user (user_id),
            INDEX idx_patients_doctor (assigned_doctor_id)
        )");

        $_db->exec("CREATE TABLE IF NOT EXISTS doctors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            specialization VARCHAR(255) NULL,
            license_number VARCHAR(100) NULL,
            hospital VARCHAR(255) NULL,
            experience_years INT DEFAULT 0,
            profile_picture VARCHAR(500) NULL,
            profile_image_url VARCHAR(500) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_doctors_user (user_id)
        )");

        $_db->exec("CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NOT NULL,
            appointment_date DATE NULL,
            appointment_time TIME NULL,
            title VARCHAR(255) NULL,
            description TEXT NULL,
            reason TEXT NULL,
            location VARCHAR(255) NULL,
            type VARCHAR(50) DEFAULT 'CONSULTATION',
            status VARCHAR(20) DEFAULT 'SCHEDULED',
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_appt_patient (patient_id, appointment_date),
            INDEX idx_appt_doctor (doctor_id, appointment_date)
        )");

        $_db->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) DEFAULT 'INFO',
            title VARCHAR(255) NULL,
            body TEXT NULL,
            message TEXT NULL,
            is_read TINYINT(1) DEFAULT 0,
            read_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notifications_user (user_id, created_at)
        )");

        $_db->exec("CREATE TABLE IF NOT EXISTS medications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            generic_name VARCHAR(255) NULL,
            description TEXT NULL,
            category VARCHAR(100) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $_db->exec("CREATE TABLE IF NOT EXISTS patient_medications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            medication_id INT NULL,
            prescribed_by INT NULL,
            doctor_id INT NULL,
            name_override VARCHAR(255) NULL,
            medication_name VARCHAR(255) NULL,
            dosage VARCHAR(100) NULL,
            instructions TEXT NULL,
            duration VARCHAR(100) NULL,
            is_morning TINYINT(1) DEFAULT 0,
            is_afternoon TINYINT(1) DEFAULT 0,
            is_night TINYINT(1) DEFAULT 0,
            food_relation VARCHAR(100) NULL,
            frequency VARCHAR(100) NULL,
            frequency_per_day INT NULL,
            start_date DATE NULL,
            end_date DATE NULL,
            active TINYINT(1) DEFAULT 1,
            is_active TINYINT(1) DEFAULT 1,
            status VARCHAR(20) DEFAULT 'ACTIVE',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_pm_patient (patient_id, active)
        )");

        $_db->exec("CREATE TABLE IF NOT EXISTS medication_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_medication_id INT NULL,
            patient_id INT NULL,
            medication_id INT NULL,
            status VARCHAR(20) DEFAULT 'taken',
            taken_at DATETIME NULL,
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_med_logs_patient (patient_id, taken_at)
        )");

        $_db->exec("CREATE TABLE IF NOT EXISTS symptoms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            date DATE NULL,
            pain_level INT NULL,
            stiffness_level INT NULL,
            fatigue_level INT NULL,
            joint_count INT NULL,
            notes TEXT NULL,
            symptom_type VARCHAR(100) NULL,
            severity VARCHAR(20) NULL,
            logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_symptoms_patient (patient_id, date)
        )");

        $_db->exec("CREATE TABLE IF NOT EXISTS settings (
            user_id INT NOT NULL PRIMARY KEY,
            notifications_enabled TINYINT(1) DEFAULT 1,
            medication_reminders TINYINT(1) DEFAULT 1,
            appointment_reminders TINYINT(1) DEFAULT 1,
            theme VARCHAR(32) DEFAULT 'light',
            language VARCHAR(16) DEFAULT 'en',
            `key` VARCHAR(100) NULL,
            `value` TEXT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        $_db->exec("CREATE TABLE IF NOT EXISTS crp_measurements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NULL,
            report_id INT NULL,
            crp_value DECIMAL(10,4) NULL,
            measurement_unit VARCHAR(20) DEFAULT 'mg/L',
            measurement_date DATE NULL,
            notes TEXT NULL,
            measured_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_crp_patient (patient_id, measurement_date)
        )");

        $_db->exec("CREATE TABLE IF NOT EXISTS rehab_exercises (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rehab_name VARCHAR(255) NULL,
            name VARCHAR(255) NULL,
            description TEXT NULL,
            benefits TEXT NULL,
            category VARCHAR(100) NULL,
            video_url VARCHAR(500) NULL,
            sets INT NULL,
            reps INT NULL,
            frequency_per_week INT NULL,
            status VARCHAR(20) DEFAULT 'ACTIVE',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        $_db->exec("CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NULL,
            doctor_id INT NULL,
            title VARCHAR(255) NULL,
            description TEXT NULL,
            file_path VARCHAR(500) NULL,
            file_url VARCHAR(500) NULL,
            file_name VARCHAR(255) NULL,
            file_size INT NULL,
            mime_type VARCHAR(120) NULL,
            status VARCHAR(20) DEFAULT 'PENDING',
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_reports_patient (patient_id, uploaded_at)
        )");

        // ── USERS ──────────────────────────────────────────
        $addCol('users', 'avatar_url',     'VARCHAR(500) NULL');
        $addCol('users', 'date_of_birth',  'DATE NULL');
        $addCol('users', 'address',        'TEXT NULL');
        $addCol('users', 'gender',         'VARCHAR(10) NULL');
        $addCol('users', 'last_login_at',  'DATETIME NULL');
        $addCol('users', 'phone',          'VARCHAR(20) NULL');
        $addCol('users', 'name',           'VARCHAR(255) NULL');
        $addCol('users', 'status',         "VARCHAR(20) NOT NULL DEFAULT 'ACTIVE'");
        try { $_db->exec("UPDATE users SET status='ACTIVE' WHERE status IS NULL OR status=''"); } catch(\Throwable $e) {}

        // ── APPOINTMENTS ───────────────────────────────────
        $addCol('appointments', 'appointment_date', 'DATE NULL');
        $addCol('appointments', 'appointment_time', 'TIME NULL');
        $addCol('appointments', 'title',            'VARCHAR(255) NULL');
        $addCol('appointments', 'description',      'TEXT NULL');
        $addCol('appointments', 'location',         'VARCHAR(255) NULL');
        $addCol('appointments', 'type',             "VARCHAR(50) DEFAULT 'CONSULTATION'");
        $addCol('appointments', 'status',           "VARCHAR(20) DEFAULT 'SCHEDULED'");
        try {
            if ($_db->query("SHOW COLUMNS FROM appointments LIKE 'start_time'")->fetch()) {
                $_db->exec("UPDATE appointments SET appointment_date=DATE(start_time), appointment_time=TIME(start_time) WHERE appointment_date IS NULL AND start_time IS NOT NULL");
            }
        } catch(\Throwable $e) {}

        // ── PATIENTS ───────────────────────────────────────
        $addCol('patients', 'age',               'INT NULL');
        $addCol('patients', 'gender',            'VARCHAR(10) NULL');
        $addCol('patients', 'address',           'TEXT NULL');
        $addCol('patients', 'assigned_doctor_id','INT NULL');
        $addCol('patients', 'medical_id',        'VARCHAR(50) NULL');
        $addCol('patients', 'blood_group',       'VARCHAR(10) NULL');
        $addCol('patients', 'height',            'DECIMAL(5,2) NULL');
        $addCol('patients', 'weight',            'DECIMAL(5,2) NULL');
        $addCol('patients', 'medical_history',   'TEXT NULL');
        $addCol('patients', 'allergies',         'TEXT NULL');
        $addCol('patients', 'emergency_contact', 'VARCHAR(255) NULL');
        $addCol('patients', 'current_medications','TEXT NULL');

        // ── DOCTORS ────────────────────────────────────────
        $addCol('doctors', 'user_id',        'INT NULL');
        $addCol('doctors', 'specialization', 'VARCHAR(255) NULL');
        $addCol('doctors', 'updated_at',     'DATETIME NULL');

        // ── NOTIFICATIONS ──────────────────────────────────
        $addCol('notifications', 'user_id', 'INT NULL');
        $addCol('notifications', 'title',   'VARCHAR(255) NULL');
        $addCol('notifications', 'body',    'TEXT NULL');
        $addCol('notifications', 'message', 'TEXT NULL');
        $addCol('notifications', 'type',    "VARCHAR(50) DEFAULT 'INFO'");
        $addCol('notifications', 'read_at', 'DATETIME NULL');

        // ── PATIENT_MEDICATIONS ────────────────────────────
        $addCol('patient_medications', 'patient_id',       'INT NULL');
        $addCol('patient_medications', 'medication_id',    'INT NULL');
        $addCol('patient_medications', 'prescribed_by',    'INT NULL');
        $addCol('patient_medications', 'doctor_id',        'INT NULL');
        $addCol('patient_medications', 'name_override',    'VARCHAR(255) NULL');
        $addCol('patient_medications', 'medication_name',  'VARCHAR(255) NULL');
        $addCol('patient_medications', 'dosage',           'VARCHAR(100) NULL');
        $addCol('patient_medications', 'instructions',     'TEXT NULL');
        $addCol('patient_medications', 'duration',         'VARCHAR(100) NULL');
        $addCol('patient_medications', 'is_morning',       'TINYINT(1) DEFAULT 0');
        $addCol('patient_medications', 'is_afternoon',     'TINYINT(1) DEFAULT 0');
        $addCol('patient_medications', 'is_night',         'TINYINT(1) DEFAULT 0');
        $addCol('patient_medications', 'food_relation',    'VARCHAR(100) NULL');
        $addCol('patient_medications', 'frequency',        'VARCHAR(100) NULL');
        $addCol('patient_medications', 'frequency_per_day','INT NULL');
        $addCol('patient_medications', 'start_date',       'DATE NULL');
        $addCol('patient_medications', 'end_date',         'DATE NULL');
        $addCol('patient_medications', 'active',           'TINYINT(1) DEFAULT 1');
        $addCol('patient_medications', 'is_active',        'TINYINT(1) DEFAULT 1');
        $addCol('patient_medications', 'status',           "VARCHAR(20) DEFAULT 'ACTIVE'");
        $addCol('patient_medications', 'created_at',       'DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
        $addCol('patient_medications', 'updated_at',       'DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
        try {
            $_db->exec("ALTER TABLE patient_medications MODIFY COLUMN medication_name VARCHAR(255) NULL");
            $_db->exec("ALTER TABLE patient_medications MODIFY COLUMN dosage VARCHAR(100) NULL");
            $_db->exec("ALTER TABLE patient_medications MODIFY COLUMN frequency VARCHAR(100) NULL");
            $_db->exec("ALTER TABLE patient_medications MODIFY COLUMN start_date DATE NULL");
        } catch(\Throwable $e) {}

        // ── MEDICATION_LOGS ────────────────────────────────
        $addCol('medication_logs', 'patient_medication_id', 'INT NULL');
        $addCol('medication_logs', 'patient_id',            'INT NULL');
        $addCol('medication_logs', 'status',                "VARCHAR(20) DEFAULT 'taken'");
        $addCol('medication_logs', 'taken_at',              'DATETIME NULL');
        $addCol('medication_logs', 'notes',                 'TEXT NULL');
        $addCol('medication_logs', 'created_at',            'DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
        try {
            $_db->exec("ALTER TABLE medication_logs MODIFY COLUMN medication_id INT NULL");
            $_db->exec("ALTER TABLE medication_logs MODIFY COLUMN status VARCHAR(20) NULL DEFAULT 'taken'");
        } catch(\Throwable $e) {}

        // ── SYMPTOMS ───────────────────────────────────────
        $addCol('symptoms', 'patient_id',       'INT NULL');
        $addCol('symptoms', 'date',             'DATE NULL');
        $addCol('symptoms', 'pain_level',       'INT NULL');
        $addCol('symptoms', 'stiffness_level',  'INT NULL');
        $addCol('symptoms', 'fatigue_level',    'INT NULL');
        $addCol('symptoms', 'joint_count',      'INT NULL');
        $addCol('symptoms', 'notes',            'TEXT NULL');
        $addCol('symptoms', 'created_at',       'DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
        $addCol('symptoms', 'symptom_type',     'VARCHAR(100) NULL');
        $addCol('symptoms', 'severity',         'VARCHAR(20) NULL');
        $addCol('symptoms', 'logged_at',        'DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
        try {
            $_db->exec("ALTER TABLE symptoms MODIFY COLUMN symptom_type VARCHAR(100) NULL");
            $_db->exec("ALTER TABLE symptoms MODIFY COLUMN severity VARCHAR(20) NULL");
        } catch(\Throwable $e) {}

        // ── HEALTH_METRICS ─────────────────────────────────
        $_db->exec("CREATE TABLE IF NOT EXISTS health_metrics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            metric_type VARCHAR(100) NOT NULL,
            value DECIMAL(10,4) NOT NULL,
            unit VARCHAR(50) NULL,
            recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            notes TEXT NULL
        )");

        // ── SETTINGS ───────────────────────────────────────
        $_db->exec("CREATE TABLE IF NOT EXISTS user_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            `key` VARCHAR(100) NOT NULL,
            `value` TEXT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_key (user_id, `key`)
        )");
        // Also patch settings table if it exists instead
        $addCol('settings', 'user_id',    'INT NULL');
        $addCol('settings', '`key`',      'VARCHAR(100) NULL');
        $addCol('settings', '`value`',    'TEXT NULL');
        $addCol('settings', 'updated_at', 'DATETIME NULL');
        try { $_db->exec("ALTER TABLE settings ADD UNIQUE KEY uq_user_key (user_id, `key`)"); } catch(\Throwable $e) {}

        // ── CRP_MEASUREMENTS ───────────────────────────────
        $addCol('crp_measurements', 'patient_id',       'INT NULL');
        $addCol('crp_measurements', 'doctor_id',        'INT NULL');
        $addCol('crp_measurements', 'report_id',        'INT NULL');
        $addCol('crp_measurements', 'crp_value',        'DECIMAL(10,4) NULL');
        $addCol('crp_measurements', 'measurement_unit', "VARCHAR(20) DEFAULT 'mg/L'");
        $addCol('crp_measurements', 'measurement_date', 'DATE NULL');
        $addCol('crp_measurements', 'notes',            'TEXT NULL');
        $addCol('crp_measurements', 'measured_at',      'DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
        $addCol('crp_measurements', 'updated_at',       'DATETIME NULL');

        // ── REHAB_EXERCISES ────────────────────────────────
        $addCol('rehab_exercises', 'rehab_name',         'VARCHAR(255) NULL');
        $addCol('rehab_exercises', 'name',               'VARCHAR(255) NULL');
        $addCol('rehab_exercises', 'description',        'TEXT NULL');
        $addCol('rehab_exercises', 'benefits',           'TEXT NULL');
        $addCol('rehab_exercises', 'category',           'VARCHAR(100) NULL');
        $addCol('rehab_exercises', 'video_url',          'VARCHAR(500) NULL');
        $addCol('rehab_exercises', 'sets',               'INT NULL');
        $addCol('rehab_exercises', 'reps',               'INT NULL');
        $addCol('rehab_exercises', 'frequency_per_week', 'INT NULL');
        $addCol('rehab_exercises', 'status',             "VARCHAR(20) DEFAULT 'ACTIVE'");
        try {
            $_db->exec("UPDATE rehab_exercises SET rehab_name=name WHERE rehab_name IS NULL AND name IS NOT NULL");
            $_db->exec("UPDATE rehab_exercises SET name=rehab_name WHERE name IS NULL AND rehab_name IS NOT NULL");
        } catch(\Throwable $e) {}

        // ── PATIENT_REHAB_ASSIGNMENT ───────────────────────
        $_db->exec("CREATE TABLE IF NOT EXISTS patient_rehab_assignment (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            rehab_id INT NOT NULL,
            assigned_by_doctor_id INT NULL,
            sets INT DEFAULT 3,
            reps INT DEFAULT 10,
            status VARCHAR(20) DEFAULT 'pending',
            assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        // ── EDUCATION_ARTICLES ─────────────────────────────
        $_db->exec("CREATE TABLE IF NOT EXISTS education_articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            content TEXT,
            summary TEXT,
            category VARCHAR(100),
            author VARCHAR(255),
            image_url VARCHAR(500),
            published_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_slug (slug)
        )");
        if ((int)$_db->query("SELECT COUNT(*) FROM education_articles")->fetchColumn() === 0) {
            $_db->exec("INSERT INTO education_articles (title,slug,content,summary,category,author) VALUES
                ('What is Rheumatoid Arthritis','what-is-ra','Rheumatoid arthritis (RA) is an autoimmune disease causing chronic joint inflammation. Early diagnosis and treatment are key.','Learn about RA basics.','BASICS','Medical Team'),
                ('Managing RA Pain Daily','managing-ra-pain','Daily strategies including exercise, medication adherence, and lifestyle changes improve quality of life.','Tips for daily RA pain management.','LIFESTYLE','Medical Team'),
                ('Diet and Nutrition for RA','diet-nutrition-ra','An anti-inflammatory diet with omega-3 fatty acids, antioxidants, and fiber helps reduce inflammation.','How diet affects RA.','NUTRITION','Medical Team'),
                ('Exercise and Physical Therapy','exercise-physical-therapy','Low-impact activities like yoga, tai chi, and swimming maintain joint function and muscle strength.','Exercise importance in RA.','EXERCISE','Medical Team'),
                ('Understanding Your Medications','understanding-medications','DMARDs, biologics, and NSAIDs each work differently to reduce inflammation and slow disease progression.','Guide to common RA medications.','MEDICATION','Medical Team')
            ");
        }

        // ── CHATBOT_CONVERSATIONS ──────────────────────────
        $_db->exec("CREATE TABLE IF NOT EXISTS chatbot_conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            user_message TEXT NULL,
            bot_response TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $addCol('chatbot_conversations', 'user_id',    'INT NULL');
        $addCol('chatbot_conversations', 'message',    'TEXT NULL');
        $addCol('chatbot_conversations', 'response',   'TEXT NULL');
        $addCol('chatbot_conversations', 'created_at', 'DATETIME NULL DEFAULT CURRENT_TIMESTAMP');

        // ── REPORTS ────────────────────────────────────────
        $addCol('reports', 'patient_id',  'INT NULL');
        $addCol('reports', 'doctor_id',   'INT NULL');
        $addCol('reports', 'title',       'VARCHAR(255) NULL');
        $addCol('reports', 'description', 'TEXT NULL');
        $addCol('reports', 'file_path',   'VARCHAR(500) NULL');
        $addCol('reports', 'file_url',    'VARCHAR(500) NULL');
        $addCol('reports', 'file_name',   'VARCHAR(255) NULL');
        $addCol('reports', 'file_size',   'INT NULL');
        $addCol('reports', 'mime_type',   'VARCHAR(120) NULL');
        $addCol('reports', 'status',      "VARCHAR(20) DEFAULT 'PENDING'");
        $addCol('reports', 'uploaded_at', 'DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
        $addCol('reports', 'created_at',  'DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
        $addCol('reports', 'updated_at',  'DATETIME NULL DEFAULT CURRENT_TIMESTAMP');

        // ── SEED REHAB EXERCISES if empty ─────────────────
        if ((int)$_db->query("SELECT COUNT(*) FROM rehab_exercises")->fetchColumn() === 0) {
            $_db->exec("INSERT INTO rehab_exercises (rehab_name,name,description,category,sets,reps,frequency_per_week,video_url) VALUES
                ('Wrist Flexion','Wrist Flexion','Gently bend your wrist forward and hold for 5 seconds','HAND',3,10,5,'ex_001_wrist_flexion.mp4'),
                ('Wrist Rotation','Wrist Rotation','Rotate your wrist in circles, 10 times each direction','HAND',3,10,5,'ex_002_wrist_rotation.mp4'),
                ('Thumb Opposition','Thumb Opposition','Touch your thumb to each finger tip in sequence','HAND',3,10,5,'ex_003_thumb_opposition.mp4'),
                ('Thumb Flexion','Thumb Flexion','Bend your thumb across your palm and hold','HAND',3,10,5,'ex_004_thumb_flexion.mp4'),
                ('Finger Flexion','Finger Flexion','Curl your fingers into a fist slowly and release','HAND',3,10,5,'ex_005_finger_flexion.mp4'),
                ('Finger Extension','Finger Extension','Spread your fingers wide apart and hold','HAND',3,10,5,'ex_006_finger_extension.mp4'),
                ('Finger Pinch','Finger Pinch','Pinch a soft ball or putty between fingers','HAND',3,10,5,'ex_007_finger_pinch.mp4'),
                ('Knee Flexion','Knee Flexion','Slowly bend and straighten your knee while seated','KNEE',3,10,3,'ex_008_knee_flexion.mp4'),
                ('Hip Flexion','Hip Flexion','Lift your knee toward your chest while standing','HIP',3,10,3,'ex_009_hip_flexion.mp4'),
                ('Hip Abduction','Hip Abduction','Move your leg out to the side while standing','HIP',3,10,3,'ex_010_hip_abduction.mp4')
            ");
        }

} catch (\Throwable $_migEx) {
    error_log('Auto-migration error: ' . $_migEx->getMessage());
}
// ============================================================
// END AUTO-MIGRATION
// ============================================================

use Src\Utils\Response;
use Src\Middlewares\Auth;
use Src\Controllers\AuthController;
use Src\Controllers\UserController;
use Src\Controllers\PatientController;
use Src\Controllers\DoctorController;
use Src\Controllers\AppointmentController;
use Src\Controllers\ReportController;
use Src\Controllers\MedicationController;
use Src\Controllers\RehabController;
use Src\Controllers\NotificationController;
use Src\Controllers\EducationController;
use Src\Controllers\SymptomController;
use Src\Controllers\MetricController;
use Src\Controllers\SettingsController;
use Src\Controllers\AdminController;
use Src\Controllers\ReportNoteController;
use Src\Controllers\CrpController;
use Src\Controllers\ChatbotController;
use Src\Controllers\ExerciseController;
use Src\Controllers\ExerciseSessionController;
use Src\Controllers\RehabV2Controller;


// Basic CORS handling for preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Src\Config\Cors::preflight();
    exit;
}
Src\Config\Cors::allow();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['PATH_INFO'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// DEBUG: Force write to file to see what's happening
$debugInfo = [
    'method' => $method,
    'uri' => $uri,
    'PATH_INFO' => $_SERVER['PATH_INFO'] ?? 'not set',
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'not set',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'not set'
];
file_put_contents(__DIR__ . '/debug_uri.txt', date('[Y-m-d H:i:s] ') . json_encode($debugInfo) . PHP_EOL, FILE_APPEND);

// CRITICAL: Log all requests
file_put_contents(__DIR__ . '/api_log.txt', date('[Y-m-d H:i:s] ') . $method . ' ' . $uri . ' (PATH_INFO: ' . ($_SERVER['PATH_INFO'] ?? 'not set') . ', REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'not set') . ')' . PHP_EOL, FILE_APPEND);

// DEBUG: Log delete requests specifically
if ($method === 'DELETE' || strpos($uri, 'delete') !== false) {
    file_put_contents(__DIR__ . '/api_log.txt', date('[Y-m-d H:i:s] DELETE_DEBUG - METHOD: ') . $method . ', URI: ' . $uri . ', REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'not set') . PHP_EOL, FILE_APPEND);
}

// ADDITIONAL: Log POST data for report endpoints
if ($method === 'POST' && strpos($uri, 'reports') !== false) {
    file_put_contents(__DIR__ . '/api_log.txt', date('[Y-m-d H:i:s] REPORTS_POST - FILES: ') . print_r($_FILES, true) . PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__ . '/api_log.txt', date('[Y-m-d H:i:s] REPORTS_POST - POST: ') . print_r($_POST, true) . PHP_EOL, FILE_APPEND);
}

// Ensure URI starts with /
if ($uri === '' || ($uri[0] !== '/')) {
    $uri = '/' . $uri;
}

// Exclude certain helper files
$allowedFiles = ['admin-api.php', 'doctor-patients.php', 'clear-cache.php'];

// For XAMPP compatibility, we don't need the file serving logic
// since we're using PATH_INFO for routing

// If request targets non-API path and matches known test files or explicitly allowed filenames, return 404 JSON
if (strpos($uri, '/api/v1/') !== 0) {
    $testFiles = ['test-', 'debug-', 'api-info.php'];
    $isTestFile = false;
    foreach ($testFiles as $prefix) {
        if (strpos(basename($uri), $prefix) === 0) {
            $isTestFile = true;
            break;
        }
    }

    if ($isTestFile || in_array(basename($uri), $allowedFiles)) {
        http_response_code(404);
        Response::json([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'File not found. If this is a test file, access it directly via browser.'
            ]
        ], 404);
        exit;
    }
}

// Route helper
function route(string $method, string $path): bool {
    global $uri;
    return $_SERVER['REQUEST_METHOD'] === $method && $uri === $path;
}

// ======================
// HEALTH CHECK
// ======================
if (route('GET', '/api/v1/health')) { 
    Response::json([
        'success' => true,
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'server' => 'MyRA Journey API - UPDATED VERSION 2.0',
        'debug' => [
            'uri' => $uri,
            'method' => $method,
            'PATH_INFO' => $_SERVER['PATH_INFO'] ?? 'not set',
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'not set'
        ]
    ]); 
    exit; 
}

// ======================
// DEBUG ENDPOINT
// ======================
if (route('GET', '/api/v1/debug')) {
    Response::json([
        'success' => true,
        'uri' => $uri,
        'method' => $method,
        'path_info' => $_SERVER['PATH_INFO'] ?? 'not set',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'not set'
    ]);
    exit;
}

// ======================
// AUTH ROUTES
// ======================
if (route('POST', '/api/v1/auth/register')) { (new AuthController())->register(); exit; }
if (route('POST', '/api/v1/auth/login')) { 
    file_put_contents(__DIR__ . '/api_log.txt', date('[Y-m-d H:i:s] LOGIN_ROUTE_MATCHED - Calling AuthController->login()') . PHP_EOL, FILE_APPEND);
    try {
        (new AuthController())->login(); 
    } catch (\Exception $e) {
        file_put_contents(__DIR__ . '/api_log.txt', date('[Y-m-d H:i:s] LOGIN_ERROR: ') . $e->getMessage() . PHP_EOL, FILE_APPEND);
        Response::json([
            'success' => false,
            'error' => [
                'code' => 'SERVER_ERROR',
                'message' => 'Login failed: ' . $e->getMessage()
            ]
        ], 500);
    }
    exit; 
}
if (route('GET', '/api/v1/auth/me')) { Auth::requireAuth(); (new AuthController())->me(); exit; }
if (route('POST', '/api/v1/auth/delete-account')) { Auth::requireAuth(); (new AuthController())->deleteAccount(); exit; }
if (route('DELETE', '/api/v1/auth/account')) { Auth::requireAuth(); (new AuthController())->deleteAccount(); exit; }
if (route('POST', '/api/v1/auth/forgot-password')) { (new AuthController())->forgotPassword(); exit; }
if (route('POST', '/api/v1/auth/reset-password')) { (new AuthController())->resetPassword(); exit; }
if (route('POST', '/api/v1/auth/change-password')) { Auth::requireAuth(); (new AuthController())->changePassword(); exit; }

// ======================
// USER ROUTES
// ======================
if (route('PUT', '/api/v1/users/me')) { Auth::requireAuth(); (new UserController())->updateMe(); exit; }
// POST alternative for Android compatibility
if (route('POST', '/api/v1/users/me/update')) { Auth::requireAuth(); (new UserController())->updateMe(); exit; }
if (route('GET', '/api/v1/users')) { Auth::requireAuth(); (new AdminController())->listUsers(); exit; }
if (route('GET', '/api/v1/users/doctors')) { Auth::requireAuth(); (new AdminController())->listDoctors(); exit; }

// ======================
// PATIENT ROUTES
// ======================
if (route('GET', '/api/v1/patients/me/overview')) { Auth::requireAuth(); (new PatientController())->overviewMe(); exit; }
if (route('GET', '/api/v1/patients')) { Auth::requireAuth(); (new PatientController())->listAll(); exit; }
if (preg_match('#^/api/v1/patients/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new PatientController())->get((int)$m[1]); exit;
}
// Use POST for update instead of PUT (Android compatibility)
if (preg_match('#^/api/v1/patients/(\d+)/update$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new PatientController())->update((int)$m[1]); exit;
}

// ======================
// ADMIN ROUTES
// ======================
if (route('GET', '/api/v1/admin/test')) { Response::json(['success'=>true,'message'=>'Admin routes working','uri'=>$uri]); exit; }
if (route('GET', '/api/v1/admin/users')) { Auth::requireAuth(); (new AdminController())->listUsers(); exit; }
if (route('POST', '/api/v1/admin/users')) { Auth::requireAuth(); (new AdminController())->createUser(); exit; }
if (route('POST', '/api/v1/admin/assign-patient')) { Auth::requireAuth(); (new AdminController())->assignPatientToDoctor(); exit; }
if (route('GET', '/api/v1/admin/doctors')) { Auth::requireAuth(); (new AdminController())->listDoctors(); exit; }

// Delete user routes
if (preg_match('#^/api/v1/admin/users/(\d+)/delete$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new AdminController())->deleteUser(); exit;
}
if (preg_match('#^/api/v1/admin/users/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    Auth::requireAuth(); (new AdminController())->deleteUser(); exit;
}

// Get user by ID route
if (preg_match('#^/api/v1/admin/users/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new AdminController())->getUserById(); exit;
}

// Update user route
if (preg_match('#^/api/v1/admin/users/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    Auth::requireAuth(); (new AdminController())->updateUser(); exit;
}

// Update user status route
if (preg_match('#^/api/v1/admin/users/(\d+)/status$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    Auth::requireAuth(); (new AdminController())->updateUserStatus((int)$m[1]); exit;
}

// ======================
// DOCTOR ROUTES
// ======================
if (route('GET', '/api/v1/doctor/overview')) { Auth::requireAuth(); (new DoctorController())->overview(); exit; }
if (route('POST', '/api/v1/doctor/assign-medication')) { Auth::requireAuth(); (new MedicationController())->assign(); exit; }

// ======================
// APPOINTMENTS
// ======================
if (route('GET', '/api/v1/appointments')) { Auth::requireAuth(); (new AppointmentController())->list(); exit; }
if (route('POST', '/api/v1/appointments')) { Auth::requireAuth(); (new AppointmentController())->create(); exit; }
if (preg_match('#^/api/v1/appointments/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new AppointmentController())->get((int)$m[1]); exit;
}
if (preg_match('#^/api/v1/appointments/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    Auth::requireAuth(); (new AppointmentController())->update((int)$m[1]); exit;
}
if (preg_match('#^/api/v1/appointments/(\d+)/delete$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new AppointmentController())->delete((int)$m[1]); exit;
}

// ======================
// REPORT ROUTES
// ======================
if (route('GET', '/api/v1/reports')) { Auth::requireAuth(); (new ReportController())->list(); exit; }
if (route('POST', '/api/v1/reports')) { Auth::requireAuth(); (new ReportController())->create(); exit; }
if (preg_match('#^/api/v1/reports/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new ReportController())->get((int)$m[1]); exit;
}
if (preg_match('#^/api/v1/reports/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    Auth::requireAuth(); (new ReportController())->delete((int)$m[1]); exit;
}
// POST alternative for delete (Android compatibility)
if (preg_match('#^/api/v1/reports/(\d+)/delete$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new ReportController())->delete((int)$m[1]); exit;
}
if (route('POST', '/api/v1/reports/notes')) { Auth::requireAuth(); (new ReportNoteController())->create(); exit; }
if (preg_match('#^/api/v1/reports/(\d+)/notes$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new ReportNoteController())->get((int)$m[1]); exit;
}

// ⭐ STATUS ROUTE — required by mobile app to update report status
if (route('POST', '/api/v1/reports/status')) {
    Auth::requireAuth();
    (new ReportController())->updateStatus();
    exit;
}

// ======================
// CRP ROUTES
// ======================
if (route('GET', '/api/v1/crp/test')) { Response::json(['success'=>true,'message'=>'CRP routes working','timestamp'=>date('Y-m-d H:i:s')]); exit; }
if (preg_match('#^/api/v1/crp/history/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new CrpController())->getHistory((int)$m[1]); exit;
}
if (route('POST', '/api/v1/crp')) { Auth::requireAuth(); (new CrpController())->create(); exit; }

// ======================
// MEDICATION
// ======================
if (route('GET', '/api/v1/medications')) { Auth::requireAuth(); (new MedicationController())->search(); exit; }
if (route('GET', '/api/v1/patient-medications')) { Auth::requireAuth(); (new MedicationController())->listForPatient(); exit; }
if (route('POST', '/api/v1/patient-medications')) { Auth::requireAuth(); (new MedicationController())->assign(); exit; }
if (preg_match('#^/api/v1/patient-medications/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'PATCH') {
    Auth::requireAuth(); (new MedicationController())->setActive((int)$m[1]); exit;
}
if (preg_match('#^/api/v1/patient-medications/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    Auth::requireAuth(); (new MedicationController())->delete((int)$m[1]); exit;
}
// POST alternative for delete (Android compatibility)
if (preg_match('#^/api/v1/patient-medications/(\d+)/delete$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new MedicationController())->delete((int)$m[1]); exit;
}
if (route('GET', '/api/v1/medication-logs')) { Auth::requireAuth(); (new MedicationController())->listLogs(); exit; }
if (route('POST', '/api/v1/medication-logs')) { Auth::requireAuth(); (new MedicationController())->logIntake(); exit; }
if (route('POST', '/api/v1/medications/log')) { Auth::requireAuth(); (new MedicationController())->logIntake(); exit; }
if (route('GET', '/api/v1/medication-logs/test')) { Auth::requireAuth(); Response::json(['success'=>true,'message'=>'Medication logs endpoint working','timestamp'=>date('Y-m-d H:i:s')]); exit; }
if (route('POST', '/api/v1/medication-logs/debug')) { 
    Auth::requireAuth(); 
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    Response::json([
        'success'=>true,
        'message'=>'Debug endpoint working',
        'received_data'=>$input,
        'auth'=>$_SERVER['auth'] ?? [],
        'timestamp'=>date('Y-m-d H:i:s')
    ]); 
    exit; 
}

// Admin medication management
if (route('DELETE', '/api/v1/admin/patient-medications/clear-all')) { Auth::requireAuth(); (new MedicationController())->clearAllPatientMedications(); exit; }
if (route('POST', '/api/v1/admin/patient-medications/clear-all/delete')) { Auth::requireAuth(); (new MedicationController())->clearAllPatientMedications(); exit; }
if (route('GET', '/api/v1/admin/patient-medications/all')) { Auth::requireAuth(); (new MedicationController())->getAllPatientMedications(); exit; }

// ======================
// REHAB SYSTEM (NEW)
// ======================
// Test endpoint without auth
if (route('GET', '/api/v1/rehabs-test')) {
    Response::json(['success' => true, 'message' => 'Rehab test endpoint working', 'uri' => $uri]);
    exit;
}

if (route('GET', '/api/v1/rehabs')) { Auth::requireAuth(); (new RehabController())->listAll(); exit; }
if (route('POST', '/api/v1/assign-rehab')) { Auth::requireAuth(); (new RehabController())->assign(); exit; }
// Android aliases: /rehab/exercises and /rehab/assignments
if (route('GET', '/api/v1/rehab/exercises')) {
    // Return exercise library from rehab_exercises table
    Auth::requireAuth();
    $db = \Src\Config\DB::conn();
    $rows = $db->query("SELECT * FROM rehab_exercises WHERE status='ACTIVE' ORDER BY category, name")->fetchAll();
    \Src\Utils\Response::json(['success'=>true,'data'=>$rows]);
    exit;
}
if (route('GET', '/api/v1/rehab/assignments')) { Auth::requireAuth(); (new RehabV2Controller())->listForPatient(); exit; }
if (route('POST', '/api/v1/rehab/assign')) { Auth::requireAuth(); (new RehabV2Controller())->assign(); exit; }
if (preg_match('#^/api/v1/patient/(\d+)/rehabs$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new RehabController())->listForPatient((int)$m[1]); exit;
}
// Android alias: GET /api/v1/patients/{id}/rehab-plans
if (preg_match('#^/api/v1/patients/(\d+)/rehab-plans$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new RehabController())->listForPatient((int)$m[1]); exit;
}
if (route('POST', '/api/v1/rehab-status')) { Auth::requireAuth(); (new RehabController())->updateStatus(); exit; }
if (preg_match('#^/api/v1/rehab-exercises/(\d+)/delete$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new RehabController())->deleteAssignment((int)$m[1]); exit;
}


// ======================
// NOTIFICATIONS
// ======================
if (route('GET', '/api/v1/notifications')) { Auth::requireAuth(); (new NotificationController())->listMine(); exit; }
if (route('PUT', '/api/v1/notifications/read-all')) { Auth::requireAuth(); (new NotificationController())->markAllRead(); exit; }
if (preg_match('#^/api/v1/notifications/(\d+)/read$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new NotificationController())->markRead((int)$m[1]); exit;
}

// ======================
// EDUCATION
// ======================
if (route('GET', '/api/v1/education/articles')) { (new EducationController())->list(); exit; }
// Android alias: /education maps to /education/articles
if (route('GET', '/api/v1/education')) { (new EducationController())->list(); exit; }
if (preg_match('#^/api/v1/education/articles/([A-Za-z0-9_-]+)$#', $uri, $m)
    && $_SERVER['REQUEST_METHOD'] === 'GET') {
    (new EducationController())->getBySlug($m[1]); exit;
}
// Android alias: /education/{id} by numeric id
if (preg_match('#^/api/v1/education/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    (new EducationController())->getById((int)$m[1]); exit;
}

// ======================
// SYMPTOMS
// ======================
if (route('GET', '/api/v1/symptoms')) { Auth::requireAuth(); (new SymptomController())->list(); exit; }
if (route('POST', '/api/v1/symptoms')) { Auth::requireAuth(); (new SymptomController())->create(); exit; }

// ======================
// METRICS
// ======================
if (route('GET', '/api/v1/health-metrics')) { Auth::requireAuth(); (new MetricController())->list(); exit; }
if (route('POST', '/api/v1/health-metrics')) { Auth::requireAuth(); (new MetricController())->create(); exit; }
// Android alias: /metrics
if (route('GET', '/api/v1/metrics')) { Auth::requireAuth(); (new MetricController())->list(); exit; }
if (route('POST', '/api/v1/metrics')) { Auth::requireAuth(); (new MetricController())->create(); exit; }

// ======================
// SETTINGS
// ======================
if (route('GET', '/api/v1/settings')) { Auth::requireAuth(); (new SettingsController())->getMine(); exit; }
if (route('PUT', '/api/v1/settings')) { Auth::requireAuth(); (new SettingsController())->putMine(); exit; }
// POST alternatives for Android compatibility
if (route('POST', '/api/v1/settings')) { Auth::requireAuth(); (new SettingsController())->putMine(); exit; }
if (route('POST', '/api/v1/settings/update')) { Auth::requireAuth(); (new SettingsController())->putMine(); exit; }

// ======================
// CHATBOT & CONVERSATION MANAGEMENT
// ======================
if (route('POST', '/api/v1/chatbot/chat')) { Auth::requireAuth(); (new ChatbotController())->chat(); exit; }
if (route('POST', '/api/v1/chat/send')) { Auth::requireAuth(); (new ChatbotController())->chat(); exit; }
// Android alias: /chatbot/message
if (route('POST', '/api/v1/chatbot/message')) { Auth::requireAuth(); (new ChatbotController())->chat(); exit; }
if (route('GET', '/api/v1/chatbot/history')) { Auth::requireAuth(); (new ChatbotController())->history(); exit; }
if (route('GET', '/api/v1/chat/history')) { Auth::requireAuth(); (new ChatbotController())->history(); exit; }
if (route('GET', '/api/v1/chatbot/session/history')) { Auth::requireAuth(); (new ChatbotController())->sessionHistory(); exit; }
if (route('POST', '/api/v1/chatbot/session/end')) { Auth::requireAuth(); (new ChatbotController())->endSession(); exit; }
if (route('GET', '/api/v1/chatbot/session/context')) { Auth::requireAuth(); (new ChatbotController())->getContext(); exit; }

// ======================
// EXERCISE TRACKING SYSTEM
// ======================
// Exercise Library Routes
if (route('GET', '/api/v1/exercises')) { Auth::requireAuth(); (new ExerciseController())->getAllExercises(); exit; }
if (preg_match('#^/api/v1/exercises/([A-Za-z0-9_-]+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new ExerciseController())->getExerciseById($m[1]); exit;
}
if (preg_match('#^/api/v1/exercises/category/([A-Z]+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new ExerciseController())->getExercisesByCategory($m[1]); exit;
}

// Exercise Assignment Routes
if (route('POST', '/api/v1/exercise-assignments')) { Auth::requireAuth(); (new ExerciseController())->createAssignment(); exit; }
if (route('GET', '/api/v1/exercise-assignments/patient')) { Auth::requireAuth(); (new ExerciseController())->getPatientAssignments(); exit; }
if (route('GET', '/api/v1/exercise-assignments/doctor')) { Auth::requireAuth(); (new ExerciseController())->getDoctorAssignments(); exit; }
if (preg_match('#^/api/v1/exercise-assignments/([A-Za-z0-9_-]+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    Auth::requireAuth(); (new ExerciseController())->updateAssignment($m[1]); exit;
}
if (preg_match('#^/api/v1/exercise-assignments/([A-Za-z0-9_-]+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    Auth::requireAuth(); (new ExerciseController())->deleteAssignment($m[1]); exit;
}

// Exercise Session Routes
if (route('POST', '/api/v1/exercise-sessions')) { Auth::requireAuth(); (new ExerciseSessionController())->createSession(); exit; }
if (preg_match('#^/api/v1/exercise-sessions/patient/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new ExerciseSessionController())->getPatientSessions((int)$m[1]); exit;
}
if (preg_match('#^/api/v1/exercise-sessions/([A-Za-z0-9_-]+)/report$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new ExerciseSessionController())->generateReport($m[1]); exit;
}

// Exercise Report Routes
if (preg_match('#^/api/v1/exercise-reports/patient/(\d+)$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new ExerciseSessionController())->getPatientReports((int)$m[1]); exit;
}



// ======================
// AI ROUTES - Medical Report Analysis & Predictions
// ======================
use Src\Controllers\AIController;

// Process report with OCR
if (route('POST', '/api/v1/ai/reports/process')) { Auth::requireAuth(); (new AIController())->processReport(); exit; }

// Get extracted data from report
if (preg_match('#^/api/v1/ai/reports/(\d+)/extracted-data$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new AIController())->getExtractedData((int)$m[1]); exit;
}

// Get trend analysis for patient
if (preg_match('#^/api/v1/ai/patients/(\d+)/trends$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new AIController())->getTrends((int)$m[1]); exit;
}

// Verify/correct extracted lab value
if (preg_match('#^/api/v1/ai/lab-values/(\d+)/verify$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireAuth(); (new AIController())->verifyLabValue((int)$m[1]); exit;
}

// Get flare-up prediction for patient
if (preg_match('#^/api/v1/ai/patients/(\d+)/prediction$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireAuth(); (new AIController())->getFlareUpPrediction((int)$m[1]); exit;
}

// Report actual flare-up occurrence
if (route('POST', '/api/v1/ai/flareup/report')) { Auth::requireAuth(); (new AIController())->reportFlareUp(); exit; }

// Get AI system status (for monitoring)
if (route('GET', '/api/v1/ai/status')) { Auth::requireAuth(); (new AIController())->getSystemStatus(); exit; }

// ======================
// 404
// ======================
Response::json([
    'success' => false,
    'error' => [
        'code' => 'NOT_FOUND',
        'message' => 'Endpoint not found'
    ]
], 404);
