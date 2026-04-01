<?php
require_once __DIR__ . '/src/bootstrap.php';
use Src\Controllers\RehabController;

$_GET['patient_id'] = 1;
$_SERVER['auth'] = ['uid' => 10, 'role' => 'doctor']; // Simulate doctor view
$_SERVER['REQUEST_METHOD'] = 'GET';

$controller = new RehabController();

// Capture output
ob_start();
$controller->listAll(); // listAll calls listForPatient internally for doctors
$output = ob_get_clean();

echo $output;
?>
