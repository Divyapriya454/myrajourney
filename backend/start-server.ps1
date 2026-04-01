# MYRA Journey Backend Server Startup Script
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Starting PHP Development Server" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Server URL (Local):  http://localhost:8000" -ForegroundColor Green
Write-Host "Server URL (Mobile): http://10.34.163.165:8000" -ForegroundColor Green
Write-Host ""
Write-Host "Document Root: public/" -ForegroundColor Yellow
Write-Host ""
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Red
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Start PHP server
php -S 0.0.0.0:8000 -t public
