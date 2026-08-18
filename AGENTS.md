# KopiPOS Agent Guide

## Overview
KopiPOS is a single-store POS modular monolith using PHP 8.3, Laravel 12.64, Livewire 3.8, Blade, Alpine.js, Tailwind CSS 4, Vite 7, and MySQL 8.0. Package managers: Composer and npm.

## Commands
```bash
composer install && npm install
php artisan migrate:fresh --seed
composer run test
./vendor/bin/pint --test
npm run dev
npm run build
php artisan serve --host=127.0.0.1 --port=8310
```

## Conventions
Use backed enums for statuses, Actions for business transactions, Policies/Gates for access, and integer rupiah amounts.
```php
DB::transaction(fn () => app(CompleteOrder::class)->handle($payload));
```

## Boundaries
- **NEVER** commit `.env`, secrets, credentials, production hosts, customer data, or payment card data.
- **NEVER** edit `vendor/`, `node_modules/`, generated Vite assets, or paid orders directly.
- **ALWAYS** validate and authorize on server.
- **ALWAYS** use one DB transaction for order, payment, stock movement, and stock update.
- **IMPORTANT**: checkout needs idempotency protection; held orders do not consume stock.

## Dependencies
- `laravel/framework 12.64`: application framework and session auth.
- `livewire/livewire 3.8`: interactive POS without full-page reload.
- `tailwindcss 4`: responsive touch-first UI.
- PHPUnit 11 + Pint: tests and code style.

## Config
Required: `${APP_KEY}`, `${DB_HOST}`, `${DB_DATABASE}`, `${DB_USERNAME}`, `${DB_PASSWORD}`. Defaults: `APP_TIMEZONE=Asia/Jakarta`, `APP_CURRENCY=IDR`. Placeholders only; never copy real values into docs.

## Error Handling
Throw domain exceptions for invalid shift, unavailable products, insufficient stock, and duplicate submission. Log unexpected failures; show cashier-safe messages. Payment persistence must roll back completely on failure.

## Troubleshooting
- POS refuses checkout: verify cashier has one OPEN shift.
- Duplicate transaction: inspect `submission_token` unique index and disabled payment button.
- Wrong totals: run PricingService tests and verify integer rupiah values.
- Stock mismatch: inspect immutable `stock_movements` ledger and recipe quantities.
- Livewire slow: inspect query count, component payload, catalog cache, and eager loading.
- Tunnel returns 502: verify Nginx listens on `127.0.0.1:8311` and `/etc/nginx/sites-enabled/coffee-pos` exists.

## Architecture
Domains: Auth, Users, Catalog, Orders, Payments, Shifts, Inventory, Reports, Settings, Audit. Critical path lives in Actions/Services; Livewire components orchestrate UI only.
