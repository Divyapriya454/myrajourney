@echo off
echo ================================================================
echo MYRA JOURNEY BACKEND SERVER
echo ================================================================
echo.

REM Get the current directory
set "BACKEND_DIR=%~dp0"
cd /d "%BACKEND_DIR%"

echo Backend Directory: %BACKEND_DIR%
echo.

REM Check if PHP is available
php -v >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] PHP not found in PATH
    echo.
    echo Trying XAMPP PHP...
    set "PATH=C:\xampp\php;%PATH%"
    
    php -v >nul 2>&1
    if %errorLevel% neq 0 (
        echo [ERROR] PHP still not found
        echo Please install PHP or XAMPP
        pause
        exit /b 1
    )
)

echo [OK] PHP Version:
php -v | findstr /C:"PHP"
echo.

REM Get local IP address
echo Detecting your local IP address...
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address"') do (
    set "LOCAL_IP=%%a"
    goto :ip_found
)

:ip_found
REM Trim spaces
for /f "tokens=* delims= " %%a in ("%LOCAL_IP%") do set LOCAL_IP=%%a

echo Your Local IP: %LOCAL_IP%
echo.

echo ================================================================
echo IMPORTANT: Update Android App Configuration
echo ================================================================
echo.
echo For Android Emulator:
echo   - Use IP: 10.0.2.2
echo   - Update: app/src/main/res/values/network_config.xml
echo   - Set api_base_ip to: 10.0.2.2
echo.
echo For Physical Device:
echo   - Use IP: %LOCAL_IP%
echo   - Update: app/src/main/res/values/network_config.xml
echo   - Set api_base_ip to: %LOCAL_IP%
echo   - Make sure device is on same WiFi network
echo.
echo ================================================================
echo.

echo Starting PHP Development Server...
echo Server will be accessible at:
echo   - Local: http://localhost:8000
echo   - Network: http://%LOCAL_IP%:8000
echo   - API Base: http://%LOCAL_IP%:8000/api/v1/
echo.
echo Press Ctrl+C to stop the server
echo ================================================================
echo.

REM Start the PHP server
php -S 0.0.0.0:8000 -t public

pause
