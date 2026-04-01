<?php
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

echo "REQUEST_METHOD: " . $method . "\n";
echo "REQUEST_URI (raw): " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "Parsed URI (before): " . $uri . "\n";

// Strip the base path
$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
$scriptDir = str_replace('\\', '/', rtrim(__DIR__, '/\\'));
$basePath = str_replace($docRoot, '', $scriptDir);

echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET') . "\n";
echo "SCRIPT_DIR: " . __DIR__ . "\n";
echo "BASE_PATH: " . $basePath . "\n";

if ($basePath && strpos($uri, $basePath) === 0) {
	$uri = substr($uri, strlen($basePath));
}
if ($uri === '' || ($uri[0] !== '/')) {
	$uri = '/' . $uri;
}

echo "Final URI (after): " . $uri . "\n";


