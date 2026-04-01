<?php

require_once 'src/bootstrap.php';

try {
    $db = Src\Config\DB::conn();
    
    echo "Running exercise tracking migration..." . PHP_EOL;
    
    $sql = file_get_contents(__DIR__ . '/scripts/migrations/add_rehab_exercise_tracking.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(--|\/\*|\s*$)/', $statement)) {
            $db->exec($statement);
        }
    }
    
    echo "✅ Exercise tracking tables created successfully!" . PHP_EOL;
    
    // Verify tables were created
    $stmt = $db->query("SHOW TABLES LIKE 'ra_exercises'");
    if ($stmt->rowCount() > 0) {
        echo "✅ ra_exercises table created" . PHP_EOL;
    }
    
    $stmt = $db->query("SHOW TABLES LIKE 'exercise_assignments'");
    if ($stmt->rowCount() > 0) {
        echo "✅ exercise_assignments table created" . PHP_EOL;
    }
    
    $stmt = $db->query("SHOW TABLES LIKE 'exercise_sessions'");
    if ($stmt->rowCount() > 0) {
        echo "✅ exercise_sessions table created" . PHP_EOL;
    }
    
    $stmt = $db->query("SHOW TABLES LIKE 'performance_reports'");
    if ($stmt->rowCount() > 0) {
        echo "✅ performance_reports table created" . PHP_EOL;
    }
    
    // Check if exercises were inserted
    $stmt = $db->query("SELECT COUNT(*) FROM ra_exercises");
    $count = $stmt->fetchColumn();
    echo "✅ {$count} RA exercises inserted" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
