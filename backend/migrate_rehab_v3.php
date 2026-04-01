<?php
require_once 'src/bootstrap.php';

try {
    $db = Src\Config\DB::conn();
    
    echo "Creating new rehab system tables..." . PHP_EOL;
    
    // 1. Create rehab_exercises (Master Table)
    $db->exec("DROP TABLE IF EXISTS patient_rehab_assignment"); // Drop child first
    $db->exec("DROP TABLE IF EXISTS rehab_exercises");
    
    $sql1 = "
    CREATE TABLE rehab_exercises (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rehab_name VARCHAR(255) NOT NULL,
        description TEXT,
        benefits TEXT,
        sets INT DEFAULT 3,
        reps INT DEFAULT 10,
        category VARCHAR(100),
        video_url VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sql1);
    echo "✅ rehab_exercises table created" . PHP_EOL;
    
    // 2. Create patient_rehab_assignment
    $sql2 = "
    CREATE TABLE patient_rehab_assignment (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        rehab_id INT NOT NULL,
        assigned_by_doctor_id INT NOT NULL,
        sets INT DEFAULT 3,
        reps INT DEFAULT 10,
        status ENUM('pending', 'completed') DEFAULT 'pending',
        assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_rehab_assignment_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_rehab_assignment_rehab FOREIGN KEY (rehab_id) REFERENCES rehab_exercises(id) ON DELETE CASCADE,
        CONSTRAINT fk_rehab_assignment_doctor FOREIGN KEY (assigned_by_doctor_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sql2);
    echo "✅ patient_rehab_assignment table created" . PHP_EOL;
    
    // 3. Seed Master Rehab Data
    echo "Seeding master rehab data..." . PHP_EOL;
    $exercises = [
        [
            'Wrist Flexion',
            'Bend your wrist forward and backward to improve wrist mobility and reduce stiffness.',
            'Improves wrist mobility;Reduces joint stiffness;Strengthens forearm muscles',
            3, 12, 'Wrist Exercises', 'file:///android_asset/exercise_videos/ex_001_wrist_flexion.mp4'
        ],
        [
            'Wrist Rotation',
            'Rotate your wrist in circular motions to maintain full range of motion and lubricate the joint.',
            'Maintains wrist range of motion;Lubricates wrist joint;Reduces morning stiffness',
            3, 10, 'Wrist Exercises', 'file:///android_asset/exercise_videos/ex_002_wrist_rotation.mp4'
        ],
        [
            'Thumb Opposition',
            'Touch your thumb to each fingertip one at a time to maintain thumb mobility and coordination.',
            'Maintains thumb flexibility;Improves grip strength;Helps with daily activities',
            3, 10, 'Hand Exercises', 'file:///android_asset/exercise_videos/ex_003_thumb_opposition.mp4'
        ],
        [
            'Thumb Flexion',
            'Bend and straighten your thumb to improve thumb joint mobility and strength.',
            'Strengthens thumb muscles;Improves fine motor control;Reduces thumb stiffness',
            3, 10, 'Hand Exercises', 'file:///android_asset/exercise_videos/ex_004_thumb_flexion.mp4'
        ],
        [
            'Finger Flexion',
            'Curl all fingers into a fist and then straighten them to maintain finger joint mobility.',
            'Maintains finger mobility;Prevents contractures;Improves overall hand function',
            3, 12, 'Finger Exercises', 'file:///android_asset/exercise_videos/ex_005_finger_flexion.mp4'
        ],
        [
            'Finger Extension',
            'Spread your fingers wide apart and then bring them together to improve finger extension.',
            'Prevents finger contractures;Improves finger extension;Maintains hand span for gripping',
            3, 10, 'Finger Exercises', 'file:///android_asset/exercise_videos/ex_006_finger_extension.mp4'
        ],
        [
            'Finger Pinch',
            'Pinch your thumb and index finger together in a controlled motion to strengthen pinch grip.',
            'Strengthens pinch grip;Improves dexterity;Helps with writing and holding objects',
            3, 12, 'Finger Exercises', 'file:///android_asset/exercise_videos/ex_007_finger_pinch.mp4'
        ],
        [
            'Knee Flexion',
            'Bend and straighten your knee while seated to maintain knee joint range of motion.',
            'Maintains knee mobility;Strengthens quadriceps and hamstrings;Reduces knee stiffness',
            3, 12, 'Knee Exercises', 'file:///android_asset/exercise_videos/ex_008_knee_flexion.mp4'
        ],
        [
            'Hip Flexion',
            'Lift your knee toward your chest while standing to improve hip flexor strength and mobility.',
            'Improves hip flexibility;Strengthens hip flexors;Reduces hip pain during walking',
            3, 10, 'Hip Exercises', 'file:///android_asset/exercise_videos/ex_009_hip_flexion.mp4'
        ],
        [
            'Hip Abduction',
            'Side leg lifting exercise to strengthen hip abductor muscles and improve stability.',
            'Strengthens hip stabilizers;Improves balance;Reduces hip pain during walking',
            3, 12, 'Hip Exercises', 'file:///android_asset/exercise_videos/ex_010_hip_abduction.mp4'
        ],
    ];
    
    $stmt = $db->prepare("INSERT INTO rehab_exercises (rehab_name, description, benefits, sets, reps, category, video_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($exercises as $ex) {
        $stmt->execute($ex);
    }
    echo "✅ Seeded " . count($exercises) . " exercises." . PHP_EOL;
    
    echo "\n🚀 Database Implementation Complete!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
