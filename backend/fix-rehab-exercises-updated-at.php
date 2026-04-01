<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Adding updated_at column to rehab_exercises...\n";

try {
    $db->exec("ALTER TABLE rehab_exercises ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    echo "✓ Added updated_at column\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⊙ updated_at already exists\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}
