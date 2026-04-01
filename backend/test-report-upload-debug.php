<?php
/**
 * Test Report Upload - Debug Script
 * Tests authentication and report upload functionality
 */

require __DIR__ . '/src/bootstrap.php';

echo "=== REPORT UPLOAD DEBUG TEST ===\n\n";

// Test 1: Check database connection
echo "1. Testing database connection...\n";
try {
    $db = Src\Config\DB::conn();
    echo "   ✓ Database connected\n\n";
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Check if user exists
echo "2. Checking test user (deepankumar@gmail.com)...\n";
$stmt = $db->prepare("SELECT id, email, role, password FROM users WHERE email = ?");
$stmt->execute(['deepankumar@gmail.com']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "   ✓ User found: ID={$user['id']}, Role={$user['role']}\n";
    echo "   Password hash: " . substr($user['password'], 0, 20) . "...\n\n";
} else {
    echo "   ✗ User not found\n\n";
    exit(1);
}

// Test 3: Test login and get token
echo "3. Testing login to get JWT token...\n";
$loginData = [
    'email' => 'deepankumar@gmail.com',
    'password' => 'Welcome@456'
];

$ch = curl_init('http://localhost:8000/api/v1/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
$loginResult = json_decode($response, true);

if ($httpCode === 200 && isset($loginResult['data']['token'])) {
    $token = $loginResult['data']['token'];
    echo "   ✓ Login successful\n";
    echo "   Token: " . substr($token, 0, 30) . "...\n\n";
} else {
    echo "   ✗ Login failed\n";
    echo "   Response: $response\n\n";
    exit(1);
}

// Test 4: Verify token
echo "4. Verifying JWT token...\n";
try {
    $payload = Src\Utils\Jwt::decode($token, $_ENV['JWT_SECRET'] ?? '');
    echo "   ✓ Token valid\n";
    echo "   User ID: {$payload['uid']}\n";
    echo "   Role: {$payload['role']}\n\n";
} catch (Exception $e) {
    echo "   ✗ Token invalid: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 5: Check uploads directory
echo "5. Checking uploads directory...\n";
$uploadsDir = __DIR__ . '/public/uploads/reports';
if (!is_dir($uploadsDir)) {
    echo "   Creating uploads directory...\n";
    mkdir($uploadsDir, 0777, true);
}
if (is_writable($uploadsDir)) {
    echo "   ✓ Uploads directory writable: $uploadsDir\n\n";
} else {
    echo "   ✗ Uploads directory not writable: $uploadsDir\n\n";
}

// Test 6: Test report upload with authentication
echo "6. Testing report upload with authentication...\n";

// Create a test file
$testContent = "Test report content - " . date('Y-m-d H:i:s');
$testFile = sys_get_temp_dir() . '/test_report.txt';
file_put_contents($testFile, $testContent);

$ch = curl_init('http://localhost:8000/api/v1/reports');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'patient_id' => $user['id'],
    'title' => 'Test Report - ' . date('Y-m-d H:i:s'),
    'description' => 'Automated test upload',
    'file' => new CURLFile($testFile, 'text/plain', 'test_report.txt')
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
if ($curlError) {
    echo "   cURL Error: $curlError\n";
}

$uploadResult = json_decode($response, true);
if ($httpCode === 201 && isset($uploadResult['success']) && $uploadResult['success']) {
    echo "   ✓ Upload successful\n";
    echo "   Report ID: {$uploadResult['data']['id']}\n";
    echo "   File URL: {$uploadResult['data']['file_url']}\n\n";
} else {
    echo "   ✗ Upload failed\n";
    echo "   Response: $response\n\n";
}

// Test 7: Check server is accessible from network
echo "7. Checking network accessibility...\n";
$interfaces = [];
if (PHP_OS_FAMILY === 'Windows') {
    exec('ipconfig', $output);
    foreach ($output as $line) {
        if (preg_match('/IPv4.*?:\s*(\d+\.\d+\.\d+\.\d+)/', $line, $m)) {
            $interfaces[] = $m[1];
        }
    }
} else {
    exec('hostname -I', $output);
    $interfaces = explode(' ', trim($output[0] ?? ''));
}

echo "   Available IP addresses:\n";
foreach ($interfaces as $ip) {
    if (!empty($ip) && $ip !== '127.0.0.1') {
        echo "   - $ip\n";
    }
}
echo "\n";

// Test 8: Check PHP server status
echo "8. PHP Server Status:\n";
echo "   Expected: php -S 0.0.0.0:8000 -t public\n";
echo "   Current IP in app: 10.34.163.165:8000\n";
echo "   Make sure:\n";
echo "   - PHP server is running on port 8000\n";
echo "   - Mobile is connected to PC's hotspot\n";
echo "   - Firewall allows port 8000\n\n";

echo "=== TEST COMPLETE ===\n";
echo "\nIf upload failed with 403:\n";
echo "1. Re-login in the app to get fresh token\n";
echo "2. Check token is being sent in Authorization header\n";
echo "3. Verify JWT_SECRET in .env matches\n";
echo "\nIf upload failed with network error:\n";
echo "1. Verify PHP server is running: php -S 0.0.0.0:8000 -t public\n";
echo "2. Check mobile is on PC's hotspot\n";
echo "3. Test connection: curl http://10.34.163.165:8000/api/v1/auth/login\n";
