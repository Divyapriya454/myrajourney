<?php
require 'c:/Users/Admin/AndroidStudioProjects/myrajourney/myrajourney/backend/src/bootstrap.php';
use Src\Models\MedicationModel;
$meds = new MedicationModel();
$r = $meds->patientMedications(25, 1, 1, 1);
echo "API JSON OUTPUT FOR PATIENT 25 (Latest):\n";
echo json_encode($r['items'][0], JSON_PRETTY_PRINT);
