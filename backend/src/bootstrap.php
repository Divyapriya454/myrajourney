<?php
declare(strict_types=1);

// Suppress warnings and notices to prevent them from breaking JSON responses
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

// Start output buffering to catch any accidental output
ob_start();

spl_autoload_register(function ($class) {
	$prefix = 'Src\\';
	$base_dir = __DIR__ . '/';
	$len = strlen($prefix);
	if (strncmp($prefix, $class, $len) !== 0) {
		return;
	}
	$relative_class = substr($class, $len);
	$file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
	if (file_exists($file)) {
		require $file;
	}
});

// Load env
Src\Utils\Env::load(__DIR__ . '/../.env');




















