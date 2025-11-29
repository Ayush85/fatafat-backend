@echo off
REM Fatafat API Documentation Quick Start
REM This script starts the Laravel development server and opens the documentation in your browser

echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║     Fatafat E-Commerce API - Documentation Quick Start       ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.

REM Check if PHP is available
php -v >nul 2>&1
if errorlevel 1 (
    echo ❌ ERROR: PHP is not installed or not in PATH
    echo Please install PHP or add it to your system PATH
    pause
    exit /b 1
)

echo ✅ PHP found
echo.

REM Check if Laravel is available
php -r "require 'vendor/autoload.php';" >nul 2>&1
if errorlevel 1 (
    echo ⚠️ WARNING: Vendor folder not found. Running composer install...
    call composer install --no-dev
    if errorlevel 1 (
        echo ❌ Composer install failed
        pause
        exit /b 1
    )
)

echo ✅ Laravel project found
echo.

REM Clear Laravel caches
echo 🧹 Clearing Laravel caches...
php artisan config:clear >nul 2>&1
php artisan cache:clear >nul 2>&1
php artisan route:clear >nul 2>&1

echo ✅ Caches cleared
echo.

REM Check if .env file exists
if not exist .env (
    echo ⚠️ .env file not found. Creating from .env.example...
    if exist .env.example (
        copy .env.example .env >nul
        echo ✅ .env created
    ) else (
        echo ❌ ERROR: .env.example not found
        pause
        exit /b 1
    )
)

echo.
echo 🚀 Starting Laravel development server...
echo.
echo 📖 Documentation will be available at: http://localhost:8000/documentation
echo.
echo Press Ctrl+C to stop the server
echo.

php artisan serve --host=127.0.0.1 --port=8000

pause
