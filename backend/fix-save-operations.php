<?php
require_once __DIR__ . '/src/bootstrap.php';

$db = Src\Config\DB::conn();

echo "Fixing tables for save operations...\n\n";

function columnExists($db, $table, $column) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->fetch() !== false;
}

// Fix symptoms table - make symptom_type nullable or add default
echo "Symptoms table:\n";
try {
    // Check if symptom_type exists and modify it to be nullable
    if (columnExists($db, 'symptoms', 'symptom_type')) {
        $db->exec("ALTER TABLE symptoms MODIFY COLUMN symptom_type VARCHAR(100) NULL DEFAULT NULL");
        echo "✓ Modified symptoms.symptom_type to be nullable\n";
    }
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n";
}

// Fix appointments table - add 'type' column (alias for appointment_type)
echo "\nAppointments table:\n";
try {
    if (!columnExists($db, 'appointments', 'type')) {
        $db->exec("ALTER TABLE appointments ADD COLUMN type VARCHAR(50) NULL DEFAULT NULL");
        echo "✓ Added appointments.type\n";
    }
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n";
}

// Add other potentially missing appointment columns
$appointmentColumns = [
    'patient_id' => 'INT NULL DEFAULT NULL',
    'doctor_id' => 'INT NULL DEFAULT NULL',
    'appointment_date' => 'DATE NULL DEFAULT NULL',
    'appointment_time' => 'TIME NULL DEFAULT NULL',
];

foreach ($appointmentColumns as $column => $definition) {
    if (!columnExists($db, 'appointments', $column)) {
        try {
            $db->exec("ALTER TABLE appointments ADD COLUMN $column $definition");
            echo "✓ Added appointments.$column\n";
        } catch (Exception $e) {
            echo "✗ Failed to add appointments.$column: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✓ Tables fixed for save operations!\n";
