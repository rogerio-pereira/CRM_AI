---
description: Project initialization guide for Cursor/Codex
---

# Project Initialization

## Overview

GoViral is a micro SaaS product designed to provide fast, AI-powered
TikTok profile analysis for beginner and small creators.

The product analyzes user-provided profile information and generates
actionable recommendations focused on growth and monetization. It is
positioned as an affordable, impulse-buy entry product rather than a
marketing consultancy.

Primary value proposition: 
- Fast AI-driven analysis 
- Clear, practical recommendations 
- 30-day action plan 
- No learning curve required

## Stack

- PHP 8.5
- Laravel 12
- Inertia (Vue3) With Vuetify
- Pest PHP tests with 90% minimum coverage
- Laravel Pint for linting
- Docker via Laravel Sail

## Standards

- All code must be in English.
- Follow PSR standards (one statement per line).
- Do **not** use ternary operators (`condition ? a : b`) in PHP; use explicit `if` / `else` or early returns.
- For fluent chains, put one method call per line and keep indentation consistent.
- For assigned fluent chains, use a deeper continuation indent for `->` lines.
- For standalone fluent chains, use a single continuation indent level for `->` lines.
- In tests, chained expectations are allowed when each method call is on its own line.
- Follow Clean Code and treat this motto as non‑negotiable:
    > Any fool can write code that a computer can understand. Good programmers write code that humans can understand. — Robert C. Martin
- Prefer readable code over cleverness.
- Keep controllers thin and move business logic to services.
- Prefer Form Requests for validation.
- Use interfaces for services when appropriate.
- Every Eloquent model must have an equivalent factory in `database/factories`.
- All frontend pages must use Vuetify only (components and styling primitives); do not use Tailwind, Bootstrap, or other UI component libraries.
- All pages must have dedicated browser tests and be included in smoke route checks (`tests/Browser/WebRoutesTest.php`).
- Every new public page (ignore Core Routes (Admin) Group) must also have translation coverage tests (en/es/pt), preferably via Feature tests asserting Inertia props.
- Critical user journeys must include at least one end-to-end browser test covering validation, successful submit, and expected persistence/redirect outcomes.
- For browser automation reliability, interactive UI elements used in E2E tests should expose stable selectors: prefer **`data-test="..."`** (Pest Browser resolves `@name` to `[data-test="name"]`) and/or explicit form `name` attributes.
- Browser automation in this project uses **Pest Browser** only. **Do not** add or use Laravel Dusk (`dusk` attributes, Dusk tests, or Dusk-only flows).
- Natural-language instructions to the agent may be Portuguese or English; **all code** must be in English.

## Environment and Commands

All commands must run inside Sail. Use the rule in `.cursor/rules/starting-environment.mdc` as the source of truth for setup and test commands.

Key commands:

```
./vendor/bin/sail up -d
./vendor/bin/sail artisan test --parallel --coverage --min=90
./vendor/bin/sail artisan test --type-coverage --min=90 --parallel
./vendor/bin/sail exec laravel.test vendor/bin/pint --parallel
```

## Queue Worker

The project uses **Redis** as the queue driver (`QUEUE_CONNECTION=redis`). Redis runs as a Sail service (`redis` in `compose.yaml`).

### Running the worker (development / Sail)

```
./vendor/bin/sail artisan queue:work redis --queue=default
```

### Running the worker (production)

Use a process manager (supervisor, systemd, or Laravel Cloud) to keep the worker alive:

```
php artisan queue:work redis --queue=default --tries=12 --backoff=300 --timeout=300 --sleep=3
```

Key flags:
- `--tries=12` — max 12 attempts per job (ADR-011)
- `--backoff=300` — 5-minute delay between retries
- `--timeout=300` — kill a job after 300 s (LLM + email ceiling)
- `--sleep=3` — poll interval when queue is empty

The `retry_after` in `config/queue.php` (Redis connection) is set to **600 s** so Redis does not re-queue a job that is still running within the 300 s timeout window.

### Laravel Horizon (Redis queue dashboard)

The project uses **Laravel Horizon** for Redis queue workers and a dashboard. Horizon is started by Supervisor inside the Sail container (see `docker/8.5/supervisord.conf`).

- **Dashboard (local):** `http://localhost/horizon` (or your app URL + `/horizon`). In non-local environments, access is restricted by the gate in `App\Providers\HorizonServiceProvider` using `config('horizon.allowed_emails')` from env `HORIZON_ALLOWED_EMAILS` (comma-separated emails).
- **Manual run (dev):** `./vendor/bin/sail artisan horizon`
- **Terminate (deploy):** `./vendor/bin/sail artisan horizon:terminate` so the process manager restarts Horizon with new code.
- **Config:** `config/horizon.php` (environments, tries, timeout, backoff). Default supervisor uses `tries=12`, `timeout=330`, `backoff=300` to align with `ProcessAnalysisRequest`.

## Pull requests

When a feature is complete and the branch is pushed, **create the PR using the GitHub MCP server** (MCP tools), not the `gh` CLI. If MCP is unavailable, push the branch and tell the user to open the PR manually (branch name + repo URL).

## Notes

- Use docs in `docs/` for project and setup details
