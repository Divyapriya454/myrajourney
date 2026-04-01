<?php

require_once 'src/bootstrap.php';

try {
    $db = Src\Config\DB::conn();
    
    echo "Testing database connection..." . PHP_EOL;
    
    // Test connection
    $stmt = $db->query("SELECT DATABASE() as dbname");
    $result = $stmt->fetch();
    echo "Connected to database: " . $result['dbname'] . PHP_EOL;
    
    // Create ra_exercises table first
    echo "Creating ra_exercises table..." . PHP_EOL;
    $sql = "
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
    ";
    
    $db->exec($sql);
    echo "✅ ra_exercises table created" . PHP_EOL;
    
    // Insert sample exercise
    echo "Inserting sample exercise..." . PHP_EOL;
    $insertSql = "
    INSERT IGNORE INTO ra_exercises (id, name, description, category, target_joints, difficulty_level, video_url, animation_url, instructions, ra_benefits) VALUES
    ('ex_001', 'Wrist Flexion/Extension', 'Gentle wrist movement to improve flexibility and reduce stiffness in wrist joints', 'WRIST', 
     JSON_ARRAY('Wrist', 'Forearm'), 1, 'https://www.youtube.com/watch?v=wrist_flex_ext', 'animation_wrist_flex.gif',
     JSON_ARRAY('Sit comfortably with your arm supported', 'Slowly bend your wrist up and down', 'Hold for 2-3 seconds at each position', 'Repeat 10-15 times'),
     JSON_ARRAY('Reduces wrist stiffness common in RA', 'Improves range of motion', 'Helps maintain joint function'))
    ";
    
    $db->exec($insertSql);
    echo "✅ Sample exercise inserted" . PHP_EOL;
    
    // Verify
    $stmt = $db->query("SELECT COUNT(*) FROM ra_exercises");
    $count = $stmt->fetchColumn();
    echo "✅ {$count} exercises in database" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
