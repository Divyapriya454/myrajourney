<?php
/**
 * Comprehensive Backend Connection Test
 * Tests all backend connections: Environment, Database, API Endpoints
 * 
 * Access via: http://localhost/backend/public/test-backend-connections.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Backend Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; border-left: 4px solid #2196F3; padding-left: 10px; }
        .test-section { margin: 20px 0; padding: 15px; background: #fafafa; border-radius: 4px; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        .info { color: #2196F3; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #4CAF50; color: white; }
        .status-ok { color: #4CAF50; }
        .status-fail { color: #f44336; }
        .endpoint-test { margin: 10px 0; padding: 10px; background: white; border-left: 3px solid #2196F3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Backend Connection Test Report</h1>
        <p><strong>Test Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <?php
        require __DIR__ . '/../src/bootstrap.php';
        
        use Src\Config\Config;
        use Src\Config\DB;
        use Src\Utils\Response;
        
        $results = [
            'env' => [],
            'database' => [],
            'api' => [],
            'overall' => true
        ];
        
        // ============================================
        // 1. ENVIRONMENT CONFIGURATION TEST
        // ============================================
        echo '<div class="test-section">';
        echo '<h2>1. Environment Configuration</h2>';
        
        $envVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'JWT_SECRET', 'JWT_TTL_SECONDS', 'CORS_ORIGINS'];
        $envFile = __DIR__ . '/../.env';
        
        echo '<p><strong>Environment File:</strong> ' . $envFile . '</p>';
        echo '<p><strong>File Exists:</strong> ' . (file_exists($envFile) ? '<span class="success">✅ Yes</span>' : '<span class="error">❌ No</span>') . '</p>';
        
        if (!file_exists($envFile)) {
            echo '<p class="warning">⚠️ Warning: .env file not found. Using default values.</p>';
        }
        
        echo '<table>';
        echo '<tr><th>Variable</th><th>Value</th><th>Status</th></tr>';
        
        foreach ($envVars as $var) {
            $value = Config::get($var);
            $displayValue = $value;
            
            if ($var === 'DB_PASS') {
                $displayValue = $value ? '***SET***' : 'EMPTY';
            } elseif ($var === 'JWT_SECRET') {
                $displayValue = $value ? '***SET***' : 'NOT SET';
            }
            
            $status = $value ? '<span class="status-ok">✅ Set</span>' : '<span class="status-fail">❌ Not Set</span>';
            
            if ($var === 'DB_PASS' && !$value) {
                $status = '<span class="warning">⚠️ Empty (may be OK if MySQL has no password)</span>';
            }
            
            echo "<tr><td><strong>$var</strong></td><td>$displayValue</td><td>$status</td></tr>";
            
            $results['env'][$var] = (bool)$value;
        }
        
        echo '</table>';
        echo '</div>';
        
        // ============================================
        // 2. DATABASE CONNECTION TEST
        // ============================================
        echo '<div class="test-section">';
        echo '<h2>2. Database Connection</h2>';
        
        try {
            $conn = DB::conn();
            echo '<p class="success">✅ Database connection: SUCCESS</p>';
            
            // Test query
            $stmt = $conn->query("SELECT VERSION() as version");
            $version = $stmt->fetch();
            echo '<p><strong>MySQL Version:</strong> ' . htmlspecialchars($version['version']) . '</p>';
            
            // Check database name
            $stmt = $conn->query("SELECT DATABASE() as dbname");
            $dbname = $stmt->fetch();
            echo '<p><strong>Current Database:</strong> ' . htmlspecialchars($dbname['dbname']) . '</p>';
            
            // Check required tables
            $requiredTables = [
                'users', 'profiles', 'settings', 'appointments', 'reports', 
                'medications', 'rehab', 'notifications', 'education', 
                'symptoms_metrics', 'password_resets'
            ];
            
            echo '<h3>Required Tables:</h3>';
            echo '<table>';
            echo '<tr><th>Table Name</th><th>Status</th><th>Row Count</th></tr>';
            
            foreach ($requiredTables as $table) {
                try {
                    $stmt = $conn->query("SELECT COUNT(*) as count FROM `$table`");
                    $result = $stmt->fetch();
                    $count = $result['count'];
                    echo "<tr><td><strong>$table</strong></td><td><span class='status-ok'>✅ Exists</span></td><td>$count</td></tr>";
                    $results['database'][$table] = true;
                } catch (PDOException $e) {
                    echo "<tr><td><strong>$table</strong></td><td><span class='status-fail'>❌ Missing</span></td><td>-</td></tr>";
                    $results['database'][$table] = false;
                    $results['overall'] = false;
                }
            }
            
            echo '</table>';
            
            // Test a simple query
            try {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
                $result = $stmt->fetch();
                echo '<p class="success">✅ Test query successful. Users count: ' . $result['count'] . '</p>';
            } catch (PDOException $e) {
                echo '<p class="error">❌ Test query failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
                $results['overall'] = false;
            }
            
            $results['database']['connection'] = true;
            
        } catch (Exception $e) {
            echo '<p class="error">❌ Database connection: FAILED</p>';
            echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>Troubleshooting:</strong></p>';
            echo '<ul>';
            echo '<li>Check if MySQL is running in XAMPP</li>';
            echo '<li>Verify database credentials in .env file</li>';
            echo '<li>Ensure database "myrajourney" exists</li>';
            echo '<li>Check if migrations have been imported</li>';
            echo '</ul>';
            $results['database']['connection'] = false;
            $results['overall'] = false;
        }
        
        echo '</div>';
        
        // ============================================
        // 3. API ENDPOINT TESTS
        // ============================================
        echo '<div class="test-section">';
        echo '<h2>3. API Endpoint Tests</h2>';
        
        $baseUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']);
        $apiBase = $baseUrl . '/api/v1';
        
        echo '<p><strong>Base URL:</strong> ' . htmlspecialchars($baseUrl) . '</p>';
        echo '<p><strong>API Base:</strong> ' . htmlspecialchars($apiBase) . '</p>';
        
        // Test public endpoint (no auth required)
        echo '<div class="endpoint-test">';
        echo '<h3>3.1 Public Endpoint Test (No Auth Required)</h3>';
        echo '<p><strong>Endpoint:</strong> GET /api/v1/education/articles</p>';
        
        $ch = curl_init($apiBase . '/education/articles');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            echo '<p class="error">❌ Connection Error: ' . htmlspecialchars($curlError) . '</p>';
            echo '<p class="warning">⚠️ Make sure Apache is running and the backend is accessible.</p>';
            $results['api']['public'] = false;
            $results['overall'] = false;
        } elseif ($httpCode === 200) {
            echo '<p class="success">✅ Public endpoint: SUCCESS (HTTP ' . $httpCode . ')</p>';
            $json = json_decode($response, true);
            if ($json) {
                echo '<pre>' . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT)) . '</pre>';
            }
            $results['api']['public'] = true;
        } else {
            echo '<p class="error">❌ Public endpoint: FAILED (HTTP ' . $httpCode . ')</p>';
            echo '<pre>' . htmlspecialchars($response) . '</pre>';
            $results['api']['public'] = false;
            $results['overall'] = false;
        }
        echo '</div>';
        
        // Test authentication endpoints
        echo '<div class="endpoint-test">';
        echo '<h3>3.2 Authentication Endpoint Test</h3>';
        echo '<p><strong>Endpoint:</strong> POST /api/v1/auth/register</p>';
        
        $testEmail = 'test_' . time() . '@test.com';
        $registerData = json_encode([
            'email' => $testEmail,
            'password' => 'test123456',
            'role' => 'PATIENT',
            'name' => 'Test User'
        ]);
        
        $ch = curl_init($apiBase . '/auth/register');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $registerData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 || $httpCode === 201) {
            echo '<p class="success">✅ Registration endpoint: SUCCESS (HTTP ' . $httpCode . ')</p>';
            $json = json_decode($response, true);
            if ($json && isset($json['data']['token'])) {
                $token = $json['data']['token'];
                echo '<p class="info">Token received: ' . htmlspecialchars(substr($token, 0, 50)) . '...</p>';
                $results['api']['auth'] = true;
                
                // Test protected endpoint with token
                echo '<h3>3.3 Protected Endpoint Test (With Token)</h3>';
                echo '<p><strong>Endpoint:</strong> GET /api/v1/auth/me</p>';
                
                $ch = curl_init($apiBase . '/auth/me');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    echo '<p class="success">✅ Protected endpoint: SUCCESS (HTTP ' . $httpCode . ')</p>';
                    $json = json_decode($response, true);
                    if ($json) {
                        echo '<pre>' . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT)) . '</pre>';
                    }
                    $results['api']['protected'] = true;
                } else {
                    echo '<p class="error">❌ Protected endpoint: FAILED (HTTP ' . $httpCode . ')</p>';
                    echo '<pre>' . htmlspecialchars($response) . '</pre>';
                    $results['api']['protected'] = false;
                    $results['overall'] = false;
                }
            } else {
                echo '<p class="warning">⚠️ Registration succeeded but no token in response</p>';
                $results['api']['auth'] = false;
            }
        } else {
            echo '<p class="error">❌ Registration endpoint: FAILED (HTTP ' . $httpCode . ')</p>';
            echo '<pre>' . htmlspecialchars($response) . '</pre>';
            $results['api']['auth'] = false;
            $results['overall'] = false;
        }
        echo '</div>';
        
        // Test CORS headers
        echo '<div class="endpoint-test">';
        echo '<h3>3.4 CORS Configuration Test</h3>';
        
        $ch = curl_init($apiBase . '/education/articles');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $headers = [];
        if ($response) {
            $headerLines = explode("\r\n", $response);
            foreach ($headerLines as $line) {
                if (stripos($line, 'Access-Control') !== false) {
                    $headers[] = trim($line);
                }
            }
        }
        
        if (!empty($headers)) {
            echo '<p class="success">✅ CORS headers detected:</p>';
            echo '<ul>';
            foreach ($headers as $header) {
                echo '<li>' . htmlspecialchars($header) . '</li>';
            }
            echo '</ul>';
            $results['api']['cors'] = true;
        } else {
            echo '<p class="warning">⚠️ No CORS headers detected (may be OK for same-origin requests)</p>';
            $results['api']['cors'] = false;
        }
        echo '</div>';
        
        echo '</div>';
        
        // ============================================
        // 4. SUMMARY
        // ============================================
        echo '<div class="test-section">';
        echo '<h2>4. Test Summary</h2>';
        
        $envOk = !in_array(false, $results['env'], true);
        $dbOk = isset($results['database']['connection']) && $results['database']['connection'];
        $apiOk = isset($results['api']['public']) && $results['api']['public'];
        
        echo '<table>';
        echo '<tr><th>Component</th><th>Status</th></tr>';
        echo '<tr><td>Environment Configuration</td><td>' . ($envOk ? '<span class="status-ok">✅ OK</span>' : '<span class="status-fail">❌ Issues Found</span>') . '</td></tr>';
        echo '<tr><td>Database Connection</td><td>' . ($dbOk ? '<span class="status-ok">✅ OK</span>' : '<span class="status-fail">❌ Failed</span>') . '</td></tr>';
        echo '<tr><td>API Endpoints</td><td>' . ($apiOk ? '<span class="status-ok">✅ OK</span>' : '<span class="status-fail">❌ Failed</span>') . '</td></tr>';
        echo '</table>';
        
        if ($results['overall']) {
            echo '<p class="success" style="font-size: 18px; padding: 15px; background: #e8f5e9; border-radius: 4px;">';
            echo '✅ <strong>All Backend Connections: SUCCESS</strong>';
            echo '</p>';
        } else {
            echo '<p class="error" style="font-size: 18px; padding: 15px; background: #ffebee; border-radius: 4px;">';
            echo '❌ <strong>Some Backend Connections: FAILED</strong>';
            echo '</p>';
            echo '<p><strong>Next Steps:</strong></p>';
            echo '<ul>';
            if (!$envOk) {
                echo '<li>Check .env file configuration</li>';
            }
            if (!$dbOk) {
                echo '<li>Verify MySQL is running and database exists</li>';
                echo '<li>Check database credentials</li>';
                echo '<li>Import database migrations if tables are missing</li>';
            }
            if (!$apiOk) {
                echo '<li>Verify Apache is running</li>';
                echo '<li>Check backend/public/index.php is accessible</li>';
                echo '<li>Review PHP error logs</li>';
            }
            echo '</ul>';
        }
        
        echo '</div>';
        
        // ============================================
        // 5. QUICK LINKS
        // ============================================
        echo '<div class="test-section">';
        echo '<h2>5. Quick Test Links</h2>';
        echo '<ul>';
        echo '<li><a href="test-env.php" target="_blank">Environment Variables Test</a></li>';
        echo '<li><a href="test-db.php" target="_blank">Database Connection Test</a></li>';
        echo '<li><a href="test-db-detailed.php" target="_blank">Detailed Database Test</a></li>';
        echo '<li><a href="api-info.php" target="_blank">API Info</a></li>';
        echo '<li><a href="' . htmlspecialchars($apiBase . '/education/articles') . '" target="_blank">Public API Endpoint</a></li>';
        echo '</ul>';
        echo '</div>';
        ?>
        
        <hr>
        <p style="color: #666; font-size: 12px;">
            <strong>Note:</strong> This test script checks backend connections. 
            For detailed API testing, use the PowerShell script: <code>QUICK_TEST_SCRIPT.ps1</code>
        </p>
    </div>
</body>
</html>









