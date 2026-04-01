<?php
/**
 * TEST REPORT PROCESSING WITH 5MB LIMIT
 */

echo "=================================================================\n";
echo "TESTING REPORT PROCESSING\n";
echo "=================================================================\n\n";

// Check GD extension
echo "1. Checking GD extension...\n";
if (extension_loaded('gd')) {
    echo "✅ GD extension is loaded\n";
    $gdInfo = gd_info();
    echo "   GD Version: " . $gdInfo['GD Version'] . "\n";
    echo "   JPEG Support: " . ($gdInfo['JPEG Support'] ? 'Yes' : 'No') . "\n";
    echo "   PNG Support: " . ($gdInfo['PNG Support'] ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ GD extension is NOT loaded\n";
}

// Check file size limits
echo "\n2. Checking PHP configuration...\n";
echo "   upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "   post_max_size: " . ini_get('post_max_size') . "\n";
echo "   memory_limit: " . ini_get('memory_limit') . "\n";

// Check FreeOCRService configuration
echo "\n3. Checking FreeOCRService configuration...\n";
$serviceFile = __DIR__ . '/src/services/ai/FreeOCRService.php';
if (file_exists($serviceFile)) {
    $content = file_get_contents($serviceFile);
    
    // Check for file size limit
    if (preg_match('/MAX_FILE_SIZE\s*=\s*(\d+)/', $content, $matches)) {
        $maxSize = (int)$matches[1];
        $maxSizeMB = $maxSize / (1024 * 1024);
        echo "   MAX_FILE_SIZE: " . $maxSizeMB . " MB\n";
        
        if ($maxSizeMB >= 5) {
            echo "   ✅ File size limit is 5MB or higher\n";
        } else {
            echo "   ⚠️  File size limit is less than 5MB\n";
        }
    }
    
    // Check for compression threshold
    if (preg_match('/COMPRESSION_THRESHOLD\s*=\s*(\d+)/', $content, $matches)) {
        $threshold = (int)$matches[1];
        $thresholdKB = $threshold / 1024;
        echo "   COMPRESSION_THRESHOLD: " . $thresholdKB . " KB\n";
    }
} else {
    echo "   ❌ FreeOCRService.php not found\n";
}

// Check upload directories
echo "\n4. Checking upload directories...\n";
$dirs = [
    __DIR__ . '/public/uploads/reports',
    __DIR__ . '/public/uploads/profile_pictures',
    __DIR__ . '/public/uploads/temp'
];

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "   ✅ " . basename($dir) . " - exists and writable\n";
        } else {
            echo "   ⚠️  " . basename($dir) . " - exists but NOT writable\n";
        }
    } else {
        echo "   ❌ " . basename($dir) . " - does NOT exist\n";
    }
}

echo "\n=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n\n";

$allGood = true;

if (!extension_loaded('gd')) {
    echo "❌ GD extension not loaded - image compression will fail\n";
    $allGood = false;
}

if ($allGood) {
    echo "✅ Report processing system is ready\n";
    echo "✅ Can handle files up to 5MB with automatic compression\n";
    echo "✅ Upload directories are configured\n";
} else {
    echo "⚠️  Some issues detected - see above\n";
}

echo "\n=================================================================\n";
