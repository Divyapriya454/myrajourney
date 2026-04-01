@echo off
echo ================================================================
echo MONITORING APACHE REQUESTS IN REAL-TIME
echo ================================================================
echo.
echo Watching for requests to the backend...
echo Press Ctrl+C to stop
echo.
echo ================================================================
echo.

powershell -Command "Get-Content 'C:\xampp\apache\logs\access.log' -Wait -Tail 0 | Where-Object { $_ -match 'myrajourney' } | ForEach-Object { Write-Host $_ -ForegroundColor Green }"
