<?php
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'NOT SET') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
echo "Parsed URI: " . $uri . "\n";


