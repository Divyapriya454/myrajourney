<?php
/**
 * Simple SQL Update Runner
 */

// Database configuration
$host = '127.0.0.1';
$dbname = 'myrajourney';
$username = 'root';
$password = '';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "Connected to database successfully.\n";
    
    // Read and execute SQL file
    $sqlFile = __DIR__ . '/scripts/update_database_new_features.sql';
    $sql = file_get_contents($sqlFile);
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "Database update completed successfully!\n";
    
    // Check what tables were created
    $stmt = $pdo->query("
        SELECT TABLE_NAME 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = 'myrajourney' 
        AND (TABLE_NAME LIKE 'crp_%' 
             OR TABLE_NAME LIKE 'exercise_%' 
             OR TABLE_NAME LIKE 'notification_%' 
             OR TABLE_NAME LIKE 'chatbot_%')
        ORDER BY TABLE_NAME
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "New tables created:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
