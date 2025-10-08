# Developer Quickstart Guide

**Project**: Game Server Promotion Platform
**Tech Stack**: Nuxt.js 3 (Frontend) + CodeIgniter 4 (Backend) + MySQL 8+ + Docker

---

## Prerequisites

Before you begin, ensure you have the following installed:

- **Docker** (20.10+) and **Docker Compose** (2.0+)
- **Node.js** (18 LTS+) and **npm** (9+)
- **PHP** (8.1+) with extensions: mysqli, pdo, gd, curl, json
- **MySQL** (8.0+) or **MariaDB** (10.5+)
- **Git** (2.30+)
- **(Optional)** **Redis** (7.0+) for caching

---

## Quick Start (Docker Compose)

### 1. Clone the Repository

```bash
git clone <repository-url> promotion_optimize
cd promotion_optimize
```

### 2. Environment Setup

Copy the environment template:

```bash
cp .env.example .env
```

Edit `.env` and configure:

```env
# Database Configuration
DB_HOST=mysql
DB_DATABASE=promotion_platform
DB_USERNAME=root
DB_PASSWORD=your_secure_password

# Backend Configuration
APP_ENV=development
APP_BASE_URL=http://localhost:8080

# Frontend Configuration
NUXT_PUBLIC_API_BASE_URL=http://localhost:8080/api

# JWT Secret (generate with: openssl rand -base64 32)
JWT_SECRET=your_jwt_secret_key_here

# Session Configuration
SESSION_DRIVER=file
SESSION_LIFETIME=3600

# Email Configuration (optional)
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@example.com

# LINE Notification (optional)
LINE_CHANNEL_ACCESS_TOKEN=your_line_token
```

### 3. Start Docker Services

```bash
docker-compose up -d
```

This will start:
- **MySQL** on port `3306`
- **Backend (PHP)** on port `8080`
- **Frontend (Nuxt)** on port `3000`
- **(Optional) Redis** on port `6379`

### 4. Database Migration

Run database migrations to create tables:

```bash
docker-compose exec backend php spark migrate
```

Seed initial data (roles, permissions):

```bash
docker-compose exec backend php spark db:seed InitialSeeder
```

### 5. Access the Application

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8080/api
- **phpMyAdmin**: http://localhost:8081 (if enabled)

### 6. Create Admin User

Register via the web interface at http://localhost:3000/auth/register, then manually assign super admin role in the database:

```sql
-- Connect to MySQL
docker-compose exec mysql mysql -uroot -p promotion_platform

-- Find the user ID
SELECT id, username, email FROM users WHERE email = 'youremail@example.com';

-- Assign super_admin role (role_id = 1)
INSERT INTO user_roles (user_id, role_id, assigned_by)
VALUES (1, 1, 1);
```

---

## Manual Setup (Without Docker)

### 1. Backend Setup

```bash
cd backend

# Install Composer dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env with your database credentials

# Run migrations
php spark migrate

# Seed database
php spark db:seed InitialSeeder

# Start development server
php spark serve --port=8080
```

### 2. Frontend Setup

```bash
cd frontend

# Install npm dependencies
npm install

# Configure environment
cp .env.example .env
# Edit .env to point to backend API

# Start development server
npm run dev
```

Frontend will be available at http://localhost:3000

---

## Common Development Tasks

### Running Migrations

```bash
# Create new migration
docker-compose exec backend php spark make:migration AddColumnToServers

# Run pending migrations
docker-compose exec backend php spark migrate

# Rollback last migration
docker-compose exec backend php spark migrate:rollback
```

### Creating API Endpoints

1. **Create Controller**:

```bash
docker-compose exec backend php spark make:controller API/ExampleController
```

2. **Define Routes** in `backend/app/Config/Routes.php`:

```php
$routes->group('api', ['namespace' => 'App\Controllers\API'], function($routes) {
    $routes->get('example', 'ExampleController::index');
    $routes->post('example', 'ExampleController::create');
});
```

3. **Implement Controller** in `backend/app/Controllers/API/ExampleController.php`:

```php
<?php
namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;

class ExampleController extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        return $this->respond([
            'success' => true,
            'data' => []
        ]);
    }
}
```

### Adding Frontend Pages

1. **Create Page Component** in `frontend/pages/`:

```vue
<!-- frontend/pages/example.vue -->
<template>
  <div>
    <h1>Example Page</h1>
  </div>
</template>

<script setup>
// Page logic here
</script>
```

