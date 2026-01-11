#!/bin/bash
# Update server from master to main branch

echo "=== Switching from master to main branch ==="
echo ""

# Navigate to the project directory
cd /var/www/apifatafatsewa || exit

# Fetch all branches
echo "Fetching latest branches..."
git fetch origin

# Check current branch
CURRENT_BRANCH=$(git branch --show-current)
echo "Current branch: $CURRENT_BRANCH"

# Switch to main branch
echo ""
echo "Switching to main branch..."
git checkout main

# If main doesn't exist locally, create it from origin/main
if [ $? -ne 0 ]; then
    echo "Creating main branch from origin/main..."
    git checkout -b main origin/main
fi

# Pull latest changes
echo ""
echo "Pulling latest changes from main..."
git pull origin main

# Update dependencies
echo ""
echo "Updating Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Clear and rebuild cache
echo ""
echo "Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo ""
echo "Rebuilding caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Regenerate API documentation
echo ""
echo "Regenerating API documentation..."
mkdir -p public/docs
php artisan scribe:generate

# Wait for generation to complete
sleep 2

# Add security schemes to OpenAPI spec (only if not already present)
echo ""
echo "Adding security schemes to OpenAPI spec..."
if ! grep -q "securitySchemes:" public/docs/openapi.yaml; then
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
    echo "Security schemes added"
else
    echo "Security schemes already present"
fi

# Set proper permissions for docs
chown -R www-data:www-data public/docs
chmod -R 755 public/docs

echo ""
echo "=== Update Complete! ==="
echo ""
echo "Current branch: $(git branch --show-current)"
echo "Latest commit: $(git log -1 --oneline)"
echo ""
echo "Test the API:"
echo "  curl https://api.fatafatsewa.com/health"
echo "  curl https://api.fatafatsewa.com/documentation"
