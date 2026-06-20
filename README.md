# DevHub Backend

Backend API for DevHub, a developer community platform built with Laravel.

## Project Snapshot

- Framework: Laravel 12 (PHP)
- API prefix: `/api/v1`
- Auth: JWT (`auth:api` middleware)
- API routes: `routes/api.php`
- Web routes: `routes/web.php`

## Base URLs

- Local API: `http://127.0.0.1:8000/api/v1`
- Production API: `https://dev-hubs.tech/api/v1`

## Prerequisites

Before running the project, make sure you have:

- PHP `8.3+`
- Composer `2+`
- Node.js `18+` and npm
- MySQL (default in `.env.example`) or another configured DB connection

## 1) Install Dependencies and Bootstrap

From the project root, run:

```bash
composer setup
```

This script installs PHP/JS dependencies, creates `.env` if missing, generates `APP_KEY`, runs migrations, and builds frontend assets.

## 2) Configure Environment

Open `.env` and replace placeholder values with real credentials.

At minimum, verify these values:

- App settings: `APP_NAME`, `APP_URL`
- Database: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- JWT: `JWT_SECRET` (generate with `php artisan jwt:secret` if needed)

Optional services (mail, Redis, Cloudinary, Pusher, OneSignal, Algolia, AI providers) can stay unset until you use those features.

## 3) Run the Project (Development)

Recommended (starts API server, queue worker, logs, and Vite together):

```bash
composer dev
```

If you only need the API server:

```bash
php artisan serve
```

## 4) Verify It Works

Health endpoint:

- `GET /api/v1/`

Example request:

```bash
curl http://127.0.0.1:8000/api/v1/
```

Expected response includes fields like `message`, `status`, and `api_docs`.

## Useful Commands

```bash
# Run test suite
composer test

# Run migrations
php artisan migrate

# Clear and rebuild config/cache
php artisan optimize:clear
```

## API Notes

- API documentation URL in the health response: `https://devhub.apidog.io/`
- Most endpoints require JWT authentication.

