<?php
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
$scriptDir = str_replace('\\', '/', rtrim(__DIR__, '/\\'));
$basePath = str_replace($docRoot, '', $scriptDir);
if ($basePath && strpos($uri, $basePath) === 0) {
	$uri = substr($uri, strlen($basePath));
}
if ($uri === '' || ($uri[0] !== '/')) {
	$uri = '/' . $uri;
}

echo json_encode([
	'method' => $method,
	'raw_uri' => $_SERVER['REQUEST_URI'] ?? '/',
	'parsed_uri' => $uri,
	'base_path' => $basePath,
	'matches_route' => [
		'/api/v1/education/articles' => ($uri === '/api/v1/education/articles' && $method === 'GET'),
		'/api/v1/auth/login' => ($uri === '/api/v1/auth/login' && $method === 'POST'),
		'/api/v1/auth/register' => ($uri === '/api/v1/auth/register' && $method === 'POST'),
	]
], JSON_PRETTY_PRINT);


