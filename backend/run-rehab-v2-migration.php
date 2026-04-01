<?php
require_once __DIR__ . '/src/bootstrap.php';
use Src\Config\DB;

try {
    $db = DB::conn();
    echo "Creating rehab_assignments_v2 table..." . PHP_EOL;
    
    // First, let's see exactly how users.id is defined
    $desc = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    $idType = 'INT(11) UNSIGNED';
    foreach($desc as $col) {
        if ($col['Field'] === 'id') {
            $idType = strtoupper($col['Type']);
            break;
        }
    }
    echo "users.id type is: $idType" . PHP_EOL;
    
    $sql = "
    CREATE TABLE IF NOT EXISTS rehab_assignments_v2 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT " . (strpos($idType, 'UNSIGNED') !== false ? 'UNSIGNED' : '') . " NOT NULL,
        doctor_id INT " . (strpos($idType, 'UNSIGNED') !== false ? 'UNSIGNED' : '') . " NOT NULL,
        exercise_name VARCHAR(255) NOT NULL,
        description TEXT,
        sets INT DEFAULT 3,
        reps INT DEFAULT 10,
        video_url VARCHAR(500),
        status ENUM('PENDING', 'COMPLETED') DEFAULT 'PENDING',
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        CONSTRAINT fk_rehab_v2_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_rehab_v2_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->exec($sql);
    echo "✅ Table rehab_assignments_v2 created successfully" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
