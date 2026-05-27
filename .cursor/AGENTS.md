---
description: Project initialization guide for Cursor/Codex
---

# Project Initialization

## Overview

Internal AI-assisted CRM for freelance lead generation, opportunity management, sales pipeline tracking, and AI-assisted prospecting automation.

The platform is for **internal operational use** and prioritizes simplicity, automation, and AI-assisted workflows over traditional enterprise CRM complexity.

Primary goals:

- Unified lead/client management
- Opportunity and Kanban pipeline tracking
- Follow-ups and tasks
- AI-assisted prospecting, qualification, and proposals
- Operational dashboard and integrations (Slack, Google Calendar)

Sources of truth: `docs/01 PRD.md`, `docs/02 HLD.md`, `docs/05 - Feature List.md`, `docs/ADRs/`, `docs/FDRs/`.

## Stack

- PHP 8.5
- Laravel 13
- Laravel Livewire (+ Flux UI components per starter kit)
- Pest PHP tests with 90% minimum coverage
- Laravel Pint for linting
- Docker via Laravel Sail
- PostgreSQL, Redis, Laravel Horizon

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
- UI: **Livewire** for interactive pages and **Flux** (`flux:*`) for components; follow `docs/04 - Design System.md` and `docs/03 - Branding Manual.md` (dark mode, Tailwind tokens, CRM-first layouts).
- All pages must have dedicated browser tests and be included in smoke route checks (`tests/Browser/WebRoutesTest.php`).
- All pages must also have translation coverage tests (if applicable), preferably via Feature tests asserting language.
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
php artisan queue:work redis --queue=default --tries=3 --timeout=120 --sleep=3
```

Key flags:
- `--tries=12` — max 12 attempts per job 
- `--backoff=300` — 5-minute delay between retries
- `--timeout=300` — kill a job after 300 s (LLM + email ceiling)
- `--sleep=3` — poll interval when queue is empty

The `retry_after` in `config/queue.php` (Redis connection) is set to **600 s** so Redis does not re-queue a job that is still running within the 300 s timeout window.
Tune `tries`, `timeout`, and `backoff` per job type and ADRs.

### Laravel Horizon (Redis queue dashboard)

The project uses **Laravel Horizon** for Redis queue workers and a dashboard. Horizon is started by Supervisor inside the Sail container (see `docker/8.5/supervisord.conf`).

- **Dashboard (local):** `http://localhost/horizon` (or your app URL + `/horizon`). In non-local environments, access is restricted by the gate in `App\Providers\HorizonServiceProvider` using `config('horizon.allowed_emails')` from env `HORIZON_ALLOWED_EMAILS` (comma-separated emails).
- **Manual run (dev):** `./vendor/bin/sail artisan horizon`
- **Terminate (deploy):** `./vendor/bin/sail artisan horizon:terminate` so the process manager restarts Horizon with new code.
- **Config:** `config/horizon.php` (environments, tries, timeout, backoff). Tune per job type and ADRs.

## Pull requests

When a feature is complete and the branch is pushed, **create the PR using the GitHub MCP server** (MCP tools), not the `gh` CLI. If MCP is unavailable, push the branch and tell the user to open the PR manually (branch name + repo URL).

## Notes

- Use `docs/` for product, architecture, feature and setup specifications.
