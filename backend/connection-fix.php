<?php
// Quick fix for connection issues with PHP built-in server
// Add this to the beginning of SymptomController.php create() method

// Set headers to prevent connection issues
header('Connection: close');
header('Content-Type: application/json; charset=utf-8');

// Ensure output buffering is handled properly
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Register shutdown function to ensure proper connection handling
register_shutdown_function(function() {
    if (ob_get_level()) {
        ob_end_flush();
    }
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
});
?>
