<?php

require_once 'src/bootstrap.php';

try {
    $db = Src\Config\DB::conn();
    
    echo "Creating exercise tracking tables..." . PHP_EOL;
    
    // 1. Create exercise_assignments table
    echo "Creating exercise_assignments table..." . PHP_EOL;
    $sql1 = "
    CREATE TABLE IF NOT EXISTS exercise_assignments (
        id VARCHAR(50) PRIMARY KEY,
        doctor_id INT UNSIGNED NOT NULL,
        patient_id INT UNSIGNED NOT NULL,
        exercise_ids JSON NOT NULL,
        assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_patient_active (patient_id, is_active),
        INDEX idx_doctor_patient (doctor_id, patient_id),
        INDEX idx_assigned_date (assigned_date),
        CONSTRAINT fk_exercise_assign_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_exercise_assign_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sql1);
    echo "✅ exercise_assignments table created" . PHP_EOL;
    
    // 2. Create exercise_sessions table
    echo "Creating exercise_sessions table..." . PHP_EOL;
    $sql2 = "
    CREATE TABLE IF NOT EXISTS exercise_sessions (
        id VARCHAR(50) PRIMARY KEY,
        patient_id INT UNSIGNED NOT NULL,
        exercise_id VARCHAR(50) NOT NULL,
        start_time TIMESTAMP NOT NULL,
        end_time TIMESTAMP NULL,
        session_duration INT UNSIGNED NULL,
        overall_accuracy FLOAT NULL,
        completion_rate FLOAT NULL,
        motion_data JSON,
        performance_metrics JSON,
        completed BOOLEAN NOT NULL DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_patient_exercise (patient_id, exercise_id),
        INDEX idx_session_date (patient_id, start_time),
        INDEX idx_completed (completed),
        CONSTRAINT fk_exercise_session_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_exercise_session_exercise FOREIGN KEY (exercise_id) REFERENCES ra_exercises(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sql2);
    echo "✅ exercise_sessions table created" . PHP_EOL;
    
    // 3. Create performance_reports table
    echo "Creating performance_reports table..." . PHP_EOL;
    $sql3 = "
    CREATE TABLE IF NOT EXISTS performance_reports (
        id VARCHAR(50) PRIMARY KEY,
        session_id VARCHAR(50) NOT NULL,
        patient_id INT UNSIGNED NOT NULL,
        exercise_id VARCHAR(50) NOT NULL,
        report_data JSON NOT NULL,
        generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_patient_reports (patient_id, generated_at),
        INDEX idx_exercise_reports (exercise_id, generated_at),
        CONSTRAINT fk_perf_report_session FOREIGN KEY (session_id) REFERENCES exercise_sessions(id) ON DELETE CASCADE,
        CONSTRAINT fk_perf_report_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_perf_report_exercise FOREIGN KEY (exercise_id) REFERENCES ra_exercises(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sql3);
    echo "✅ performance_reports table created" . PHP_EOL;
    
    // 4. Insert all 10 RA exercises
    echo "Inserting RA exercises..." . PHP_EOL;
    
    $exercises = [
        [
            'id' => 'ex_001',
            'name' => 'Wrist Flexion/Extension',
            'description' => 'Gentle wrist movement to improve flexibility and reduce stiffness in wrist joints',
            'category' => 'WRIST',
            'target_joints' => ['Wrist', 'Forearm'],
            'difficulty_level' => 1,
            'video_url' => 'https://www.youtube.com/watch?v=wrist_flex_ext',
            'animation_url' => 'animation_wrist_flex.gif',
            'instructions' => ['Sit comfortably with your arm supported', 'Slowly bend your wrist up and down', 'Hold for 2-3 seconds at each position', 'Repeat 10-15 times'],
            'ra_benefits' => ['Reduces wrist stiffness common in RA', 'Improves range of motion', 'Helps maintain joint function']
        ],
        [
            'id' => 'ex_002',
            'name' => 'Wrist Rotation (Clockwise/Counterclockwise)',
            'description' => 'Circular wrist movements to maintain joint mobility and reduce morning stiffness',
            'category' => 'WRIST',
            'target_joints' => ['Wrist', 'Radius', 'Ulna'],
            'difficulty_level' => 1,
            'video_url' => 'https://www.youtube.com/watch?v=wrist_rotation',
            'animation_url' => 'animation_wrist_rotation.gif',
            'instructions' => ['Extend your arm in front of you', 'Make slow circles with your wrist', 'Rotate 10 times clockwise', 'Rotate 10 times counterclockwise'],
            'ra_benefits' => ['Maintains wrist joint mobility', 'Reduces morning stiffness', 'Improves circulation in wrist area']
        ],
        [
            'id' => 'ex_003',
            'name' => 'Thumb Opposition Exercise',
            'description' => 'Thumb-to-finger touching exercise to maintain thumb mobility and grip strength',
            'category' => 'THUMB',
            'target_joints' => ['Thumb', 'CMC Joint', 'Fingers'],
            'difficulty_level' => 1,
            'video_url' => 'https://www.youtube.com/watch?v=thumb_opposition',
            'animation_url' => 'animation_thumb_opposition.gif',
            'instructions' => ['Touch your thumb to each fingertip', 'Start with index finger, move to pinky', 'Hold each touch for 2 seconds', 'Repeat sequence 5-10 times'],
            'ra_benefits' => ['Maintains thumb joint flexibility', 'Improves grip strength', 'Helps with daily activities like writing']
        ],
        [
            'id' => 'ex_004',
            'name' => 'Thumb Flexion/Extension',
            'description' => 'Thumb bending exercise to improve thumb joint range of motion',
            'category' => 'THUMB',
            'target_joints' => ['Thumb', 'MCP Joint', 'IP Joint'],
            'difficulty_level' => 1,
            'video_url' => 'https://www.youtube.com/watch?v=thumb_flex_ext',
            'animation_url' => 'animation_thumb_flex.gif',
            'instructions' => ['Keep your hand flat on a table', 'Slowly bend your thumb toward your palm', 'Straighten your thumb back up', 'Repeat 10-15 times'],
            'ra_benefits' => ['Improves thumb joint mobility', 'Reduces thumb stiffness', 'Helps maintain pinch strength']
        ],
        [
            'id' => 'ex_005',
            'name' => 'Finger Flexion (Making a Fist)',
            'description' => 'Gentle fist-making exercise to maintain finger joint flexibility',
            'category' => 'FINGER',
            'target_joints' => ['Fingers', 'MCP Joints', 'PIP Joints', 'DIP Joints'],
            'difficulty_level' => 1,
            'video_url' => 'https://www.youtube.com/watch?v=finger_flexion',
            'animation_url' => 'animation_finger_flex.gif',
            'instructions' => ['Start with fingers straight and spread', 'Slowly curl fingers into a loose fist', 'Don\'t squeeze tightly', 'Hold for 3 seconds, then open', 'Repeat 10 times'],
            'ra_benefits' => ['Maintains finger joint flexibility', 'Improves grip strength gradually', 'Reduces finger stiffness']
        ],
        [
            'id' => 'ex_006',
            'name' => 'Finger Extension/Spreading',
            'description' => 'Finger spreading exercise to improve finger extension and reduce joint contractures',
            'category' => 'FINGER',
            'target_joints' => ['Fingers', 'MCP Joints', 'Interosseous muscles'],
            'difficulty_level' => 1,
            'video_url' => 'https://www.youtube.com/watch?v=finger_extension',
            'animation_url' => 'animation_finger_spread.gif',
            'instructions' => ['Place your hand flat on a table', 'Spread your fingers as wide as comfortable', 'Hold for 5 seconds', 'Relax and repeat 10 times'],
            'ra_benefits' => ['Prevents finger contractures', 'Improves finger extension', 'Maintains hand span for gripping']
        ],
        [
            'id' => 'ex_007',
            'name' => 'Finger Pinch Strengthening',
            'description' => 'Gentle pinching exercise using therapy putty or soft objects to maintain pinch strength',
            'category' => 'FINGER',
            'target_joints' => ['Thumb', 'Index finger', 'Pinch muscles'],
            'difficulty_level' => 2,
            'video_url' => 'https://www.youtube.com/watch?v=finger_pinch',
            'animation_url' => 'animation_finger_pinch.gif',
            'instructions' => ['Use therapy putty or soft ball', 'Pinch between thumb and index finger', 'Hold for 3 seconds', 'Release slowly', 'Repeat 10-15 times'],
            'ra_benefits' => ['Maintains pinch strength for daily tasks', 'Improves fine motor control', 'Helps with buttoning and writing']
        ],
        [
            'id' => 'ex_008',
            'name' => 'Knee Flexion/Extension (Seated)',
            'description' => 'Seated knee straightening exercise to maintain knee joint mobility and quadriceps strength',
            'category' => 'KNEE',
            'target_joints' => ['Knee', 'Quadriceps', 'Hamstrings'],
            'difficulty_level' => 1,
            'video_url' => 'https://www.youtube.com/watch?v=knee_flex_seated',
            'animation_url' => 'animation_knee_seated.gif',
            'instructions' => ['Sit in a chair with back support', 'Slowly straighten one leg', 'Hold for 3-5 seconds', 'Lower leg slowly', 'Repeat 10 times each leg'],
            'ra_benefits' => ['Maintains knee joint mobility', 'Strengthens quadriceps muscles', 'Reduces knee stiffness']
        ],
        [
            'id' => 'ex_009',
            'name' => 'Hip Flexion (Seated/Standing)',
            'description' => 'Hip lifting exercise to maintain hip joint flexibility and hip flexor strength',
            'category' => 'HIP',
            'target_joints' => ['Hip', 'Hip flexors', 'Psoas'],
            'difficulty_level' => 2,
            'video_url' => 'https://www.youtube.com/watch?v=hip_flexion',
            'animation_url' => 'animation_hip_flexion.gif',
            'instructions' => ['Sit or stand with support if needed', 'Slowly lift one knee toward chest', 'Hold for 3 seconds', 'Lower slowly', 'Repeat 10 times each leg'],
            'ra_benefits' => ['Maintains hip joint mobility', 'Improves walking ability', 'Reduces hip stiffness']
        ],
        [
            'id' => 'ex_010',
            'name' => 'Hip Abduction (Side-lying/Standing)',
            'description' => 'Side leg lifting exercise to strengthen hip abductor muscles and improve stability',
            'category' => 'HIP',
            'target_joints' => ['Hip', 'Gluteus medius', 'Hip abductors'],
            'difficulty_level' => 2,
            'video_url' => 'https://www.youtube.com/watch?v=hip_abduction',
            'animation_url' => 'animation_hip_abduction.gif',
            'instructions' => ['Lie on your side or stand with support', 'Slowly lift top leg to the side', 'Keep leg straight', 'Hold for 3 seconds', 'Lower slowly, repeat 10 times'],
            'ra_benefits' => ['Strengthens hip stabilizer muscles', 'Improves balance and stability', 'Reduces hip pain during walking']
        ]
    ];
    
    $stmt = $db->prepare("
        INSERT IGNORE INTO ra_exercises 
        (id, name, description, category, target_joints, difficulty_level, video_url, animation_url, instructions, ra_benefits) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($exercises as $exercise) {
        $stmt->execute([
            $exercise['id'],
            $exercise['name'],
            $exercise['description'],
            $exercise['category'],
            json_encode($exercise['target_joints']),
            $exercise['difficulty_level'],
            $exercise['video_url'],
            $exercise['animation_url'],
            json_encode($exercise['instructions']),
            json_encode($exercise['ra_benefits'])
        ]);
    }
    
    // Verify exercises
    $stmt = $db->query("SELECT COUNT(*) FROM ra_exercises");
    $count = $stmt->fetchColumn();
    echo "✅ {$count} RA exercises inserted" . PHP_EOL;
    
    // Show exercise categories
    $stmt = $db->query("SELECT category, COUNT(*) as count FROM ra_exercises GROUP BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nExercise breakdown by category:" . PHP_EOL;
    foreach ($categories as $cat) {
        echo "  {$cat['category']}: {$cat['count']} exercises" . PHP_EOL;
    }
    
    echo "\n✅ Exercise tracking system setup complete!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
