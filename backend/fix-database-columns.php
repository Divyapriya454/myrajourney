<?php
$pdo = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "===========================================\n";
echo "Fixing Database Columns\n";
echo "===========================================\n\n";

$fixes = [
    // Fix reports table
    "ALTER TABLE reports ADD COLUMN IF NOT EXISTS uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER created_at",
    
    // Fix appointments table
    "ALTER TABLE appointments MODIFY COLUMN start_time DATETIME",
    "ALTER TABLE appointments MODIFY COLUMN end_time DATETIME",
    
    // Fix notifications table
    "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS body TEXT AFTER message",
    "UPDATE notifications SET body = message WHERE body IS NULL",
    
    // Fix users table
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_at TIMESTAMP NULL",
];

foreach ($fixes as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ " . substr($sql, 0, 60) . "...\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠ Column already exists: " . substr($sql, 0, 40) . "...\n";
        } else {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n===========================================\n";
echo "Verifying Reports Table\n";
echo "===========================================\n";
$stmt = $pdo->query('DESCRIBE reports');
$columns = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $columns[] = $row['Field'];
}

$required = ['uploaded_at', 'created_at', 'updated_at'];
foreach ($required as $col) {
    if (in_array($col, $columns)) {
        echo "✓ $col exists\n";
    } else {
        echo "✗ $col missing\n";
    }
}

echo "\n✓ Database columns fixed!\n";
