<?php
/**
 * Test GD Extension in Web Server Context
 */

header('Content-Type: application/json');

$result = [
    'gd_loaded' => extension_loaded('gd'),
    'php_version' => PHP_VERSION,
    'loaded_extensions' => get_loaded_extensions()
];

if ($result['gd_loaded']) {
    $result['gd_info'] = gd_info();
}

echo json_encode($result, JSON_PRETTY_PRINT);
