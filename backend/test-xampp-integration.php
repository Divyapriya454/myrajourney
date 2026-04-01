<?php
/**
 * XAMPP Integration Test Script
 * Tests all connections after XAMPP reinstallation
 */

echo "=================================================================\n";
echo "XAMPP INTEGRATION TEST\n";
echo "=================================================================\n\n";

// Test 1: Check if script is accessible
echo "✓ Test 1: PHP Script Execution\n";
echo "  PHP Version: " . phpversion() . "\n\n";

// Test 2: Check .env file
echo "Test 2: Environment Configuration\n";
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "  ✓ .env file found\n";
    $envContent = file_get_contents($envFile);
    
    // Parse .env manually
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $_ENV[trim($parts[0])] = trim($parts[1]);
        }
    }
    
    echo "  DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
    echo "  DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NOT SET') . "\n";
    echo "  DB_USER: " . ($_ENV['DB_USER'] ?? 'NOT SET') . "\n";
    echo "  DB_PASS: " . (empty($_ENV['DB_PASS']) ? '(empty)' : '***') . "\n\n";
} else {
    echo "  ✗ .env file NOT found at: $envFile\n\n";
}

// Test 3: MySQL Connection
echo "Test 3: MySQL Database Connection\n";
try {
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $dbname = $_ENV['DB_NAME'] ?? 'myrajourney';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';
    
    $dsn = "mysql:host=$host;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "  ✓ MySQL Server Connected\n";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    $dbExists = $stmt->fetch();
    
    if ($dbExists) {
        echo "  ✓ Database '$dbname' exists\n";
        
        // Connect to the database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Check tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "  ✓ Tables found: " . count($tables) . "\n";
        
        $requiredTables = [
            'users', 'patients', 'doctors', 'appointments', 
            'patient_medications', 'rehab_plans', 'rehab_exercises',
            'reports', 'symptoms', 'notifications'
        ];
        
        $missingTables = array_diff($requiredTables, $tables);
        
        if (empty($missingTables)) {
            echo "  ✓ All required tables present\n\n";
        } else {
            echo "  ⚠ Missing tables: " . implode(', ', $missingTables) . "\n\n";
        }
        
    } else {
        echo "  ✗ Database '$dbname' does NOT exist\n";
        echo "  → Run setup-fresh-database.php to create it\n\n";
    }
    
} catch (PDOException $e) {
    echo "  ✗ MySQL Connection Failed: " . $e->getMessage() . "\n\n";
}

// Test 4: Check XAMPP htdocs symlink/copy
echo "Test 4: XAMPP htdocs Integration\n";
$xamppPath = 'C:/xampp/htdocs/myrajourney';
if (file_exists($xamppPath)) {
    echo "  ✓ Backend folder found in htdocs\n";
    
    // Check if it's the same folder or a copy
    $currentPath = realpath(__DIR__);
    $htdocsPath = realpath($xamppPath);
    
    if ($currentPath === $htdocsPath) {
        echo "  ✓ Same folder (symlink or direct location)\n\n";
    } else {
        echo "  ⚠ Different folder (copy)\n";
        echo "    Current: $currentPath\n";
        echo "    htdocs:  $htdocsPath\n";
        echo "  → You may need to sync changes between folders\n\n";
    }
} else {
    echo "  ✗ Backend folder NOT found in htdocs\n";
    echo "  → Copy or symlink backend folder to: $xamppPath\n\n";
}

// Test 5: Check if Apache is serving files
echo "Test 5: Apache Web Server\n";
$testUrl = "http://localhost/myrajourney/simple-test.php";
echo "  Testing URL: $testUrl\n";

// Create a simple test file
file_put_contents(__DIR__ . '/simple-test.php', '<?php echo "OK"; ?>');

$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response === 'OK') {
    echo "  ✓ Apache is serving files correctly\n\n";
} else {
    echo "  ✗ Apache test failed (HTTP $httpCode)\n";
    echo "  → Make sure Apache is running in XAMPP Control Panel\n\n";
}

// Test 6: Test API endpoint
echo "Test 6: API Endpoint Test\n";
$apiUrl = "http://localhost/myrajourney/api/v1/test";
echo "  Testing URL: $apiUrl\n";

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "  ✓ API endpoint accessible\n";
    echo "  Response: " . substr($response, 0, 100) . "\n\n";
} else {
    echo "  ⚠ API endpoint returned HTTP $httpCode\n";
    echo "  → This is normal if API routing is not set up\n\n";
}

// Test 7: Check Android app network config
echo "Test 7: Android App Configuration\n";
$networkConfigPath = dirname(__DIR__) . '/app/src/main/res/values/network_config.xml';
if (file_exists($networkConfigPath)) {
    $config = file_get_contents($networkConfigPath);
    
    if (preg_match('/<string name="api_base_ip">(.*?)<\/string>/', $config, $matches)) {
        $currentIp = $matches[1];
        echo "  Current API IP: $currentIp\n";
        
        // Get local IP
        $localIp = gethostbyname(gethostname());
        echo "  Your PC IP: $localIp\n";
        
        if ($currentIp === $localIp || $currentIp === '10.0.2.2') {
            echo "  ✓ IP configuration looks correct\n\n";
        } else {
            echo "  ⚠ IP may need updating\n";
            echo "  → Use 10.0.2.2 for emulator\n";
            echo "  → Use $localIp for physical device\n\n";
        }
    }
} else {
    echo "  ⚠ network_config.xml not found\n\n";
}

// Summary
echo "=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n";
echo "1. Ensure XAMPP Apache and MySQL are running\n";
echo "2. Backend folder should be in C:/xampp/htdocs/myrajourney\n";
echo "3. Database 'myrajourney' should exist with all tables\n";
echo "4. Android app should point to correct IP address\n";
echo "5. Test from Android: http://YOUR_IP/myrajourney/api/v1/\n";
echo "=================================================================\n";
