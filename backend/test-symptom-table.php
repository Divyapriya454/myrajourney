<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=myrajourney;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Database Connection Test ===" . PHP_EOL;
    echo "✓ Connected to database" . PHP_EOL . PHP_EOL;
    
    // Check if symptom_logs table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'symptom_logs'");
    if ($stmt->rowCount() > 0) {
        echo "✓ symptom_logs table exists" . PHP_EOL . PHP_EOL;
        
        // Show table structure
        echo "=== symptom_logs Table Structure ===" . PHP_EOL;
        $stmt = $pdo->query("DESCRIBE symptom_logs");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("  %-20s %-20s %s", $row['Field'], $row['Type'], $row['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . PHP_EOL;
        }
        
        // Count rows
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM symptom_logs");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo PHP_EOL . "Total rows: " . $count . PHP_EOL . PHP_EOL;
        
        // Show sample data if exists
        if ($count > 0) {
            echo "=== Sample Data ===" . PHP_EOL;
            $stmt = $pdo->query("SELECT * FROM symptom_logs ORDER BY created_at DESC LIMIT 3");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                print_r($row);
            }
        }
    } else {
        echo "✗ symptom_logs table does NOT exist" . PHP_EOL;
        echo "Run migration: backend/scripts/migrations/010_symptoms_metrics.sql" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . PHP_EOL;
}
