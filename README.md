# DevHub Backend

Backend API for DevHub, a developer community platform built with Laravel.

## Base URLs

- Local API base URL: `http://127.0.0.1:8000/api/v1`
- Production API base URL: `https://devhub.azurewebsites.net/api/v1`

## Quick Project Info

- Framework: Laravel 12 (PHP)
- API version prefix: `/api/v1`
- Auth: JWT (`auth:api` middleware)
- Main entry points:
  - API routes: `routes/api.php`
  - Web routes: `routes/web.php`

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Useful Commands

```bash
php artisan test
npm run build
```

## Health Check

- API status endpoint: `GET /api/v1/`

## License

This project is licensed under the MIT License.
