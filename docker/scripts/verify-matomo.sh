#!/bin/bash

# Verify Matomo installation and tracking

echo "========================================"
echo "🔍 Verifying Matomo Setup"
echo "========================================"
echo ""

# Check if Matomo is responding
echo "📍 Checking Matomo health..."
MATOMO_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8002/)
if [ "$MATOMO_STATUS" = "200" ] || [ "$MATOMO_STATUS" = "301" ] || [ "$MATOMO_STATUS" = "302" ]; then
  echo "✅ Matomo is responding (HTTP $MATOMO_STATUS)"
else
  echo "❌ Matomo is not responding (HTTP $MATOMO_STATUS)"
  echo "   Waiting for Matomo to initialize... this may take a minute"
  exit 1
fi

echo ""
echo "📊 Checking database schema..."

# Check Matomo database tables
TABLES=$(docker-compose exec -T mysql mysql -u root -proot matomo -e "SELECT COUNT(*) as table_count FROM information_schema.TABLES WHERE TABLE_SCHEMA='matomo';" 2>/dev/null | tail -1)
echo "   Matomo tables created: $TABLES"

if [ "$TABLES" -gt 30 ]; then
  echo "✅ Database schema appears complete"
else
  echo "⚠️  Database schema may be incomplete (expected 40+, found $TABLES)"
fi

# Check for key tables
echo ""
echo "🔑 Checking required tables..."
REQUIRED_TABLES=("matomo_log_visit" "matomo_log_action" "matomo_log_conversion" "matomo_site")

for table in "${REQUIRED_TABLES[@]}"; do
  EXISTS=$(docker-compose exec -T mysql mysql -u root -proot matomo -e "SHOW TABLES LIKE '$table';" 2>/dev/null | grep -c "$table")
  if [ "$EXISTS" -eq 1 ]; then
    echo "  ✅ $table"
  else
    echo "  ❌ $table (missing)"
  fi
done

echo ""
echo "📈 Checking tracked events..."
VISIT_COUNT=$(docker-compose exec -T mysql mysql -u root -proot matomo -e "SELECT COUNT(*) FROM matomo_log_visit;" 2>/dev/null | tail -1)
echo "   Visits recorded: $VISIT_COUNT"

ACTION_COUNT=$(docker-compose exec -T mysql mysql -u root -proot matomo -e "SELECT COUNT(*) FROM matomo_log_action;" 2>/dev/null | tail -1)
echo "   Actions recorded: $ACTION_COUNT"

echo ""
echo "========================================"
echo "✨ Verification complete!"
echo "========================================"
echo ""
echo "📋 Next steps:"
echo "  1. Open http://localhost:8002/ in your browser"
echo "  2. Log in with:"
echo "     Username: admin"
echo "     Password: matomo_admin_123"
echo "  3. Perform actions in the app (login, check-in, etc.)"
echo "  4. Check Matomo dashboard for tracked events"
echo ""
