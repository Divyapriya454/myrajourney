@echo off
echo ================================================================
echo XAMPP Integration Setup
echo ================================================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo WARNING: Not running as administrator
    echo Some operations may fail without admin rights
    echo.
)

echo Step 1: Checking XAMPP installation...
if exist "C:\xampp\xampp-control.exe" (
    echo [OK] XAMPP found at C:\xampp
) else (
    echo [ERROR] XAMPP not found at C:\xampp
    echo Please install XAMPP first from https://www.apachefriends.org/
    pause
    exit /b 1
)
echo.

echo Step 2: Checking if Apache and MySQL are running...
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] Apache is running
) else (
    echo [WARNING] Apache is not running
    echo Please start Apache from XAMPP Control Panel
)

tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MySQL is running
) else (
    echo [WARNING] MySQL is not running
    echo Please start MySQL from XAMPP Control Panel
)
echo.

echo Step 3: Setting up backend in htdocs...
set "BACKEND_SOURCE=%~dp0"
set "HTDOCS_TARGET=C:\xampp\htdocs\myrajourney"

if exist "%HTDOCS_TARGET%" (
    echo [INFO] Folder already exists in htdocs
    echo Checking if it's the same folder...
    
    REM Simple check - compare a file
    fc /b "%BACKEND_SOURCE%.env" "%HTDOCS_TARGET%\.env" >nul 2>&1
    if errorlevel 1 (
        echo [WARNING] Different folders detected
        echo.
        echo Choose an option:
        echo 1. Delete existing and create new symlink
        echo 2. Keep existing (manual sync required)
        echo 3. Cancel
        choice /C 123 /N /M "Enter choice (1-3): "
        
        if errorlevel 3 goto :skip_copy
        if errorlevel 2 goto :skip_copy
        if errorlevel 1 (
            echo Removing existing folder...
            rmdir /s /q "%HTDOCS_TARGET%"
        )
    ) else (
        echo [OK] Same folder or already synced
        goto :skip_copy
    )
)

echo Creating symbolic link...
mklink /D "%HTDOCS_TARGET%" "%BACKEND_SOURCE%" >nul 2>&1
if %errorLevel% equ 0 (
    echo [OK] Symbolic link created successfully
) else (
    echo [WARNING] Could not create symbolic link
    echo Copying files instead...
    xcopy /E /I /Y "%BACKEND_SOURCE%" "%HTDOCS_TARGET%"
    if %errorLevel% equ 0 (
        echo [OK] Files copied successfully
        echo [NOTE] You'll need to manually sync changes
    ) else (
        echo [ERROR] Failed to copy files
    )
)

:skip_copy
echo.

echo Step 4: Testing PHP installation...
php -v >nul 2>&1
if %errorLevel% equ 0 (
    echo [OK] PHP is available in PATH
    php -v | findstr /C:"PHP"
) else (
    echo [WARNING] PHP not in PATH
    echo Using XAMPP PHP...
    set "PATH=C:\xampp\php;%PATH%"
)
echo.

echo Step 5: Running integration test...
echo.
php "%~dp0test-xampp-integration.php"
echo.

echo ================================================================
echo Setup Complete!
echo ================================================================
echo.
echo Next Steps:
echo 1. Open XAMPP Control Panel
echo 2. Ensure Apache and MySQL are running (green indicators)
echo 3. Open browser: http://localhost/myrajourney/test-xampp-integration.php
echo 4. If database doesn't exist, run: setup-fresh-database.php
echo 5. Update Android app IP in network_config.xml if needed
echo.
echo Press any key to open XAMPP Control Panel...
pause >nul
start "" "C:\xampp\xampp-control.exe"
