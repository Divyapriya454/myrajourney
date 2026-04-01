<?php
// Direct test of medication assignment
require '../src/bootstrap.php';

use Src\Models\MedicationModel;

$model = new MedicationModel();

// Test data
$testData = [
    'patient_id' => 25,
    'doctor_id' => 26,
    'name_override' => 'Test Medication',
    'dosage' => '500mg',
    'frequency_per_day' => '3',
    'instructions' => 'TEST INSTRUCTIONS FROM SCRIPT',
    'duration' => '7 days',
    'is_morning' => 1,
    'is_afternoon' => 0,
    'is_night' => 1,
    'food_relation' => 'After Food'
];

echo "Testing medication assignment...\n";
echo "Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

try {
    $id = $model->assign($testData);
    echo "SUCCESS! Medication assigned with ID: $id\n";
    
    // Verify it was saved
    $db = Src\Config\DB::conn();
    $stmt = $db->prepare('SELECT * FROM patient_medications WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nSaved data:\n";
    echo "instructions: [" . $row['instructions'] . "]\n";
    echo "food_relation: [" . $row['food_relation'] . "]\n";
    echo "is_morning: [" . $row['is_morning'] . "]\n";
    echo "is_night: [" . $row['is_night'] . "]\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
