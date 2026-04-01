<?php
/**
 * Backend Connection Test - CLI Version
 * Run: php test-backend-cli.php
 */

require __DIR__ . '/src/bootstrap.php';

use Src\Config\Config;
use Src\Config\DB;

echo "\n";
echo "========================================\n";
echo "   Backend Connection Test (CLI)\n";
echo "========================================\n\n";

$allPassed = true;

// 1. Environment Test
echo "1. Environment Configuration\n";
echo "----------------------------------------\n";

$envFile = __DIR__ . '/.env';
echo "Environment file: $envFile\n";
echo "File exists: " . (file_exists($envFile) ? "YES" : "NO") . "\n\n";

$envVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'JWT_SECRET'];
foreach ($envVars as $var) {
    $value = Config::get($var);
    $display = $var === 'DB_PASS' || $var === 'JWT_SECRET' 
        ? ($value ? '***SET***' : 'NOT SET') 
        : ($value ?: 'NOT SET');
    $status = $value ? "✓" : "✗";
    echo "  $status $var: $display\n";
    if (!$value && $var !== 'DB_PASS') {
        $allPassed = false;
    }
}

echo "\n";

// 2. Database Test
echo "2. Database Connection\n";
echo "----------------------------------------\n";

try {
    $conn = DB::conn();
    echo "✓ Database connection: SUCCESS\n";
    
    $stmt = $conn->query("SELECT VERSION() as version");
    $version = $stmt->fetch();
    echo "  MySQL Version: " . $version['version'] . "\n";
    
    $stmt = $conn->query("SELECT DATABASE() as dbname");
    $dbname = $stmt->fetch();
    echo "  Database: " . $dbname['dbname'] . "\n";
    
    // Check tables
    $requiredTables = ['users', 'profiles', 'appointments', 'reports', 'medications'];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM `$table`");
            $result = $stmt->fetch();
            echo "  ✓ Table '$table': exists (" . $result['count'] . " rows)\n";
        } catch (PDOException $e) {
            echo "  ✗ Table '$table': MISSING\n";
            $missingTables[] = $table;
            $allPassed = false;
        }
    }
    
    if (!empty($missingTables)) {
        echo "\n  WARNING: Missing tables detected. Run migrations!\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database connection: FAILED\n";
    echo "  Error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

echo "\n";

// 3. API Test (if running via web server)
echo "3. API Endpoint Test\n";
echo "----------------------------------------\n";

$baseUrl = 'http://localhost/backend/public';
$apiUrl = $baseUrl . '/api/v1/education/articles';

echo "Testing: $apiUrl\n";

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "✗ API endpoint: FAILED\n";
    echo "  Error: $curlError\n";
    echo "  Make sure Apache is running!\n";
    $allPassed = false;
} elseif ($httpCode === 200) {
    echo "✓ API endpoint: SUCCESS (HTTP $httpCode)\n";
    $json = json_decode($response, true);
    if ($json && isset($json['success'])) {
        echo "  Response: " . ($json['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    }
} else {
    echo "✗ API endpoint: FAILED (HTTP $httpCode)\n";
    echo "  Response: " . substr($response, 0, 200) . "\n";
    $allPassed = false;
}

echo "\n";

// Summary
echo "========================================\n";
if ($allPassed) {
    echo "✓ ALL TESTS PASSED\n";
} else {
    echo "✗ SOME TESTS FAILED\n";
}
echo "========================================\n\n";

exit($allPassed ? 0 : 1);









