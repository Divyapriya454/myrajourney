<?php
require 'c:/Users/Admin/AndroidStudioProjects/myrajourney/myrajourney/backend/src/bootstrap.php';
$db = Src\Config\DB::conn();
echo "--- DB v1 (id, instructions, food_relation) ---\n";
$stmt = $db->query('SELECT id, instructions, food_relation FROM patient_medications ORDER BY id DESC LIMIT 1');
$db_row = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($db_row);

use Src\Models\MedicationModel;
$model = new MedicationModel();
$api_r = $model->patientMedications(25, null, 1, 1);
$api_item = $api_r['items'][0];
echo "\n--- API JSON OUTPUT (Latest) ---\n";
echo json_encode($api_item, JSON_PRETTY_PRINT);
