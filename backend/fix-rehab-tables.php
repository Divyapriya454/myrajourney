<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Fixing rehab tables...\n";
echo str_repeat('=', 60) . "\n";

try {
    // Add rehab_plan_id column if it doesn't exist
    $db->exec("ALTER TABLE rehab_exercises ADD COLUMN rehab_plan_id INT(11) NULL AFTER id");
    echo "✓ Added rehab_plan_id column\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⊙ rehab_plan_id already exists\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

// Copy data from plan_id to rehab_plan_id
try {
    $db->exec("UPDATE rehab_exercises SET rehab_plan_id = plan_id WHERE rehab_plan_id IS NULL");
    echo "✓ Copied plan_id to rehab_plan_id\n";
} catch (PDOException $e) {
    echo "✗ Error copying data: " . $e->getMessage() . "\n";
}

// Add name column if it doesn't exist (alias for exercise_name)
try {
    $db->exec("ALTER TABLE rehab_exercises ADD COLUMN name VARCHAR(255) NULL AFTER rehab_plan_id");
    echo "✓ Added name column\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⊙ name already exists\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

// Copy exercise_name to name
try {
    $db->exec("UPDATE rehab_exercises SET name = exercise_name WHERE name IS NULL");
    echo "✓ Copied exercise_name to name\n";
} catch (PDOException $e) {
    echo "✗ Error copying data: " . $e->getMessage() . "\n";
}

// Add frequency_per_week column
try {
    $db->exec("ALTER TABLE rehab_exercises ADD COLUMN frequency_per_week VARCHAR(100) NULL");
    echo "✓ Added frequency_per_week column\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⊙ frequency_per_week already exists\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Rehab tables fixed!\n";