2. **Auto-routing**: File `pages/example.vue` → Route `/example`

### Testing APIs

Use the provided Postman collection (if available) or test with curl:

```bash
# Login
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"password"}'

# Get current user (with token)
curl -X GET http://localhost:8080/api/auth/me \
  -H "X-Session-Token: YOUR_TOKEN_HERE"
```

---

## Troubleshooting

### Port Already in Use

```bash
# Find process using port 3000
lsof -i :3000

# Kill the process
kill -9 <PID>

# Or change port in docker-compose.yml
```

### Database Connection Failed

```bash
# Check MySQL container is running
docker-compose ps

# View MySQL logs
docker-compose logs mysql

# Restart MySQL
docker-compose restart mysql
```

### Frontend Build Errors

```bash
# Clear Nuxt cache
cd frontend
rm -rf .nuxt node_modules package-lock.json
npm install
npm run dev
```

### Backend Errors

```bash
# View backend logs
docker-compose logs backend

# Clear CodeIgniter cache
docker-compose exec backend rm -rf backend/writable/cache/*

# Check PHP version
docker-compose exec backend php -v
```

### Permission Issues

```bash
# Fix writable directory permissions
chmod -R 777 backend/writable
chmod -R 777 backend/public/uploads
```

---

## Project Structure Quick Reference

```
promotion_optimize/
├── frontend/                # Nuxt.js 3 Application
│   ├── pages/              # Auto-routed pages
│   ├── components/         # Vue components
│   ├── stores/             # Pinia state stores
│   ├── layouts/            # Layout templates
│   └── middleware/         # Route middleware
│
├── backend/                 # CodeIgniter 4 API
│   ├── app/
│   │   ├── Controllers/API/  # API controllers
│   │   ├── Models/          # Database models
│   │   ├── Filters/         # Request/response filters
│   │   ├── Libraries/       # Business logic services
│   │   └── Database/
│   │       ├── Migrations/  # Schema migrations
│   │       └── Seeds/       # Seed data
│   ├── public/             # Web root & uploads
│   └── writable/           # Logs & cache
│
├── docker-compose.yml      # Docker services
├── .env                    # Environment variables
└── specs/                  # SpecKit documentation
    └── 002-view-spekkit/
        ├── spec.md         # Feature specification
        ├── plan.md         # Implementation plan
        ├── data-model.md   # Database schema
        ├── research.md     # Technical decisions
        ├── quickstart.md   # This file
        └── contracts/      # OpenAPI specs
```

---

## Next Steps

1. **Review Documentation**:
   - Read [spec.md](./spec.md) for complete feature requirements
   - Review [data-model.md](./data-model.md) for database schema
   - Check [research.md](./research.md) for technical decisions

2. **API Contracts**:
   - Explore OpenAPI specs in `contracts/` directory
   - Import into Postman or Swagger UI

3. **Development Workflow**:
   - Create feature branch: `git checkout -b feature/your-feature`
   - Make changes following CLAUDE.md guidelines
   - Test thoroughly
   - Commit with conventional format: `feat: add feature description`
   - Push and create pull request

4. **Testing**:
   - Manual testing via browser and Postman
   - Automated testing planned for Phase 2

5. **Deployment**:
   - Production deployment uses same Docker Compose setup
   - Configure production .env with secure credentials
   - Enable Redis caching for production

---

## Useful Commands Cheatsheet

```bash
# Docker
docker-compose up -d              # Start services
docker-compose down               # Stop services
docker-compose logs -f backend    # Tail backend logs
docker-compose exec backend bash  # Access backend shell

# Backend (CodeIgniter)
php spark migrate                 # Run migrations
php spark db:seed SomeSeeder      # Run seeder
php spark make:model ModelName    # Create model
php spark cache:clear             # Clear cache

# Frontend (Nuxt)
npm run dev                       # Development server
npm run build                     # Production build
npm run generate                  # Static site generation
npm run preview                   # Preview production build

# Database
docker-compose exec mysql mysql -uroot -p  # MySQL CLI
php spark db:table users                    # Show table structure

# Git
git status                        # Check status
git add .                         # Stage all changes
git commit -m "feat: description" # Commit with message
git push origin branch-name       # Push to remote
```

---

## Getting Help

- **Documentation**: Check `specs/` directory for comprehensive docs
- **Issues**: Check project README or issue tracker
- **CLAUDE.md**: Review project-specific instructions
- **API Docs**: Refer to OpenAPI specs in `contracts/`

---

**Happy Coding! 🚀**
