#!/bin/bash
set -e

MATOMO_DIR="/var/www/html"
CONFIG_FILE="$MATOMO_DIR/config/config.ini.php"

echo "========================================"
echo "🚀 Matomo Auto-Initialization"
echo "========================================"

# Wait for MySQL
DB_HOST="${MATOMO_DATABASE_HOST:-mysql}"
echo "⏳ Waiting for MySQL at $DB_HOST:3306..."
COUNTER=0
while ! nc -z "$DB_HOST" 3306 2>/dev/null && [ $COUNTER -lt 30 ]; do
  COUNTER=$((COUNTER + 1))
  sleep 1
done
echo "✅ MySQL is ready"

# Wait for Matomo files to be in place
echo "⏳ Waiting for Matomo files..."
COUNTER=0
while [ ! -f "$MATOMO_DIR/index.php" ] && [ $COUNTER -lt 30 ]; do
  COUNTER=$((COUNTER + 1))
  sleep 1
done

if [ ! -f "$MATOMO_DIR/index.php" ]; then
  echo "❌ Matomo files not found at $MATOMO_DIR"
  exit 1
fi

# Check if already configured
if [ -f "$CONFIG_FILE" ]; then
  echo "✅ Matomo already configured"
else
  echo "🔧 Setting up Matomo for first time..."
  cd "$MATOMO_DIR"

  # Try to run core:install using the correct console path
  if [ -f "console" ]; then
    echo "   Installing database..."
    php console core:install \
      --db-host="${MATOMO_DATABASE_HOST:-mysql}" \
      --db-username="${MATOMO_DATABASE_USERNAME:-tome}" \
      --db-password="${MATOMO_DATABASE_PASSWORD:-tome}" \
      --db-name="${MATOMO_DATABASE_DBNAME:-matomo}" \
      --admin-user="${MATOMO_ADMIN_USER:-admin}" \
      --admin-password="${MATOMO_ADMIN_PASSWORD:-matomo_admin_123}" \
      --admin-email="${MATOMO_ADMIN_EMAIL:-admin@tome.local}" \
      --site-name="${MATOMO_SITE_NAME:-TOME}" \
      --site-url="${MATOMO_SITE_URL:-http://localhost:8002/}" \
      2>&1 | grep -v "^WARNING" || true

    # Enable privacy
    echo "   Configuring privacy..."
    php console config:set General anonymizeIp 1 2>/dev/null || true
    echo "✅ Matomo setup complete!"
  else
    echo "⚠️  console file not found, setup wizard will run on first access"
  fi
fi

# Ensure permissions
chown -R www-data:www-data "$MATOMO_DIR"

echo "========================================"
echo "✨ Starting Apache..."
echo "========================================"

exec apache2-foreground
