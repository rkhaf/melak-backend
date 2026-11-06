#!/bin/bash
# Exit immediately if a command exits with a non-zero status
set -e

# Load global environment paths so non-interactive SSH can find docker, git, etc.
export PATH=$PATH:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin

echo "🚀 Starting Auto-Deployment Process for Kaliwedi (Dockerized Laravel)..."

# 1. Pull the latest code from Git
echo "📥 1. Fetching and resetting to latest code from GitHub..."
git fetch --all
git reset --hard origin/main

# 2. Rebuild Docker Image & Recreate Container using local docker-compose
echo "🐳 2. Rebuilding Docker Image and Restarting Container..."

# Safely stop and remove any old manual container to avoid name conflicts
docker stop kaliwedi-web || true
docker rm -f kaliwedi-web || true

# Check if modern 'docker compose' is supported, otherwise fallback to legacy 'docker-compose'
if docker compose version >/dev/null 2>&1; then
    echo "Detected: docker compose"
    docker compose up -d --build
else
    echo "Detected: docker-compose"
    docker-compose up -d --build
fi

# 3. Wait for the container to initialize
echo "⏳ 3. Waiting for container to stabilize..."
sleep 5

# 4. Run database migrations inside the newly built running container
echo "🗄️ 4. Running database migrations inside the container..."
docker exec -t kaliwedi-web php artisan migrate --force

# 5. Clear and optimize Laravel caches inside the container
echo "🧹 5. Optimizing and clearing Laravel caches inside the container..."
docker exec -t kaliwedi-web php artisan optimize:clear
docker exec -t kaliwedi-web php artisan config:cache
docker exec -t kaliwedi-web php artisan route:cache
docker exec -t kaliwedi-web php artisan view:cache

echo "🎉 Auto-Deployment completed successfully! Kaliwedi is live."
