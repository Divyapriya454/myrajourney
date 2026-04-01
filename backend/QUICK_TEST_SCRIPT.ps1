# MyRA Journey Backend Testing Script
# Copy and paste this entire script into PowerShell

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   MyRA Journey Backend Testing" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Step 1: Test Public Endpoint
Write-Host "Step 1: Testing Public Endpoint (No Auth Required)" -ForegroundColor Yellow
Write-Host "---------------------------------------------------" -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost/backend/public/api/v1/education/articles" -Method GET
    Write-Host "✅ SUCCESS! Public endpoint works!" -ForegroundColor Green
    Write-Host "Response:" -ForegroundColor Cyan
    $response.Content | ConvertFrom-Json | ConvertTo-Json -Depth 10
} catch {
    Write-Host "❌ FAILED: $_" -ForegroundColor Red
    Write-Host "`nPlease check:" -ForegroundColor Yellow
    Write-Host "1. XAMPP Apache is running" -ForegroundColor White
    Write-Host "2. URL is correct" -ForegroundColor White
    exit
}

Write-Host "`n"

# Step 2: Register a New User
Write-Host "Step 2: Testing User Registration" -ForegroundColor Yellow
Write-Host "---------------------------------" -ForegroundColor Yellow
$randomEmail = "testuser" + (Get-Random -Minimum 1000 -Maximum 9999) + "@test.com"
Write-Host "Registering user: $randomEmail" -ForegroundColor Cyan

$registerBody = @{
    email = $randomEmail
    password = "test123456"
    role = "PATIENT"
    name = "Test User"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "http://localhost/backend/public/api/v1/auth/register" `
        -Method POST `
        -Body $registerBody `
        -ContentType "application/json"
    
    Write-Host "✅ Registration SUCCESS!" -ForegroundColor Green
    $registerJson = $response.Content | ConvertFrom-Json
    
    Write-Host "User ID: $($registerJson.data.user.id)" -ForegroundColor Yellow
    Write-Host "User Email: $($registerJson.data.user.email)" -ForegroundColor Yellow
    Write-Host "User Role: $($registerJson.data.user.role)" -ForegroundColor Yellow
    
    # Save token
    $token = "Bearer " + $registerJson.data.token
    Write-Host "Token saved!" -ForegroundColor Green
    Write-Host "Token (first 50 chars): $($token.Substring(0, [Math]::Min(50, $token.Length)))..." -ForegroundColor Gray
    
} catch {
    Write-Host "❌ Registration FAILED: $_" -ForegroundColor Red
    $token = $null
}

Write-Host "`n"

# Step 3: Test Login
Write-Host "Step 3: Testing User Login" -ForegroundColor Yellow
Write-Host "--------------------------" -ForegroundColor Yellow
Write-Host "Using email: $randomEmail" -ForegroundColor Cyan

$loginBody = @{
    email = $randomEmail
    password = "test123456"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "http://localhost/backend/public/api/v1/auth/login" `
        -Method POST `
        -Body $loginBody `
        -ContentType "application/json"
    
    Write-Host "✅ Login SUCCESS!" -ForegroundColor Green
    $loginJson = $response.Content | ConvertFrom-Json
    
    # Update token from login
    $token = "Bearer " + $loginJson.data.token
    Write-Host "Token received from login!" -ForegroundColor Green
    
} catch {
    Write-Host "❌ Login FAILED: $_" -ForegroundColor Red
    if ($response) {
        Write-Host "Response: $($response.Content)" -ForegroundColor Red
    }
}

Write-Host "`n"

# Step 4: Test Protected Endpoints
if ($token) {
    Write-Host "Step 4: Testing Protected Endpoints (With Token)" -ForegroundColor Yellow
    Write-Host "------------------------------------------------" -ForegroundColor Yellow
    
    # Create headers with token
    $headers = @{
        "Authorization" = $token
        "Content-Type" = "application/json"
    }
    
    # Test 1: Get Current User
    Write-Host "`n4.1 Testing /auth/me..." -ForegroundColor Cyan
    try {
        $response = Invoke-WebRequest -Uri "http://localhost/backend/public/api/v1/auth/me" `
            -Method GET `
            -Headers $headers
        
        Write-Host "✅ SUCCESS!" -ForegroundColor Green
        $json = $response.Content | ConvertFrom-Json
        Write-Host "Current User: $($json.data.user.email) - $($json.data.user.role)" -ForegroundColor Yellow
    } catch {
        Write-Host "❌ FAILED: $_" -ForegroundColor Red
        if ($_.Exception.Response) {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $responseBody = $reader.ReadToEnd()
            Write-Host "Error Response: $responseBody" -ForegroundColor Red
        }
    }
    
    # Test 2: Get Patient Overview
    Write-Host "`n4.2 Testing /patients/me/overview..." -ForegroundColor Cyan
    try {
        $response = Invoke-WebRequest -Uri "http://localhost/backend/public/api/v1/patients/me/overview" `
            -Method GET `
            -Headers $headers
        
        Write-Host "✅ SUCCESS!" -ForegroundColor Green
        $json = $response.Content | ConvertFrom-Json
        Write-Host "Unread Notifications: $($json.data.unreadNotifications)" -ForegroundColor Yellow
        Write-Host "Recent Reports: $($json.data.recentReports.Count)" -ForegroundColor Yellow
    } catch {
        Write-Host "❌ FAILED: $_" -ForegroundColor Red
    }
    
    # Test 3: Get Appointments
    Write-Host "`n4.3 Testing /appointments..." -ForegroundColor Cyan
    try {
        $response = Invoke-WebRequest -Uri "http://localhost/backend/public/api/v1/appointments" `
            -Method GET `
            -Headers $headers
        
        Write-Host "✅ SUCCESS!" -ForegroundColor Green
        $json = $response.Content | ConvertFrom-Json
        Write-Host "Total Appointments: $($json.data.Count)" -ForegroundColor Yellow
    } catch {
        Write-Host "❌ FAILED: $_" -ForegroundColor Red
    }
    
    # Test 4: Get Medications
    Write-Host "`n4.4 Testing /patient-medications..." -ForegroundColor Cyan
    try {
        $response = Invoke-WebRequest -Uri "http://localhost/backend/public/api/v1/patient-medications" `
            -Method GET `
            -Headers $headers
        
        Write-Host "✅ SUCCESS!" -ForegroundColor Green
        $json = $response.Content | ConvertFrom-Json
        Write-Host "Total Medications: $($json.data.Count)" -ForegroundColor Yellow
    } catch {
        Write-Host "❌ FAILED: $_" -ForegroundColor Red
    }
    
    # Test 5: Get Symptoms
    Write-Host "`n4.5 Testing /symptoms..." -ForegroundColor Cyan
    try {
        $response = Invoke-WebRequest -Uri "http://localhost/backend/public/api/v1/symptoms" `
            -Method GET `
            -Headers $headers
        
        Write-Host "✅ SUCCESS!" -ForegroundColor Green
        $json = $response.Content | ConvertFrom-Json
        Write-Host "Total Symptoms Logs: $($json.data.Count)" -ForegroundColor Yellow
    } catch {
        Write-Host "❌ FAILED: $_" -ForegroundColor Red
    }
    
} else {
    Write-Host "⚠️ No token available. Skipping protected endpoint tests." -ForegroundColor Yellow
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   Testing Complete!" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

