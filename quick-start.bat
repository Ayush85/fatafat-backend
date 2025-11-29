@echo off
echo ========================================
echo Fatafat API - Quick Start Script
echo ========================================
echo.
echo IMPORTANT: This script will install all dependencies
echo and set up your Laravel API project.
echo.
echo Prerequisites:
echo - PHP 8.0 or higher
echo - Composer
echo - MySQL database (fatafatnew)
echo.
pause
echo.

echo Step 1: Installing Composer dependencies...
echo This may take a few minutes...
call composer install
if %errorlevel% neq 0 (
    echo ERROR: Composer install failed!
    pause
    exit /b 1
)
echo.

echo Step 2: Generating application key...
php artisan key:generate
echo.

echo Step 3: Checking database connection...
php artisan migrate:status
if %errorlevel% neq 0 (
    echo WARNING: Database connection issue. Please check your .env file.
    echo.
)

echo Step 4: Installing Scribe for API documentation...
call composer require --dev knuckleswtf/scribe
echo.

echo Step 5: Generating API documentation...
php artisan scribe:generate
echo.

echo ========================================
echo Setup Complete!
echo ========================================
echo.
echo Your API is ready to use!
echo.
echo To start the development server, run:
echo   php artisan serve
echo.
echo Then visit:
echo   - API: http://localhost:8000/api/v1
echo   - Docs: http://localhost:8000/docs
echo.
echo For testing, import POSTMAN_COLLECTION.json into Postman
echo or refer to API_DOCUMENTATION.md
echo.
pause
