@echo off
echo ========================================
echo Fatafat API - Setup Verification
echo ========================================
echo.

echo Checking PHP version...
php -v
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH
    pause
    exit /b 1
)
echo.

echo Checking Composer...
call composer --version
if %errorlevel% neq 0 (
    echo ERROR: Composer is not installed or not in PATH
    pause
    exit /b 1
)
echo.

echo Checking if vendor directory exists...
if exist vendor (
    echo [OK] Vendor directory found
) else (
    echo [WARNING] Vendor directory not found. Run: composer install
)
echo.

echo Checking .env file...
if exist .env (
    echo [OK] .env file found
) else (
    echo [WARNING] .env file not found. Copying from .env.example...
    copy .env.example .env
)
echo.

echo Checking APP_KEY...
findstr /C:"APP_KEY=base64:" .env >nul
if %errorlevel% equ 0 (
    echo [OK] APP_KEY is set
) else (
    echo [WARNING] APP_KEY not set. Run: php artisan key:generate
)
echo.

echo Checking storage permissions...
if exist storage (
    echo [OK] Storage directory exists
) else (
    echo [ERROR] Storage directory not found
)
echo.

echo Checking bootstrap/cache...
if exist bootstrap\cache (
    echo [OK] Bootstrap cache directory exists
) else (
    echo [ERROR] Bootstrap cache directory not found
)
echo.

echo ========================================
echo Verification Complete
echo ========================================
echo.
echo Next steps:
echo 1. Run: composer install
echo 2. Run: php artisan key:generate
echo 3. Configure database in .env
echo 4. Run: php artisan serve
echo.
pause
