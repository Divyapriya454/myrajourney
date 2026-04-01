-- ============================================
-- Rehab Exercise Tracking System Migration
-- Adds tables for RA-specific exercise tracking with motion analysis
-- ============================================

USE myrajourney;

-- 1. RA Exercises Library Table
CREATE TABLE IF NOT EXISTS ra_exercises (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category ENUM('WRIST', 'THUMB', 'FINGER', 'KNEE', 'HIP') NOT NULL,
    target_joints JSON,
    difficulty_level TINYINT UNSIGNED NOT NULL DEFAULT 1,
    video_url VARCHAR(255),
    animation_url VARCHAR(255),
    instructions JSON,
    ra_benefits JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_difficulty (difficulty_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Exercise Assignments Table (Doctor assigns exercises to patients)
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

-- 3. Exercise Sessions Table (Patient exercise sessions with motion data)
CREATE TABLE IF NOT EXISTS exercise_sessions (
    id VARCHAR(50) PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    exercise_id VARCHAR(50) NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    session_duration INT UNSIGNED NULL, -- Duration in seconds
    overall_accuracy FLOAT NULL, -- 0.0 to 1.0
    completion_rate FLOAT NULL, -- 0.0 to 1.0
    motion_data JSON, -- Stores motion tracking frames
    performance_metrics JSON, -- Additional metrics
    completed BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_patient_exercise (patient_id, exercise_id),
    INDEX idx_session_date (patient_id, start_time),
    INDEX idx_completed (completed),
    CONSTRAINT fk_exercise_session_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_exercise_session_exercise FOREIGN KEY (exercise_id) REFERENCES ra_exercises(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Performance Reports Table (Generated reports for each session)
CREATE TABLE IF NOT EXISTS performance_reports (
    id VARCHAR(50) PRIMARY KEY,
    session_id VARCHAR(50) NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    exercise_id VARCHAR(50) NOT NULL,
    report_data JSON NOT NULL, -- Contains all report metrics and recommendations
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_patient_reports (patient_id, generated_at),
    INDEX idx_exercise_reports (exercise_id, generated_at),
    CONSTRAINT fk_perf_report_session FOREIGN KEY (session_id) REFERENCES exercise_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_perf_report_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_perf_report_exercise FOREIGN KEY (exercise_id) REFERENCES ra_exercises(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT RA EXERCISES
-- ============================================

INSERT INTO ra_exercises (id, name, description, category, target_joints, difficulty_level, video_url, animation_url, instructions, ra_benefits) VALUES
('ex_001', 'Wrist Flexion/Extension', 'Gentle wrist movement to improve flexibility and reduce stiffness in wrist joints', 'WRIST', 
 JSON_ARRAY('Wrist', 'Forearm'), 1, 'https://youtu.be/MD-ddObx9QA?si=CFZLpAB10lpl9koi', 'animation_wrist_flex.gif',
 JSON_ARRAY('Sit comfortably with your arm supported', 'Slowly bend your wrist up and down', 'Hold for 2-3 seconds at each position', 'Repeat 10-15 times'),
 JSON_ARRAY('Reduces wrist stiffness common in RA', 'Improves range of motion', 'Helps maintain joint function')),

('ex_002', 'Wrist Rotation (Clockwise/Counterclockwise)', 'Circular wrist movements to maintain joint mobility and reduce morning stiffness', 'WRIST',
 JSON_ARRAY('Wrist', 'Radius', 'Ulna'), 1, 'https://youtu.be/07xRQWfXJgI?si=2nv2BIeiMhBchKxs', 'animation_wrist_rotation.gif',
 JSON_ARRAY('Extend your arm in front of you', 'Make slow circles with your wrist', 'Rotate 10 times clockwise', 'Rotate 10 times counterclockwise'),
 JSON_ARRAY('Maintains wrist joint mobility', 'Reduces morning stiffness', 'Improves circulation in wrist area')),

('ex_003', 'Thumb Opposition Exercise', 'Thumb-to-finger touching exercise to maintain thumb mobility and grip strength', 'THUMB',
 JSON_ARRAY('Thumb', 'CMC Joint', 'Fingers'), 1, 'https://youtu.be/H5qap5Ktrlk?si=PUKL7XS__B9YQMY1', 'animation_thumb_opposition.gif',
 JSON_ARRAY('Touch your thumb to each fingertip', 'Start with index finger, move to pinky', 'Hold each touch for 2 seconds', 'Repeat sequence 5-10 times'),
 JSON_ARRAY('Maintains thumb joint flexibility', 'Improves grip strength', 'Helps with daily activities like writing')),

('ex_004', 'Thumb Flexion/Extension', 'Thumb bending exercise to improve thumb joint range of motion', 'THUMB',
 JSON_ARRAY('Thumb', 'MCP Joint', 'IP Joint'), 1, 'https://youtu.be/r85WPBt2WRw?si=DjfNBSBPkRBQbwEs', 'animation_thumb_flex.gif',
 JSON_ARRAY('Keep your hand flat on a table', 'Slowly bend your thumb toward your palm', 'Straighten your thumb back up', 'Repeat 10-15 times'),
 JSON_ARRAY('Improves thumb joint mobility', 'Reduces thumb stiffness', 'Helps maintain pinch strength')),

('ex_005', 'Finger Flexion (Making a Fist)', 'Gentle fist-making exercise to maintain finger joint flexibility', 'FINGER',
 JSON_ARRAY('Fingers', 'MCP Joints', 'PIP Joints', 'DIP Joints'), 1, 'https://youtu.be/1dJq7KKiHqM?si=qwK-T79-Pom1DE20', 'animation_finger_flex.gif',
 JSON_ARRAY('Start with fingers straight and spread', 'Slowly curl fingers into a loose fist', 'Don\'t squeeze tightly', 'Hold for 3 seconds, then open', 'Repeat 10 times'),
 JSON_ARRAY('Maintains finger joint flexibility', 'Improves grip strength gradually', 'Reduces finger stiffness')),

('ex_006', 'Finger Extension/Spreading', 'Finger spreading exercise to improve finger extension and reduce joint contractures', 'FINGER',
 JSON_ARRAY('Fingers', 'MCP Joints', 'Interosseous muscles'), 1, 'https://youtu.be/EiRC80FJbHU?si=finger_spreading_exercise', 'animation_finger_spread.gif',
 JSON_ARRAY('Place your hand flat on a table', 'Spread your fingers as wide as comfortable', 'Hold for 5 seconds', 'Relax and repeat 10 times'),
 JSON_ARRAY('Prevents finger contractures', 'Improves finger extension', 'Maintains hand span for gripping')),

('ex_007', 'Finger Pinch Strengthening', 'Gentle pinching exercise using therapy putty or soft objects to maintain pinch strength', 'FINGER',
 JSON_ARRAY('Thumb', 'Index finger', 'Pinch muscles'), 2, 'https://youtu.be/Kn-9JHkrlzk?si=pinch_grip_exercises', 'animation_finger_pinch.gif',
 JSON_ARRAY('Use therapy putty or soft ball', 'Pinch between thumb and index finger', 'Hold for 3 seconds', 'Release slowly', 'Repeat 10-15 times'),
 JSON_ARRAY('Maintains pinch strength for daily tasks', 'Improves fine motor control', 'Helps with buttoning and writing')),

('ex_008', 'Knee Flexion/Extension (Seated)', 'Seated knee straightening exercise to maintain knee joint mobility and quadriceps strength', 'KNEE',
 JSON_ARRAY('Knee', 'Quadriceps', 'Hamstrings'), 1, 'https://youtu.be/gsqKoEcbXkI?si=seated_knee_extensions', 'animation_knee_seated.gif',
 JSON_ARRAY('Sit in a chair with back support', 'Slowly straighten one leg', 'Hold for 3-5 seconds', 'Lower leg slowly', 'Repeat 10 times each leg'),
 JSON_ARRAY('Maintains knee joint mobility', 'Strengthens quadriceps muscles', 'Reduces knee stiffness')),

('ex_009', 'Hip Flexion (Seated/Standing)', 'Hip lifting exercise to maintain hip joint flexibility and hip flexor strength', 'HIP',
 JSON_ARRAY('Hip', 'Hip flexors', 'Psoas'), 2, 'https://youtu.be/YQmpO9VT2X4?si=hip_flexion_exercises', 'animation_hip_flexion.gif',
 JSON_ARRAY('Sit or stand with support if needed', 'Slowly lift one knee toward chest', 'Hold for 3 seconds', 'Lower slowly', 'Repeat 10 times each leg'),
 JSON_ARRAY('Maintains hip joint mobility', 'Improves walking ability', 'Reduces hip stiffness')),

('ex_010', 'Hip Abduction (Side-lying/Standing)', 'Side leg lifting exercise to strengthen hip abductor muscles and improve stability', 'HIP',
 JSON_ARRAY('Hip', 'Gluteus medius', 'Hip abductors'), 2, 'https://youtu.be/6JBaWiZGrQs?si=hip_abduction_exercises', 'animation_hip_abduction.gif',
 JSON_ARRAY('Lie on your side or stand with support', 'Slowly lift top leg to the side', 'Keep leg straight', 'Hold for 3 seconds', 'Lower slowly, repeat 10 times'),
 JSON_ARRAY('Strengthens hip stabilizer muscles', 'Improves balance and stability', 'Reduces hip pain during walking'));

-- ============================================
-- VERIFICATION
-- ============================================

-- Show new tables created
SELECT TABLE_NAME, TABLE_COMMENT 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'myrajourney' 
AND TABLE_NAME IN ('ra_exercises', 'exercise_assignments', 'exercise_sessions', 'performance_reports');

-- Count exercises inserted
SELECT COUNT(*) as total_exercises FROM ra_exercises;

-- Show exercise categories
SELECT category, COUNT(*) as count FROM ra_exercises GROUP BY category;

COMMIT;