<?php
/**
 * Test AI Controller
 */

require_once __DIR__ . '/src/bootstrap.php';

echo "===========================================\n";
echo "Testing AI Controller\n";
echo "===========================================\n\n";

try {
    echo "Loading AIController...\n";
    $controller = new Src\Controllers\AIController();
    echo "✅ AIController loaded successfully!\n\n";
    
    echo "Controller is ready to process reports.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n===========================================\n";
