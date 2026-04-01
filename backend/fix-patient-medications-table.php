<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Adding missing columns to patient_medications table...\n";
echo str_repeat('=', 60) . "\n";

$columns = [
    "ADD COLUMN doctor_id INT(11) NULL AFTER prescribed_by",
    "ADD COLUMN medication_id INT(11) NULL AFTER patient_id",
    "ADD COLUMN duration VARCHAR(100) NULL AFTER instructions",
    "ADD COLUMN is_morning TINYINT(1) DEFAULT 0 AFTER duration",
    "ADD COLUMN is_afternoon TINYINT(1) DEFAULT 0 AFTER is_morning",
    "ADD COLUMN is_night TINYINT(1) DEFAULT 0 AFTER is_afternoon",
    "ADD COLUMN food_relation VARCHAR(100) NULL AFTER is_night",
    "ADD COLUMN frequency_per_day INT(11) NULL AFTER frequency",
    "ADD COLUMN active TINYINT(1) DEFAULT 1 AFTER is_active"
];

foreach ($columns as $col) {
    try {
        $db->exec("ALTER TABLE patient_medications $col");
        echo "✓ Added: $col\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⊙ Already exists: $col\n";
        } else {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✅ patient_medications table updated!\n";
