.PHONY: help docker-up docker-down docker-init docker-logs db-migrate db-seed db-reset db-backup frontend-build

help:
	@echo "TOME Development Commands"
	@echo ""
	@echo "Docker:"
	@echo "  make docker-up              Start Docker containers"
	@echo "  make docker-down            Stop Docker containers"
	@echo "  make docker-rebuild         Rebuild and restart after code changes"
	@echo "  make docker-rebuild-fresh   Clean rebuild with fresh database"
	@echo "  make docker-init            Initialize database and setup"
	@echo "  make docker-logs            View Docker logs"
	@echo ""
	@echo "Database:"
	@echo "  make db-migrate        Run migrations"
	@echo "  make db-seed           Seed database"
	@echo "  make db-reset          Reset database (WARNING: deletes data)"
	@echo "  make db-backup         Backup database to SQL file"
	@echo ""
	@echo "Frontend:"
	@echo "  make frontend-build    Build and deploy frontend"
	@echo "  make frontend-dev      Start Vite dev server"
	@echo ""
	@echo "Other:"
	@echo "  make shell             Access Laravel shell"

docker-up:
	docker-compose up -d
	@echo "✅ Docker services started"
	@echo "   App:       http://localhost:8000"
	@echo "   phpMyAdmin: http://localhost:8001"

docker-down:
	docker-compose down
	@echo "✅ Docker services stopped"

docker-rebuild:
	@echo "🔄 Rebuilding Docker containers..."
	docker-compose down
	docker-compose build laravel
	docker-compose up -d
	@echo "✅ Docker rebuilt and started"
	@echo "   App:       http://localhost:8000"

docker-rebuild-fresh:
	@echo "🔄 Rebuilding Docker containers (fresh - no cache)..."
	docker-compose down -v
	docker-compose build --no-cache laravel
	docker-compose up -d
	make docker-init
	@echo "✅ Docker rebuilt fresh and initialized"

docker-init:
	bash docker/scripts/init-db.sh

docker-logs:
	docker-compose logs -f

db-migrate:
	docker-compose exec laravel php artisan migrate

db-seed:
	docker-compose exec laravel php artisan db:seed

db-reset:
	@echo "⚠️  This will DELETE all database data!"
	@read -p "Continue? (y/N) " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose exec laravel php artisan migrate:refresh --seed; \
		@echo "✅ Database reset"; \
	fi

db-backup:
	docker-compose exec mysql mysqldump -u tome -ptome tome > backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "✅ Database backed up"

frontend-build:
	cd app && npm run build:serve
	docker-compose restart nginx
	@echo "✅ Frontend built, deployed, and nginx restarted"

frontend-dev:
	cd app && npm install && npm run dev

shell:
	docker-compose exec laravel sh

.DEFAULT_GOAL := help
