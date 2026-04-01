<?php
$pdo = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "===========================================\n";
echo "Fixing Patients Table Columns\n";
echo "===========================================\n\n";

// Check current structure
$stmt = $pdo->query('DESCRIBE patients');
$columns = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $columns[] = $row['Field'];
}

echo "Current columns: " . implode(', ', $columns) . "\n\n";

$fixes = [
    "ALTER TABLE patients ADD COLUMN IF NOT EXISTS age INT",
    "ALTER TABLE patients ADD COLUMN IF NOT EXISTS gender ENUM('MALE', 'FEMALE', 'OTHER') NULL",
    "ALTER TABLE patients ADD COLUMN IF NOT EXISTS medical_id VARCHAR(50)",
    "ALTER TABLE patients ADD COLUMN IF NOT EXISTS address TEXT",
    "ALTER TABLE patients ADD COLUMN IF NOT EXISTS assigned_doctor_id INT",
];

foreach ($fixes as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ " . substr($sql, 0, 70) . "...\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠ Column already exists\n";
        } else {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n===========================================\n";
echo "Verifying Patients Table\n";
echo "===========================================\n";

$stmt = $pdo->query('DESCRIBE patients');
$columns = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $columns[] = $row['Field'];
}

$required = ['age', 'gender', 'medical_id', 'address', 'assigned_doctor_id'];
foreach ($required as $col) {
    echo (in_array($col, $columns) ? '✓' : '✗') . " $col\n";
}

echo "\n✓ Patients table fixed!\n";
