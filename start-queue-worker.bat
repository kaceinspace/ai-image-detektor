@echo off
REM Laravel Queue Worker Starter
REM This script starts the Laravel queue worker

cd /d "%~dp0"

echo Starting Laravel Queue Worker...
echo.
echo Press Ctrl+C to stop the worker
echo.

php artisan queue:work --tries=3 --timeout=120

pause
