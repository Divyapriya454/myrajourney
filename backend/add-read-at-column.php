<?php
$pdo = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
try {
    $pdo->exec('ALTER TABLE notifications ADD COLUMN read_at TIMESTAMP NULL');
    echo "✓ read_at column added to notifications\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⚠ read_at column already exists\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}
