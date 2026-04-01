# Backend Connection Test Script
# Run this script to test all backend connections

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   Backend Connection Test" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$baseUrl = "http://localhost/backend/public"
$allPassed = $true

# Test 1: Environment Configuration
Write-Host "1. Testing Environment Configuration..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/test-env.php" -Method GET -ErrorAction Stop
    Write-Host "   [OK] Environment test accessible" -ForegroundColor Green
    Write-Host "   Response:" -ForegroundColor Cyan
    $response.Content
} catch {
    Write-Host "   [FAIL] Environment test failed: $_" -ForegroundColor Red
    $allPassed = $false
}

Write-Host ""

# Test 2: Database Connection
Write-Host "2. Testing Database Connection..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/test-db.php" -Method GET -ErrorAction Stop
    if ($response.Content -match "SUCCESS") {
        Write-Host "   [OK] Database connection: SUCCESS" -ForegroundColor Green
        Write-Host "   Response:" -ForegroundColor Cyan
        $response.Content
    } else {
        Write-Host "   [FAIL] Database connection: FAILED" -ForegroundColor Red
        Write-Host "   Response:" -ForegroundColor Red
        $response.Content
        $allPassed = $false
    }
} catch {
    Write-Host "   [FAIL] Database test failed: $_" -ForegroundColor Red
    $allPassed = $false
}

Write-Host ""

# Test 3: Detailed Database Test
Write-Host "3. Testing Detailed Database Connection..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/test-db-detailed.php" -Method GET -ErrorAction Stop
    Write-Host "   Response:" -ForegroundColor Cyan
    $response.Content
    if ($response.Content -match "SUCCESS") {
        Write-Host "   [OK] Detailed database test: SUCCESS" -ForegroundColor Green
    } else {
        Write-Host "   [FAIL] Detailed database test: FAILED" -ForegroundColor Red
        $allPassed = $false
    }
} catch {
    Write-Host "   [FAIL] Detailed database test failed: $_" -ForegroundColor Red
    $allPassed = $false
}

Write-Host ""

# Test 4: Public API Endpoint
Write-Host "4. Testing Public API Endpoint..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api/v1/education/articles" -Method GET -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "   [OK] Public API endpoint: SUCCESS (HTTP $($response.StatusCode))" -ForegroundColor Green
        $json = $response.Content | ConvertFrom-Json
        Write-Host "   Response structure: OK" -ForegroundColor Green
        Write-Host "   Total articles: $($json.meta.total)" -ForegroundColor Cyan
    } else {
        Write-Host "   [FAIL] Public API endpoint: FAILED (HTTP $($response.StatusCode))" -ForegroundColor Red
        $allPassed = $false
    }
} catch {
    Write-Host "   [FAIL] Public API endpoint failed: $_" -ForegroundColor Red
    if ($_.Exception.Response) {
        $statusCode = [int]$_.Exception.Response.StatusCode.value__
        Write-Host "   HTTP Status: $statusCode" -ForegroundColor Red
    }
    $allPassed = $false
}

Write-Host ""

# Test 5: Comprehensive Connection Test (Web Interface)
Write-Host "5. Comprehensive Connection Test..." -ForegroundColor Yellow
$testUrl = "$baseUrl/test-backend-connections.php"
Write-Host "   Opening web interface: $testUrl" -ForegroundColor Cyan
Write-Host "   (Open this URL in your browser for detailed results)" -ForegroundColor Gray

try {
    $response = Invoke-WebRequest -Uri $testUrl -Method GET -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "   [OK] Comprehensive test page: ACCESSIBLE" -ForegroundColor Green
        Write-Host "   Open in browser for full report: $testUrl" -ForegroundColor Cyan
    }
} catch {
    Write-Host "   [FAIL] Comprehensive test page failed: $_" -ForegroundColor Red
    $allPassed = $false
}

Write-Host ""

# Test 6: API Info
Write-Host "6. Testing API Info Endpoint..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/api-info.php" -Method GET -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "   [OK] API info endpoint: SUCCESS" -ForegroundColor Green
        $json = $response.Content | ConvertFrom-Json
        Write-Host "   Available endpoints: $($json.available_endpoints.Count)" -ForegroundColor Cyan
    }
} catch {
    Write-Host "   [FAIL] API info endpoint failed: $_" -ForegroundColor Red
}

Write-Host ""

# Summary
Write-Host "========================================" -ForegroundColor Cyan
if ($allPassed) {
    Write-Host "   [OK] ALL CONNECTION TESTS PASSED" -ForegroundColor Green
} else {
    Write-Host "   [FAIL] SOME CONNECTION TESTS FAILED" -ForegroundColor Red
    Write-Host ""
    Write-Host "   Troubleshooting:" -ForegroundColor Yellow
    Write-Host "   1. Check if XAMPP Apache is running" -ForegroundColor White
    Write-Host "   2. Check if MySQL is running" -ForegroundColor White
    Write-Host "   3. Verify .env file exists in backend/ directory" -ForegroundColor White
    Write-Host "   4. Check database credentials in .env file" -ForegroundColor White
    Write-Host "   5. Ensure database 'myrajourney' exists" -ForegroundColor White
    Write-Host "   6. Run migrations if tables are missing" -ForegroundColor White
}
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "For detailed test results, open in browser:" -ForegroundColor Cyan
Write-Host "  $testUrl" -ForegroundColor Yellow
Write-Host ""
