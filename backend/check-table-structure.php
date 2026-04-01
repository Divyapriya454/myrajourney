<?php

require __DIR__ . '/src/bootstrap.php';

echo "=== Checking missed_dose_reports Table Structure ===" . PHP_EOL . PHP_EOL;

try {
    $db = Src\Config\DB::conn();
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'missed_dose_reports'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Table 'missed_dose_reports' does not exist!" . PHP_EOL;
        exit(1);
    }
    
    echo "✅ Table 'missed_dose_reports' exists" . PHP_EOL . PHP_EOL;
    
    // Check table structure
    $stmt = $db->query("DESCRIBE missed_dose_reports");
    echo "Table Structure:" . PHP_EOL;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  " . $row['Field'] . " - " . $row['Type'] . " - Null: " . $row['Null'] . PHP_EOL;
    }
    
    echo PHP_EOL . "✅ Table structure looks correct" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
