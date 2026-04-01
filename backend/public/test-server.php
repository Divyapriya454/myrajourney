<?php
echo "Server is working!\n";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'not set') . "\n";
echo "POST data: " . print_r($_POST, true) . "\n";
echo "FILES data: " . print_r($_FILES, true) . "\n";
?>