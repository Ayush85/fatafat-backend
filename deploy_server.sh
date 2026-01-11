#!/bin/bash
# Server Deployment Script - Run this on the server (103.163.182.59)

echo "=== Fatafatsewa API Server Deployment ==="
echo ""

# Navigate to web directory
cd /var/www || exit

# Step 1: Remove large zip files
echo "Step 1: Cleaning up zip files..."
rm -f *.zip
echo "Removed zip files"

# Step 2: Backup existing apifatafatsewa folder
echo ""
echo "Step 2: Backing up existing apifatafatsewa folder..."
if [ -d "apifatafatsewa" ]; then
    BACKUP_NAME="apifatafatsewa_backup_$(date +%Y%m%d_%H%M%S)"
    mv apifatafatsewa "$BACKUP_NAME"
    echo "Backed up to: $BACKUP_NAME"
fi

# Step 3: Create fresh directory
echo ""
echo "Step 3: Creating fresh apifatafatsewa directory..."
mkdir -p apifatafatsewa
cd apifatafatsewa || exit

# Step 4: Clone repository
echo ""
echo "Step 4: Cloning repository from GitHub..."
git clone git@github.com:sarbatrainc/Fatafatsewa.git .

# Step 5: Install Composer dependencies
echo ""
echo "Step 5: Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Step 6: Set up environment file
echo ""
echo "Step 6: Setting up environment..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env file - YOU MUST EDIT THIS WITH PRODUCTION VALUES!"
    echo "Important variables to set:"
    echo "  - APP_URL=https://api.fatafatsewa.com"
    echo "  - MEDIA_URL=https://fatafatsewa.com/storage"
    echo "  - ASSET_URL=https://fatafatsewa.com"
    echo "  - DB_* (database credentials)"
    echo "  - API_KEY (production API key)"
fi

# Step 7: Generate application key
echo ""
echo "Step 7: Generating application key..."
php artisan key:generate

# Step 8: Create storage link
echo ""
echo "Step 8: Creating storage symbolic link..."
php artisan storage:link

# Step 9: Set proper permissions
echo ""
echo "Step 9: Setting permissions..."
chown -R www-data:www-data /var/www/apifatafatsewa
chmod -R 755 /var/www/apifatafatsewa
chmod -R 775 storage bootstrap/cache

# Step 10: Cache configuration
echo ""
echo "Step 10: Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 11: Generate API documentation
echo ""
echo "Step 11: Generating API documentation..."
php artisan scribe:generate

# Add security schemes to OpenAPI spec
cat >> public/docs/openapi.yaml << 'YAML'
components:
  securitySchemes:
    ApiKeyAuth:
      type: apiKey
      in: header
      name: API-Key
      description: 'API Key for public endpoints'
    BearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
      description: 'Bearer token for authenticated endpoints'
security:
  - ApiKeyAuth: []
  - BearerAuth: []
YAML

echo ""
echo "=== Deployment Complete! ==="
echo ""
echo "Next steps:"
echo "1. Edit .env file with production values"
echo "2. Test API: curl https://api.fatafatsewa.com/health"
echo "3. Visit documentation: https://api.fatafatsewa.com/documentation"
echo ""
echo "IMPORTANT: Don't forget to configure your web server (Nginx/Apache) to point to /var/www/apifatafatsewa/public"
