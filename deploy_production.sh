#!/bin/bash

# Production Server Deployment & Troubleshooting Script

echo "========================================="
echo "Fatafat Sewa API - Production Deployment"
echo "========================================="
echo ""

# Server details
SERVER="root@103.163.182.59"
APP_DIR="/var/www/apifatafatsewa"

echo "Step 1: Pull latest code from GitHub"
ssh $SERVER "cd $APP_DIR && git pull origin main"

echo ""
echo "Step 2: Install/Update Composer dependencies"
ssh $SERVER "cd $APP_DIR && composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-dom --ignore-platform-req=ext-xml --ignore-platform-req=ext-curl"

echo ""
echo "Step 3: Clear all caches"
ssh $SERVER "cd $APP_DIR && php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear"

echo ""
echo "Step 4: Rebuild caches"
ssh $SERVER "cd $APP_DIR && php artisan config:cache && php artisan route:cache"

echo ""
echo "Step 5: Regenerate API documentation"
ssh $SERVER "cd $APP_DIR && php artisan scribe:generate"

echo ""
echo "Step 6: Set proper permissions"
ssh $SERVER "cd $APP_DIR && chown -R www-data:www-data . && chmod -R 755 . && chmod -R 775 storage bootstrap/cache"

echo ""
echo "Step 7: Check Laravel logs for errors"
echo "Last 20 lines of Laravel log:"
ssh $SERVER "tail -20 $APP_DIR/storage/logs/laravel.log 2>/dev/null || echo 'No log file found'"

echo ""
echo "Step 8: Test registration endpoint"
echo "Testing registration..."
RESPONSE=$(curl -s -X POST -H "Content-Type: application/json" \
  -d '{"name":"Deploy Test","email":"deploytest'$(date +%s)'@example.com","password":"password123","password_confirmation":"password123","contact_number":"9841234567"}' \
  "https://api.fatafatsewa.com/api/v1/register")

echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"

echo ""
echo "========================================="
echo "Deployment Complete!"
echo "========================================="
