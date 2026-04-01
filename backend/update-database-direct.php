<?php
/**
 * MyRA Journey - Direct Database Update Script
 * 
 * This script directly updates the database with all new features support
 * without requiring complex config files.
 */

echo "🚀 MyRA Journey - Database Update\n";
echo "=================================\n\n";

// Database configuration from .env
$config = [
    'host' => '127.0.0.1',
    'database' => 'myrajourney',
    'username' => 'root',
    'password' => ''
];

try {
    echo "🔌 Connecting to database...\n";
    
    $dsn = "mysql:host={$config['host']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Connected to MySQL server\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$config['database']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE {$config['database']}");
    
    echo "✅ Using database: {$config['database']}\n\n";
    
    echo "⚡ Applying database updates...\n";
    
    // 1. Add profile picture support to users table
    echo "  📸 Adding profile picture support...\n";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL COMMENT 'Profile picture filename' AFTER name");
        echo "    ✅ Added profile_picture column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "    ✅ profile_picture column already exists\n";
        } else {
            echo "    ⚠️  Warning: " . $e->getMessage() . "\n";
        }
    }
    
    // 2. Add user management fields
    echo "  👥 Adding user management fields...\n";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS specialization VARCHAR(120) NULL COMMENT 'Doctor specialization' AFTER name");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'User active status' AFTER status");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS age INT NULL COMMENT 'User age' AFTER name");
     
