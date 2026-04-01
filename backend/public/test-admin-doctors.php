<?php
// Simple test to check if AdminController works
require __DIR__ . '/../src/bootstrap.php';

use Src\Controllers\AdminController;
use Src\Middlewares\Auth;

// Simulate authentication
$_SERVER['auth'] = ['uid' => 3, 'role' => 'ADMIN'];

// Test the listDoctors method
$controller = new AdminController();
$controller->listDoctors();
