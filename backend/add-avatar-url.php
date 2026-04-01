<?php
$pdo = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
try {
    $pdo->exec('ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500) NULL');
    echo "✓ avatar_url column added\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⚠ avatar_url column already exists\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}
