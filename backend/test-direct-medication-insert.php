<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Testing medication assignment...\n";
try {
    // Test insert
    $stmt = $db->prepare("
        INSERT INTO patient_medications 
        (patient_id, doctor_id, name_override, medication_name, dosage, instructions, duration, is_morning, is_afternoon, is_night, food_relation, frequency, active, created_at, updated_at) 
        VALUES 
        (1, 2, 'Test Med', 'Test Med', '10mg', 'Take with water', '7 days', 1, 0, 1, 'After food', '2x daily', 1, NOW(), NOW())
    ");
    $stmt->execute();
    echo "✓ Medication inserted successfully\n";
    echo "ID: " . $db->lastInsertId() . "\n";
    
    // Clean up
    $db->exec("DELETE FROM patient_medications WHERE name_override = 'Test Med'");
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
