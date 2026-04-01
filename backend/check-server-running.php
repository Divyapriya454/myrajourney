<?php
/**
 * Quick Server Status Check
 */

echo "=== PHP SERVER STATUS CHECK ===\n\n";

// Check if server is running on port 8000
echo "1. Checking if port 8000 is in use...\n";
if (PHP_OS_FAMILY === 'Windows') {
    exec('netstat -ano | findstr :8000', $output);
    if (!empty($output)) {
        echo "   ✓ Port 8000 is in use:\n";
        foreach ($output as $line) {
            echo "   $line\n";
        }
    } else {
        echo "   ✗ Port 8000 is NOT in use\n";
        echo "   Start server with: php -S 0.0.0.0:8000 -t public\n";
    }
} else {
    exec('lsof -i :8000', $output);
    if (!empty($output)) {
        echo "   ✓ Port 8000 is in use:\n";
        foreach ($output as $line) {
            echo "   $line\n";
        }
    } else {
        echo "   ✗ Port 8000 is NOT in use\n";
        echo "   Start server with: php -S 0.0.0.0:8000 -t public\n";
    }
}

echo "\n2. Network IP addresses:\n";
if (PHP_OS_FAMILY === 'Windows') {
    exec('ipconfig', $output);
    foreach ($output as $line) {
        if (preg_match('/IPv4.*?:\s*(\d+\.\d+\.\d+\.\d+)/', $line, $m)) {
            echo "   - {$m[1]}\n";
        }
    }
} else {
    exec('hostname -I', $output);
    $ips = explode(' ', trim($output[0] ?? ''));
    foreach ($ips as $ip) {
        if (!empty($ip)) {
            echo "   - $ip\n";
        }
    }
}

echo "\n3. Expected configuration:\n";
echo "   - Server: php -S 0.0.0.0:8000 -t public\n";
echo "   - App IP: 10.34.163.165:8000\n";
echo "   - Mobile: Connected to PC's hotspot\n";

echo "\n=== END ===\n";
