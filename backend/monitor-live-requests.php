<?php
/**
 * Monitor Live API Requests
 * Shows what the Android app is requesting in real-time
 */

echo "=================================================================\n";
echo "MONITORING LIVE API REQUESTS\n";
echo "=================================================================\n\n";

$logFile = __DIR__ . '/public/api_log.txt';

if (!file_exists($logFile)) {
    echo "Log file not found. Make sure backend server is running.\n";
    exit(1);
}

echo "Watching: $logFile\n";
echo "Press Ctrl+C to stop\n\n";
echo str_repeat('=', 80) . "\n";

// Get last 50 lines
$lines = file($logFile);
$recentLines = array_slice($lines, -50);

foreach ($recentLines as $line) {
    echo $line;
}

echo str_repeat('=', 80) . "\n";
echo "\nRecent errors and issues:\n";
echo str_repeat('=', 80) . "\n";

// Analyze for errors
$errors = [];
foreach ($recentLines as $line) {
    if (stripos($line, 'error') !== false || 
        stripos($line, 'failed') !== false || 
        stripos($line, 'column not found') !== false ||
        stripos($line, '500') !== false) {
        $errors[] = $line;
    }
}

if (empty($errors)) {
    echo "✓ No errors found in recent requests!\n";
} else {
    foreach ($errors as $error) {
        echo $error;
    }
}

echo "\n" . str_repeat('=', 80) . "\n";
