# simple_reservation
Simple Reservation system as a case study using symfony and next.js

## Structure

```
backend/    Symfony 8 JSON API (PHP 8.4+, PostgreSQL, Doctrine)
frontend/   Next.js + React (coming soon)
```

## Running the backend

```bash
cd backend
composer install
# put your real DATABASE_URL in backend/.env.local (and .env.test.local for tests)
php bin/console doctrine:migrations:migrate
symfony serve -d          # API on http://127.0.0.1:8000/api/reservations
php bin/phpunit           # tests
```
