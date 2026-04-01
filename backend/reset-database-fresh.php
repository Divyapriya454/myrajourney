<?php
/**
 * Reset Database - Fresh Start
 * Drops and recreates the entire database
 */

require_once __DIR__ . '/src/config/config.php';

use Src\Config\Config;

try {
    // Connect to MySQL without selecting a database
    $dsn = sprintf('mysql:host=%s;charset=utf8mb4',
        Config::get('DB_HOST', '127.0.0.1')
    );
    
    $pdo = new PDO($dsn, Config::get('DB_USER', 'root'), Config::get('DB_PASS', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $dbName = Config::get('DB_NAME', 'myrajourney');
    
    echo "===========================================\n";
    echo "Database Reset - Fresh Start\n";
    echo "===========================================\n\n";
    
    echo "Dropping database '$dbName' if exists...\n";
    $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
    echo "✅ Database dropped\n\n";
    
    echo "Creating fresh database '$dbName'...\n";
    $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database created\n\n";
    
    echo "Selecting database...\n";
    $pdo->exec("USE `$dbName`");
    echo "✅ Database selected\n\n";
    
    echo "===========================================\n";
    echo "Now run: php setup-database-complete.php\n";
    echo "===========================================\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
