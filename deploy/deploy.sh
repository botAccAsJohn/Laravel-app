#!/bin/bash
set -e

echo "🚀 Starting Deployment..."

# 1. Enter maintenance mode
php artisan down || true

# 2. Update code
git pull origin main

# 3. Install composer dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 4. Run database migrations
php artisan migrate --force

# 5. Clear and rebuild cache configs
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Install npm dependencies and build frontend assets
npm ci
npm run build

# 7. Gracefully restart queue workers (Exercise 46.6 Task)
# This writes a cache key telling all active worker processes to finish their current job and exit.
# Supervisor will immediately restart them with the new codebase loaded in memory.
echo "🔄 Gracefully restarting queue workers..."
php artisan queue:restart

# 8. Exit maintenance mode
php artisan up

echo "✅ Deployment completed successfully!"
