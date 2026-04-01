@echo off
echo ========================================
echo MYRA Journey - System Check
echo ========================================
echo.

echo [1/4] Checking Database Connection...
php test-database-connection.php | findstr "Current Database" | findstr "myrajourney_new"
if %ERRORLEVEL% EQU 0 (
    echo ✅ Database: myrajourney_new
) else (
    echo ❌ Database connection failed
)
echo.

echo [2/4] Checking Password...
php test-login-direct.php | findstr "LOGIN SHOULD WORK"
if %ERRORLEVEL% EQU 0 (
    echo ✅ Password verification works
) else (
    echo ❌ Password verification failed
)
echo.

echo [3/4] Checking API Endpoint...
php test-api-endpoint.php | findstr "LOGIN SUCCESSFUL"
if %ERRORLEVEL% EQU 0 (
    echo ✅ API endpoint works
) else (
    echo ❌ API endpoint failed
)
echo.

echo [4/4] Checking Network Access...
php test-network-access.php | findstr "NETWORK ACCESS WORKS"
if %ERRORLEVEL% EQU 0 (
    echo ✅ Network access works
) else (
    echo ❌ Network access failed
)
echo.

echo ========================================
echo System Check Complete
echo ========================================
echo.
echo If all checks passed, login should work!
echo.
echo Credentials:
echo Email: deepankumar@gmail.com
echo Password: Welcome@456
echo.
pause
