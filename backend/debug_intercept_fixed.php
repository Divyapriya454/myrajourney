<?php
require_once __DIR__ . '/src/bootstrap.php';

// Manually require if autoloader fails
require_once __DIR__ . '/src/utils/Response.php';
require_once __DIR__ . '/src/models/RehabModel.php';
require_once __DIR__ . '/src/controllers/RehabController.php';

use Src\Controllers\RehabController;

$_GET['patient_id'] = 1;
$_SERVER['auth'] = ['uid' => 10, 'role' => 'doctor'];
$_SERVER['REQUEST_METHOD'] = 'GET';

$controller = new RehabController();

ob_start();
$controller->listAll();
$output = ob_get_clean();

echo $output;
?>
