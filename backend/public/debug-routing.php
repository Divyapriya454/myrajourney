<?php
// Debug routing to see what's happening
echo "=== Routing Debug ===\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'not set') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'not set') . "\n";

$uri = $_SERVER['PATH_INFO'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
echo "Calculated URI: $uri\n";

// Test the regex patterns
$testUri = "/api/v1/admin/users/999";
echo "\nTesting URI: $testUri\n";

if (preg_match('#^/api/v1/admin/users/(\d+)$#', $testUri, $matches)) {
    echo "✅ DELETE pattern matches! User ID: " . $matches[1] . "\n";
} else {
    echo "❌ DELETE pattern does not match\n";
}

if (preg_match('#^/api/v1/admin/users/(\d+)/delete$#', $testUri . "/delete", $matches)) {
    echo "✅ POST pattern matches! User ID: " . $matches[1] . "\n";
} else {
    echo "❌ POST pattern does not match\n";
}
?>