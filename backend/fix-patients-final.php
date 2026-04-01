<?php
require_once __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();

echo "Fixing patients table final columns...\n\n";

function columnExists($db, $table, $column) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->fetch() !== false;
}

// Add medical_id (alias for medical_record_number)
if (!columnExists($db, 'patients', 'medical_id')) {
    try {
        $db->exec("ALTER TABLE patients ADD COLUMN medical_id VARCHAR(100) NULL DEFAULT NULL");
        echo "✓ Added patients.medical_id\n";
        
        // Copy data from medical_record_number if it exists
        $db->exec("UPDATE patients SET medical_id = medical_record_number WHERE medical_record_number IS NOT NULL");
        echo "✓ Copied data from medical_record_number to medical_id\n";
    } catch (Exception $e) {
        echo "✗ Failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "  patients.medical_id already exists\n";
}

// Add gender if missing
if (!columnExists($db, 'patients', 'gender')) {
    try {
        $db->exec("ALTER TABLE patients ADD COLUMN gender ENUM('MALE', 'FEMALE', 'OTHER', 'PREFER_NOT_TO_SAY') NULL DEFAULT NULL");
        echo "✓ Added patients.gender\n";
    } catch (Exception $e) {
        echo "✗ Failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "  patients.gender already exists\n";
}

// Add address if missing
if (!columnExists($db, 'patients', 'address')) {
    try {
        $db->exec("ALTER TABLE patients ADD COLUMN address TEXT NULL DEFAULT NULL");
        echo "✓ Added patients.address\n";
    } catch (Exception $e) {
        echo "✗ Failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "  patients.address already exists\n";
}

echo "\n✓ Patients table fixed!\n";
