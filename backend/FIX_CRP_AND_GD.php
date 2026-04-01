<?php
/**
 * FIX CRP REPORT_ID AND CHECK GD
 */

require_once __DIR__ . '/src/bootstrap.php';

echo "=================================================================\n";
echo "FIXING CRP AND CHECKING GD\n";
echo "=================================================================\n\n";

$db = Src\Config\DB::conn();

// Fix 1: Add report_id to crp_measurements
echo "FIX 1: Adding report_id to crp_measurements\n";
try {
    $stmt = $db->prepare("SHOW COLUMNS FROM crp_measurements LIKE 'report_id'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "  ✓ report_id column already exists\n";
    } else {
        $db->exec("ALTER TABLE crp_measurements ADD COLUMN report_id INT NULL DEFAULT NULL AFTER doctor_id");
        echo "  ✅ Added report_id column\n";
    }
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// Check GD extension
echo "\nCHECK: GD Extension Status\n";
if (extension_loaded('gd')) {
    echo "  ✅ GD extension is loaded\n";
    $gdInfo = gd_info();
    echo "  Version: " . $gdInfo['GD Version'] . "\n";
    echo "  JPEG Support: " . ($gdInfo['JPEG Support'] ? 'Yes' : 'No') . "\n";
    echo "  PNG Support: " . ($gdInfo['PNG Support'] ? 'Yes' : 'No') . "\n";
} else {
    echo "  ❌ GD extension is NOT loaded\n";
    echo "  Note: You may need to restart Apache/PHP-FPM if using them\n";
}

echo "\n✅ FIXES APPLIED\n";
