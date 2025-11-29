@echo off
echo ========================================
echo Fatafat API - Fix and Test
echo ========================================
echo.

echo Step 1: Clearing all caches...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo Cache cleared!
echo.

echo Step 2: Testing configuration...
php artisan config:cache
if %errorlevel% neq 0 (
    echo ERROR: Configuration has errors!
    echo Running without cache...
    php artisan config:clear
)
echo.

echo Step 3: Testing database connection...
php artisan migrate:status
if %errorlevel% neq 0 (
    echo WARNING: Database connection issue
    echo Please check your .env file
) else (
    echo Database connection OK!
)
echo.

echo Step 4: Listing routes...
php artisan route:list --path=api
echo.

echo ========================================
echo Fix Complete!
echo ========================================
echo.
echo To start the server, run:
echo   php artisan serve
echo.
echo Then test with:
echo   curl http://localhost:8000/api/v1/products
echo.
pause
