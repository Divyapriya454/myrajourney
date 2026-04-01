@echo off
echo ========================================
echo EMERGENCY DATABASE FIX
echo ========================================
echo.
echo This will:
echo 1. Stop MySQL
echo 2. Delete corrupted database files
echo 3. Restart MySQL
echo 4. Create fresh database
echo 5. Run migrations
echo.
echo Press Ctrl+C to cancel, or
pause

echo.
echo Step 1: Stopping MySQL...
net stop MySQL
timeout /t 2

echo.
echo Step 2: Deleting corrupted database files...
cd /d "C:\xampp\mysql\data\myrajourney"
del /F /Q *.*
cd ..
rmdir /S /Q myrajourney

echo.
echo Step 3: Starting MySQL...
net start MySQL
timeout /t 3

echo.
echo Step 4: Creating fresh database and running migrations...
cd /d "%~dp0"
php reset-database-fresh.php
php setup-database-complete.php

echo.
echo ========================================
echo Done! Try logging in now.
echo ========================================
pause
