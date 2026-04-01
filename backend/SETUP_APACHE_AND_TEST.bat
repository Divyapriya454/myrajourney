@echo off
echo ================================================================
echo SETUP APACHE AND TEST ALL FEATURES
echo ================================================================
echo.

echo Step 1: Checking if backend is in htdocs...
if exist "C:\xampp\htdocs\myrajourney\backend" (
    echo [OK] Backend found in htdocs
) else (
    echo [ERROR] Backend not found in htdocs
    echo Please copy the backend folder to C:\xampp\htdocs\myrajourney\
    pause
    exit /b 1
)

echo.
echo Step 2: Creating .htaccess file...
echo RewriteEngine On > C:\xampp\htdocs\myrajourney\backend\public\.htaccess
echo RewriteCond %%{HTTP:Authorization} . >> C:\xampp\htdocs\myrajourney\backend\public\.htaccess
echo RewriteRule .* - [E=HTTP_AUTHORIZATION:%%{HTTP:Authorization}] >> C:\xampp\htdocs\myrajourney\backend\public\.htaccess
echo RewriteCond %%{REQUEST_FILENAME} !-d >> C:\xampp\htdocs\myrajourney\backend\public\.htaccess
echo RewriteCond %%{REQUEST_URI} (.+)/$ >> C:\xampp\htdocs\myrajourney\backend\public\.htaccess
echo RewriteRule ^ %%1 [L,R=301] >> C:\xampp\htdocs\myrajourney\backend\public\.htaccess
echo RewriteCond %%{REQUEST_FILENAME} !-f >> C:\xampp\htdocs\myrajourney\backend\public\.htaccess
echo RewriteCond %%{REQUEST_FILENAME} !-d >> C:\xampp\htdocs\myrajourney\backend\public\.htaccess
echo RewriteRule ^ index.php [L] >> C:\xampp\htdocs\myrajourney\backend\public\.htaccess
echo [OK] .htaccess created

echo.
echo Step 3: Testing GD extension with Apache...
echo Please make sure Apache is running in XAMPP Control Panel
echo.
pause

echo.
echo Testing GD extension...
curl -s http://localhost/myrajourney/backend/public/test_gd.php
echo.

echo.
echo Step 4: Running comprehensive tests...
cd C:\xampp\htdocs\myrajourney\backend
php FINAL_COMPREHENSIVE_TEST.php

echo.
echo ================================================================
echo SETUP COMPLETE
echo ================================================================
echo.
echo Next steps:
echo 1. Update Android app network_config.xml to:
echo    http://192.168.29.162/myrajourney/backend/public/api/v1
echo 2. Test rehab assignment from Android app
echo 3. Test report processing from Android app
echo.
pause
