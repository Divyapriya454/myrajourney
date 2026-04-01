<?php
// Test script for missed dose endpoint
echo "Testing Missed Dose API Endpoint\n";
echo "================================\n\n";

// Test 1: Check if endpoint exists
echo "1. Testing endpoint availability...\n";
$url = "http://10.114.201.165:8000/api/v1/medications/log";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "URL: $url\n";
echo "HTTP Status Code: $httpCode\n";
if ($error) {
    echo "CURL Error: $error\n";
}
echo "Response: $response\n\n";

if ($httpCode === 404) {
    echo "❌ ENDPOINT NOT FOUND - Route is missing!\n";
} elseif ($httpCode === 401 || $httpCode === 403) {
    echo "✅ ENDPOINT EXISTS - Authentication required (expected)\n";
} elseif ($httpCode === 400 || $httpCode === 422) {
    echo "✅ ENDPOINT EXISTS - Validation error (expected)\n";
} elseif ($httpCode === 0) {
    echo "❌ CONNECTION FAILED - Server might be down\n";
} else {
    echo "✅ ENDPOINT EXISTS - HTTP $httpCode\n";
}

// Test 2: Check alternative endpoint
echo "\n2. Testing alternative endpoint...\n";
$url2 = "http://10.114.201.165:8000/api/v1/medication-logs";
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $url2);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch2, CURLOPT_POSTFIELDS, '{}');
curl_setopt($ch2, CURLOPT_TIMEOUT, 10);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$error2 = curl_error($ch2);
curl_close($ch2);

echo "URL: $url2\n";
echo "HTTP Status Code: $httpCode2\n";
if ($error2) {
    echo "CURL Error: $error2\n";
}
echo "Response: $response2\n\n";

if ($httpCode2 === 404) {
    echo "❌ ALTERNATIVE ENDPOINT NOT FOUND\n";
} elseif ($httpCode2 === 0) {
    echo "❌ CONNECTION FAILED - Server might be down\n";
} else {
    echo "✅ ALTERNATIVE ENDPOINT EXISTS - HTTP $httpCode2\n";
}

echo "\n=== SUMMARY ===\n";
if ($httpCode !== 404 && $httpCode2 !== 404 && $httpCode !== 0 && $httpCode2 !== 0) {
    echo "✅ Both endpoints are working!\n";
    echo "✅ The missed dose functionality should work now.\n";
} elseif ($httpCode === 0 || $httpCode2 === 0) {
    echo "❌ Server connection failed.\n";
    echo "❌ Make sure the PHP server is running on 10.114.201.165:8000\n";
    echo "❌ Run: php -S 10.114.201.165:8000 -t backend/public\n";
} else {
    echo "❌ One or both endpoints are missing.\n";
    echo "❌ Check the routing in backend/public/index.php\n";
}
?>