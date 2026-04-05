#!/bin/bash
# Database initialization script for Docker

set -e

echo "Waiting for MySQL to be ready..."
until docker-compose exec -T mysql mysqladmin ping -h localhost > /dev/null 2>&1; do
  printf '.'
  sleep 1
done

echo ""
echo "Waiting for Laravel to be ready..."
until docker-compose exec -T laravel php -v > /dev/null 2>&1; do
  printf '.'
  sleep 1
done

echo ""
echo "Running Laravel migrations..."
docker-compose exec -T laravel php artisan migrate --force

echo "Seeding database..."
docker-compose exec -T laravel php artisan db:seed

echo "Generating JWT secret..."
docker-compose exec -T laravel php artisan jwt:secret -f

echo ""
echo "✅ Database initialized successfully!"
echo ""
echo "phpMyAdmin: http://localhost:8001"
echo "  User: tome"
echo "  Password: tome"
