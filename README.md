# CRM

Operational documentation for local environment setup, daily commands, and runtime operations lives in this file.

## Prerequisites

- Docker and Docker Compose available
- Node.js and npm available on host (only if running host-side tooling)
- `vendor/` dependencies installed via Sail composer image when missing

## First-time setup

```bash
test -d ./vendor || \
 docker run --rm -u "$(id -u):$(id -g)" \
 -v "$(pwd):/var/www/html" \
 -w /var/www/html \
 laravelsail/php85-composer:latest \
 composer install --ignore-platform-reqs --no-interaction --prefer-dist --optimize-autoloader

./vendor/bin/sail up -d

test -f .env || cp .env.example .env

grep -q "^APP_KEY=" .env || ./vendor/bin/sail artisan key:generate
grep -q "^APP_KEY=$" .env && ./vendor/bin/sail artisan key:generate
```

## Runtime defaults

- Database: PostgreSQL (`DB_CONNECTION=pgsql`)
- Queue: Redis (`QUEUE_CONNECTION=redis`)
- Cache/session: Redis
- Queue monitoring: Laravel Horizon (`/horizon`)
- Timezone: `America/New_York` in `config/app.php` (required for weekday **08:00** prospecting per ADR-007)
- Prospecting schedule: **off by default**. Set `PROSPECTING_ENABLED=true` in `.env` to let the weekday 08:00 cron run `prospecting:run`. Manual `php artisan prospecting:run` always dispatches one job per lead (up to `PROSPECTING_DEFAULT_LIMIT`), even when the flag is `false`.

## Daily development commands

Start services:

```bash
./vendor/bin/sail up -d
```

Install frontend dependencies:

```bash
./vendor/bin/sail npm install
```

Build frontend assets (required after CSS or design-token changes, and before browser tests):

```bash
./vendor/bin/sail npm run build
```

Run Vite dev server:

```bash
./vendor/bin/sail npm run dev
```

Stop services:

```bash
./vendor/bin/sail down
```

## Queue and Horizon

Run Horizon manually (development):

```bash
./vendor/bin/sail artisan horizon
```

Terminate Horizon during deployments:

```bash
./vendor/bin/sail artisan horizon:terminate
```

Run queue worker without Horizon (fallback):

```bash
./vendor/bin/sail artisan queue:work redis --queue=default
```

Access Horizon dashboard:

- Local: `http://localhost/horizon` (or your configured app URL + `/horizon`)
- Non-local: access controlled by `App\Providers\HorizonServiceProvider` and `HORIZON_ALLOWED_EMAILS`

## Scheduler operations

Inspect scheduled tasks:

```bash
./vendor/bin/sail artisan schedule:list
```

Run scheduler once:

```bash
./vendor/bin/sail artisan schedule:run
```

Production requirement:

- Configure cron to run `php artisan schedule:run` every minute
- Application timezone is fixed in `config/app.php` before enabling prospecting (FDR-010)
- Set `PROSPECTING_ENABLED=true` before the weekday 08:00 cron should dispatch; the default is `false`. Manual `php artisan prospecting:run` is not gated by this flag.

## Laravel Cloud deployment

Baseline for production on [Laravel Cloud](https://cloud.laravel.com):

1. **Application** — deploy the Laravel app with build step `./vendor/bin/npm run build` (or equivalent) so Vite assets are compiled.
2. **PostgreSQL** — managed database; set `DB_*` secrets to match the cluster.
3. **Redis** — managed Redis for `QUEUE_CONNECTION`, `CACHE_STORE`, and `SESSION_DRIVER`.
4. **Environment** — set `APP_KEY`, `APP_URL`, `HORIZON_ALLOWED_EMAILS` (comma-separated operator emails), and mail credentials for password reset.
5. **Dedicated workers** — run `php artisan horizon` (Horizon supervises Redis queue workers; tries/timeout in `config/horizon.php`).
6. **Scheduler** — Cloud scheduler or cron every minute: `php artisan schedule:run`.
7. **Users** — public registration is disabled; provision internal users via seeding or admin tools (see Authentication below).

After deploy, verify `/horizon` for allowlisted operators and `schedule:list` shows `prospecting:run` weekdays at 08:00 (`America/New_York`). The scheduled run is skipped while `PROSPECTING_ENABLED` is `false`; a manual `php artisan prospecting:run` still dispatches.

## Test and quality gates

Before full suite (browser coverage included), build frontend once:

```bash
./vendor/bin/sail npm run build
```

Run full tests with coverage threshold:

```bash
./vendor/bin/sail artisan test --parallel --coverage --min=90
./vendor/bin/sail artisan test --type-coverage --min=90 --parallel
```

Run lint:

```bash
./vendor/bin/sail exec laravel.test vendor/bin/pint --parallel
```

## Authentication (local)

Seed the default admin user:

```bash
./vendor/bin/sail artisan db:seed
```

Local credentials (from `UserSeeder`): `admin@admin.com` / `password` — **change or disable before production**.

Public registration is disabled. Use `db:seed` for local admin access (see above).

### Production mail (password reset)

Set provider variables, for example:

- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`

Tests use `MAIL_MAILER=log` or `array`; production must use a real transport.

## Troubleshooting

- If dependencies are missing, run the first-time setup block again.
- If application key is empty, regenerate with `./vendor/bin/sail artisan key:generate`.
- If assets are stale, run `./vendor/bin/sail npm run build`.
- If queue jobs are stuck, check Redis health and Horizon status.

