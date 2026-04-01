<?php
$db = new PDO('mysql:host=localhost;dbname=myrajourney', 'root', '');
$db->exec("DELETE FROM patient_medications WHERE medication_name = 'Test Medication'");
$db->exec("DELETE FROM rehab_plans WHERE title = 'Test Rehab Plan'");
echo "✓ Cleaned up test data\n";
