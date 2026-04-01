@echo off
echo ========================================
echo Starting PHP Development Server
echo ========================================
echo.
echo Server will start on: http://0.0.0.0:8000
echo Accessible from mobile: http://10.34.163.165:8000
echo.
echo Press Ctrl+C to stop the server
echo ========================================
echo.

cd /d "%~dp0"
php -S 0.0.0.0:8000 -t public

pause
