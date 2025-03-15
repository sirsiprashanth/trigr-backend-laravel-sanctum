#!/bin/bash
# Laravel Vite Deployment Script

echo "🚀 Starting deployment process..."

# Install dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "📦 Installing Node.js dependencies..."
npm ci

# Build frontend assets
echo "🔨 Building frontend assets..."
npm run build

# Clear Laravel caches
echo "🧹 Clearing Laravel caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure storage symlink exists
echo "🔗 Creating storage symlink..."
php artisan storage:link

echo "✅ Deployment completed!"
echo "Remember to set APP_ENV=production and APP_DEBUG=false in your .env file" 