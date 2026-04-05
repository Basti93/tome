# Docker Development Setup for TOME

This guide explains how to use Docker Compose for local development.

## Prerequisites

- Docker & Docker Compose installed
- Node.js 18+ (for building frontend)

## Quick Start

### 1. Clone and Setup
```bash
git clone <repo>
cd tome
```

### 2. Start Docker Services
```bash
docker-compose up -d
```

This starts:
- **nginx** on http://localhost:8000 (reverse proxy)
- **Laravel** backend (internal)
- **MySQL** database (internal)

### 3. Initialize Database

**Option A: Automated (Recommended)**
```bash
bash docker/scripts/init-db.sh
```

**Option B: Manual**
```bash
docker-compose exec laravel php artisan migrate
docker-compose exec laravel php artisan db:seed
docker-compose exec laravel php artisan jwt:secret -f
```

### 4. Access Database Tools

**phpMyAdmin** (Visual database management)
- URL: http://localhost:8001
- User: `tome`
- Password: `tome`

**MySQL CLI**
```bash
docker-compose exec mysql mysql -u tome -ptome tome
# Or as root:
docker-compose exec mysql mysql -u root -proot
```

### 5. Build & Deploy Frontend
```bash
cd app
npm install
npm run build:serve
```

This builds the Vue app and copies it to `server/public/`.

### 6. Access the App
Open http://localhost:8000 in your browser.

## Development Workflow

### Making Changes

**Frontend changes:**
```bash
cd app
npm run build:serve  # Rebuild and deploy
```

**Backend changes:**
- Changes are reflected immediately (volume is mounted)
- For migrations: `docker-compose exec laravel php artisan migrate`

### Useful Commands

**Logs & Debugging**
```bash
# View logs in real-time
docker-compose logs -f laravel
docker-compose logs -f nginx
docker-compose logs -f mysql

# View specific container logs
docker-compose logs mysql --tail=50
```

**Database Management**
```bash
# Access MySQL CLI
docker-compose exec mysql mysql -u tome -ptome tome

# Run Laravel Tinker (REPL)
docker-compose exec laravel php artisan tinker

# Run migrations
docker-compose exec laravel php artisan migrate

# Rollback migrations
docker-compose exec laravel php artisan migrate:rollback

# Seed database
docker-compose exec laravel php artisan db:seed

# Reset database (warning: deletes all data)
docker-compose exec laravel php artisan migrate:refresh --seed

# Backup database
docker-compose exec mysql mysqldump -u tome -ptome tome > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u tome -ptome tome < backup.sql
```

**Testing & CI**
```bash
# Run tests
docker-compose exec laravel phpunit

# Run tests with coverage
docker-compose exec laravel phpunit --coverage-html coverage
```

**Container Management**
```bash
# Stop all services (keeps data)
docker-compose down

# Stop and remove volumes (resets database)
docker-compose down -v

# Rebuild containers
docker-compose build --no-cache

# View running containers
docker-compose ps

# Access Laravel shell
docker-compose exec laravel sh
```

## Environment Configuration

The Docker setup reads from `server/.env`:
- Database: MySQL (via `DB_HOST=mysql`)
- App URL: http://localhost:8000
- Frontend URL: http://localhost:5173 (for CORS)

For production, update these values.

## Troubleshooting

**"Port 8000 already in use"**
```bash
docker-compose down  # Stop existing services
# Or use a different port: docker-compose -p tome up -d
```

**"MySQL can't start"**
```bash
docker-compose down -v  # Remove volumes and restart
docker-compose up -d
```

**Frontend not updating**
```bash
npm run build:serve  # Rebuild and redeploy
```

## Production Deployment

The docker-compose setup is for local development only. For production:

1. Build a production image (add .dockerignore, optimize Dockerfile)
2. Use managed databases instead of container MySQL
3. Configure environment variables properly
4. Use a proper container orchestration platform (Kubernetes, etc.)

See `Dockerfile` and `docker/nginx/default.conf` for configuration.
