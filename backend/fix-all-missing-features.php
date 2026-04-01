<?php
$pdo = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "===========================================\n";
echo "Fixing All Missing Features\n";
echo "===========================================\n\n";

$fixes = [
    // Patient Medications table
    "ALTER TABLE patient_medications ADD COLUMN IF NOT EXISTS name_override VARCHAR(255)",
    "ALTER TABLE patient_medications ADD COLUMN IF NOT EXISTS dosage_override VARCHAR(100)",
    "ALTER TABLE patient_medications ADD COLUMN IF NOT EXISTS frequency_override VARCHAR(100)",
    "ALTER TABLE patient_medications ADD COLUMN IF NOT EXISTS instructions_override TEXT",
    
    // Appointments table
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS title VARCHAR(255)",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS location VARCHAR(255)",
    "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS type VARCHAR(50)",
    
    // Rehab plans table
    "ALTER TABLE rehab_plans ADD COLUMN IF NOT EXISTS title VARCHAR(255)",
    "ALTER TABLE rehab_plans ADD COLUMN IF NOT EXISTS description TEXT",
    "ALTER TABLE rehab_plans ADD COLUMN IF NOT EXISTS status ENUM('ACTIVE', 'COMPLETED', 'CANCELLED') DEFAULT 'ACTIVE'",
    
    // Rehab exercises table
    "ALTER TABLE rehab_exercises ADD COLUMN IF NOT EXISTS title VARCHAR(255)",
    "ALTER TABLE rehab_exercises ADD COLUMN IF NOT EXISTS description TEXT",
    "ALTER TABLE rehab_exercises ADD COLUMN IF NOT EXISTS sets INT DEFAULT 1",
    "ALTER TABLE rehab_exercises ADD COLUMN IF NOT EXISTS reps INT DEFAULT 10",
    "ALTER TABLE rehab_exercises ADD COLUMN IF NOT EXISTS duration INT",
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
echo "Verifying Tables\n";
echo "===========================================\n";

// Verify patient_medications
$stmt = $pdo->query('DESCRIBE patient_medications');
$columns = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $columns[] = $row['Field'];
}
echo "Patient Medications: " . (in_array('name_override', $columns) ? '✓' : '✗') . " name_override\n";

// Verify appointments
$stmt = $pdo->query('DESCRIBE appointments');
$columns = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $columns[] = $row['Field'];
}
echo "Appointments: " . (in_array('title', $columns) ? '✓' : '✗') . " title\n";

// Verify rehab_plans
$stmt = $pdo->query('DESCRIBE rehab_plans');
$columns = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $columns[] = $row['Field'];
}
echo "Rehab Plans: " . (in_array('title', $columns) ? '✓' : '✗') . " title\n";

echo "\n✓ All features fixed!\n";
