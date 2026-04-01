<?php
/**
 * Fresh Database Setup for MyRA Journey
 * Creates database, tables, and initial data
 */

echo "=== MYRA JOURNEY - FRESH DATABASE SETUP ===\n\n";

// Database configuration
$host = '127.0.0.1';
$username = 'root';
$password = '';
$dbname = 'myrajourney';

try {
    // Step 1: Connect to MySQL (without database)
    echo "Step 1: Connecting to MySQL...\n";
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to MySQL\n\n";
    
    // Step 2: Create database
    echo "Step 2: Creating database '$dbname'...\n";
    $pdo->exec("DROP DATABASE IF EXISTS $dbname");
    $pdo->exec("CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database created\n\n";
    
    // Step 3: Connect to the new database
    echo "Step 3: Connecting to database '$dbname'...\n";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database\n\n";
    
    // Step 4: Create tables
    echo "Step 4: Creating tables...\n";
    
    // Users table
    $pdo->exec("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('PATIENT', 'DOCTOR', 'ADMIN') NOT NULL,
            phone VARCHAR(20),
            date_of_birth DATE,
            gender ENUM('MALE', 'FEMALE', 'OTHER', 'PREFER_NOT_TO_SAY'),
            address TEXT,
            profile_image VARCHAR(255),
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ users\n";
    
    // Patients table
    $pdo->exec("
        CREATE TABLE patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            medical_history TEXT,
            allergies TEXT,
            current_medications TEXT,
            emergency_contact VARCHAR(255),
            blood_group VARCHAR(10),
            height DECIMAL(5,2),
            weight DECIMAL(5,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ patients\n";
    
    // Doctors table
    $pdo->exec("
        CREATE TABLE doctors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            specialization VARCHAR(255),
            qualification VARCHAR(255),
            experience_years INT,
            license_number VARCHAR(100),
            consultation_fee DECIMAL(10,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ doctors\n";
    
    // Appointments table
    $pdo->exec("
        CREATE TABLE appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time TIME NOT NULL,
            status ENUM('SCHEDULED', 'COMPLETED', 'CANCELLED', 'RESCHEDULED') DEFAULT 'SCHEDULED',
            reason TEXT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_patient_id (patient_id),
            INDEX idx_doctor_id (doctor_id),
            INDEX idx_appointment_date (appointment_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ appointments\n";
    
    // Reports table
    $pdo->exec("
        CREATE TABLE reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            file_path VARCHAR(500),
            file_url VARCHAR(500),
            file_name VARCHAR(255),
            file_size INT,
            mime_type VARCHAR(100),
            status ENUM('PENDING', 'REVIEWED', 'NORMAL', 'ABNORMAL', 'ARCHIVED') DEFAULT 'PENDING',
            reviewed_by INT,
            reviewed_at TIMESTAMP NULL,
            ocr_processed TINYINT(1) DEFAULT 0,
            auto_extracted TINYINT(1) DEFAULT 0,
            extraction_confidence DECIMAL(3,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_patient_id (patient_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ reports\n";
    
    // Report notes table
    $pdo->exec("
        CREATE TABLE report_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            doctor_id INT NOT NULL,
            diagnosis_text TEXT,
            suggestions_text TEXT,
            crp_value DECIMAL(10,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_report_id (report_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ report_notes\n";
    
    // Lab values table (for AI OCR)
    $pdo->exec("
        CREATE TABLE lab_values (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            report_id INT NOT NULL,
            test_name VARCHAR(255) NOT NULL,
            test_value DECIMAL(10,2) NOT NULL,
            unit VARCHAR(50),
            normal_range_min DECIMAL(10,2),
            normal_range_max DECIMAL(10,2),
            is_abnormal TINYINT(1) DEFAULT 0,
            confidence_score DECIMAL(3,2),
            manually_verified TINYINT(1) DEFAULT 0,
            verified_by INT,
            verified_at TIMESTAMP NULL,
            extracted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_patient_id (patient_id),
            INDEX idx_report_id (report_id),
            INDEX idx_test_name (test_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ lab_values\n";
    
    // OCR processing logs table
    $pdo->exec("
        CREATE TABLE ocr_processing_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            processing_status ENUM('processing', 'completed', 'failed') DEFAULT 'processing',
            ocr_text TEXT,
            processing_time_ms INT,
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            INDEX idx_report_id (report_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ ocr_processing_logs\n";
    
    // Medications table
    $pdo->exec("
        CREATE TABLE medications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            generic_name VARCHAR(255),
            category VARCHAR(100),
            description TEXT,
            dosage_forms VARCHAR(255),
            common_dosages VARCHAR(255),
            side_effects TEXT,
            contraindications TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ medications\n";
    
    // Patient medications table
    $pdo->exec("
        CREATE TABLE patient_medications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            medication_name VARCHAR(255) NOT NULL,
            dosage VARCHAR(100) NOT NULL,
            frequency VARCHAR(100) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE,
            instructions TEXT,
            prescribed_by INT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (prescribed_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_patient_id (patient_id),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ patient_medications\n";
    
    // Medication logs table
    $pdo->exec("
        CREATE TABLE medication_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            medication_id INT NOT NULL,
            taken_at TIMESTAMP NOT NULL,
            status ENUM('TAKEN', 'MISSED', 'SKIPPED') NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (medication_id) REFERENCES patient_medications(id) ON DELETE CASCADE,
            INDEX idx_patient_id (patient_id),
            INDEX idx_medication_id (medication_id),
            INDEX idx_taken_at (taken_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ medication_logs\n";
    
    // Symptoms table
    $pdo->exec("
        CREATE TABLE symptoms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            symptom_type VARCHAR(100) NOT NULL,
            severity ENUM('MILD', 'MODERATE', 'SEVERE') NOT NULL,
            description TEXT,
            recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_patient_id (patient_id),
            INDEX idx_recorded_at (recorded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ symptoms\n";
    
    // Health metrics table
    $pdo->exec("
        CREATE TABLE health_metrics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            metric_type VARCHAR(100) NOT NULL,
            value DECIMAL(10,2) NOT NULL,
            unit VARCHAR(50),
            recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_patient_id (patient_id),
            INDEX idx_metric_type (metric_type),
            INDEX idx_recorded_at (recorded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ health_metrics\n";
    
    // CRP measurements table
    $pdo->exec("
        CREATE TABLE crp_measurements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            crp_value DECIMAL(10,2) NOT NULL,
            measurement_date DATE NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_patient_id (patient_id),
            INDEX idx_measurement_date (measurement_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ crp_measurements\n";
    
    // Notifications table
    $pdo->exec("
        CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(100) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ notifications\n";
    
    // Rehab plans table
    $pdo->exec("
        CREATE TABLE rehab_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            start_date DATE NOT NULL,
            end_date DATE,
            status ENUM('ACTIVE', 'COMPLETED', 'CANCELLED') DEFAULT 'ACTIVE',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_patient_id (patient_id),
            INDEX idx_doctor_id (doctor_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ rehab_plans\n";
    
    // Rehab exercises table
    $pdo->exec("
        CREATE TABLE rehab_exercises (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plan_id INT NOT NULL,
            exercise_name VARCHAR(255) NOT NULL,
            description TEXT,
            duration_minutes INT,
            repetitions INT,
            sets INT,
            instructions TEXT,
            video_url VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (plan_id) REFERENCES rehab_plans(id) ON DELETE CASCADE,
            INDEX idx_plan_id (plan_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ rehab_exercises\n";
    
    // Settings table
    $pdo->exec("
        CREATE TABLE settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            notifications_enabled TINYINT(1) DEFAULT 1,
            medication_reminders TINYINT(1) DEFAULT 1,
            appointment_reminders TINYINT(1) DEFAULT 1,
            theme VARCHAR(50) DEFAULT 'light',
            language VARCHAR(10) DEFAULT 'en',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_settings (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ settings\n";
    
    // Chatbot conversations table
    $pdo->exec("
        CREATE TABLE chatbot_conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            response TEXT NOT NULL,
            context JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ chatbot_conversations\n";
    
    echo "\n✓ All tables created successfully\n\n";
    
    // Step 5: Create initial users
    echo "Step 5: Creating initial users...\n";
    
    $users = [
        [
            'name' => 'Deepan Kumar',
            'email' => 'deepankumar@gmail.com',
            'password' => password_hash('Welcome@456', PASSWORD_BCRYPT),
            'role' => 'PATIENT',
            'phone' => '9876543210',
            'date_of_birth' => '1990-01-15',
            'gender' => 'MALE'
        ],
        [
            'name' => 'Dr. Sarah Johnson',
            'email' => 'doctor@test.com',
            'password' => password_hash('Patrol@987', PASSWORD_BCRYPT),
            'role' => 'DOCTOR',
            'phone' => '9876543211',
            'date_of_birth' => NULL,
            'gender' => 'FEMALE'
        ],
        [
            'name' => 'Admin User',
            'email' => 'testadmin@test.com',
            'password' => password_hash('AS@Saveetha123', PASSWORD_BCRYPT),
            'role' => 'ADMIN',
            'phone' => '9876543212',
            'date_of_birth' => NULL,
            'gender' => NULL
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, phone, date_of_birth, gender)
        VALUES (:name, :email, :password, :role, :phone, :date_of_birth, :gender)
    ");
    
    foreach ($users as $user) {
        $stmt->execute($user);
        echo "  ✓ {$user['role']}: {$user['email']}\n";
    }
    
    // Get user IDs
    $patientId = $pdo->lastInsertId() - 2;
    $doctorId = $pdo->lastInsertId() - 1;
    
    // Create patient entry
    $pdo->exec("
        INSERT INTO patients (user_id, medical_history, blood_group)
        VALUES ($patientId, 'Rheumatoid Arthritis diagnosed in 2020', 'O+')
    ");
    echo "  ✓ Patient profile created\n";
    
    // Create doctor entry
    $pdo->exec("
        INSERT INTO doctors (user_id, specialization, qualification, experience_years)
        VALUES ($doctorId, 'Rheumatology', 'MD, Rheumatology', 10)
    ");
    echo "  ✓ Doctor profile created\n";
    
    // Assign patient to doctor
    $pdo->exec("
        INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status, reason)
        VALUES ($patientId, $doctorId, CURDATE() + INTERVAL 7 DAY, '10:00:00', 'SCHEDULED', 'Regular checkup')
    ");
    echo "  ✓ Initial appointment created\n";
    
    echo "\n=== DATABASE SETUP COMPLETE ===\n\n";
    
    echo "Database: $dbname\n";
    echo "Tables created: 20\n";
    echo "Initial users: 3\n\n";
    
    echo "Test Accounts:\n";
    echo "  Patient: deepankumar@gmail.com / Welcome@456\n";
    echo "  Doctor: doctor@test.com / Patrol@987\n";
    echo "  Admin: testadmin@test.com / AS@Saveetha123\n\n";
    
    echo "✓ Ready to use!\n";
    
} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
