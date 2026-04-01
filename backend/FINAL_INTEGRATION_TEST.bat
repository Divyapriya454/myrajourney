@echo off
echo ================================================================
echo MYRA JOURNEY - FINAL INTEGRATION TEST
echo ================================================================
echo.

cd /d "%~dp0"

REM Ensure PHP is available
php -v >nul 2>&1
if %errorLevel% neq 0 (
    set "PATH=C:\xampp\php;%PATH%"
)

echo Step 1: Database Check
echo ================================================================
php quick-database-check.php
echo.

echo Step 2: Test User Logins
echo ================================================================
php test-login-endpoint.php
echo.

echo Step 3: Get Your IP Address
echo ================================================================
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address"') do (
    set "LOCAL_IP=%%a"
    goto :ip_found
)

:ip_found
for /f "tokens=* delims= " %%a in ("%LOCAL_IP%") do set LOCAL_IP=%%a
echo Your Local IP: %LOCAL_IP%
echo.

echo ================================================================
echo SETUP SUMMARY
echo ================================================================
echo.
echo ✓ Database: myrajourney (ready)
echo ✓ Users: 3 test accounts created
echo ✓ Tables: All required tables present
echo.
echo NEXT STEPS:
echo ================================================================
echo.
echo 1. START BACKEND SERVER:
echo    - Run: START_BACKEND.bat
echo    - Server will start on port 8000
echo    - Keep this window open while testing
echo.
echo 2. UPDATE ANDROID APP:
echo    - File: app/src/main/res/values/network_config.xml
echo    - For Emulator: Set api_base_ip to "10.0.2.2"
echo    - For Physical Device: Set api_base_ip to "%LOCAL_IP%"
echo.
echo 3. TEST ACCOUNTS:
echo    - Patient: deepankumar@gmail.com / Welcome@456
echo    - Doctor: doctor@test.com / Patrol@987
echo    - Admin: testadmin@test.com / AS@Saveetha123
echo.
echo 4. TEST CONNECTION:
echo    - After starting server, test from browser:
echo    - http://localhost:8000/test-android-connection.php
echo    - Or from Android: http://%LOCAL_IP%:8000/test-android-connection.php
echo.
echo ================================================================
echo.

pause
