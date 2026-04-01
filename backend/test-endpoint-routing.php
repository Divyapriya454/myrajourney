<?php
// Simple test to verify endpoint routing without authentication
echo "=== ENDPOINT ROUTING TEST ===\n";
echo "Testing if the missed dose endpoints are properly routed\n\n";

// Test 1: Check /api/v1/medications/log
echo "1. Testing /api/v1/medications/log endpoint...\n";
$url1 = "http://10.114.201.165:8000/api/v1/medications/log";
$testData = json_encode([
    "patient_medication_id" => "123",
    "status" => "SKIPPED",
    "notes" => "Test",
    "taken_at" => date('Y-m-d H:i:s')
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $testData);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response1 = curl_exec($ch);
$httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error1 = curl_error($ch);
curl_close($ch);

echo "URL: $url1\n";
echo "HTTP Code: $httpCode1\n";
if ($error1) echo "CURL Error: $error1\n";
echo "Response: $response1\n\n";

// Test 2: Check /api/v1/medication-logs
echo "2. Testing /api/v1/medication-logs endpoint...\n";
$url2 = "http://10.114.201.165:8000/api/v1/medication-logs";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url2);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $testData);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response2 = curl_exec($ch);
$httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error2 = curl_error($ch);
curl_close($ch);

echo "URL: $url2\n";
echo "HTTP Code: $httpCode2\n";
if ($error2) echo "CURL Error: $error2\n";
echo "Response: $response2\n\n";

// Analyze results
echo "=== ANALYSIS ===\n";

function analyzeResponse($httpCode, $response, $endpointName) {
    if ($httpCode === 404) {
        echo "❌ $endpointName: ENDPOINT NOT FOUND (404)\n";
        echo "   - Route is missing from backend/public/index.php\n";
        return false;
    } elseif ($httpCode === 401) {
        echo "✅ $endpointName: ENDPOINT EXISTS (401 - Unauthorized)\n";
        echo "   - Route is working, authentication required (expected)\n";
        return true;
    } elseif ($httpCode === 400 || $httpCode === 422) {
        echo "✅ $endpointName: ENDPOINT EXISTS (400/422 - Validation Error)\n";
        echo "   - Route is working, validation failed (expected)\n";
        return true;
    } elseif ($httpCode === 403) {
        echo "✅ $endpointName: ENDPOINT EXISTS (403 - Forbidden)\n";
        echo "   - Route is working, authorization failed (expected)\n";
        return true;
    } elseif ($httpCode === 0) {
        echo "❌ $endpointName: CONNECTION FAILED\n";
        echo "   - Server might be down or unreachable\n";
        return false;
    } elseif ($httpCode >= 200 && $httpCode < 300) {
        echo "✅ $endpointName: ENDPOINT WORKING ($httpCode - Success)\n";
        return true;
    } else {
        echo "⚠️  $endpointName: UNEXPECTED RESPONSE ($httpCode)\n";
        echo "   - Response: " . substr($response, 0, 100) . "...\n";
        return false;
    }
}

$endpoint1Working = analyzeResponse($httpCode1, $response1, "PRIMARY ENDPOINT");
$endpoint2Working = analyzeResponse($httpCode2, $response2, "ALTERNATIVE ENDPOINT");

echo "\n=== FINAL RESULT ===\n";
if ($endpoint1Working && $endpoint2Working) {
    echo "🎉 SUCCESS! Both endpoints are properly routed!\n";
    echo "✅ /api/v1/medications/log - Working\n";
    echo "✅ /api/v1/medication-logs - Working\n";
    echo "\n📱 The Android app should now be able to report missed doses!\n";
    echo "🔧 Next step: Test with proper authentication in the Android app\n";
} elseif ($endpoint1Working || $endpoint2Working) {
    echo "⚠️  PARTIAL SUCCESS - One endpoint is working\n";
    if ($endpoint1Working) {
        echo "✅ Primary endpoint (/api/v1/medications/log) is working\n";
    }
    if ($endpoint2Working) {
        echo "✅ Alternative endpoint (/api/v1/medication-logs) is working\n";
    }
    echo "📱 The Android app should work with the working endpoint\n";
} else {
    echo "❌ BOTH ENDPOINTS FAILED\n";
    echo "🔧 Check server status and routing configuration\n";
}
?>