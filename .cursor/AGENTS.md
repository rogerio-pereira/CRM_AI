---
description: Default agent orientation for repositories using this .cursor setup
---

# Agent guide

Reusable defaults for agents working in any repository that includes this `.cursor` folder. **Product scope, domain rules, and feature specs** live under `docs/`—read them before implementing.

Workflow details (Ralph loop, FDR lifecycle, commits, skills): `.cursor/rules/` and `.cursor/skills/`.

---

## Documentation layout

| Topic | Path |
|-------|------|
| Product requirements | `docs/01 PRD.md` |
| High-level design | `docs/02 HLD.md` |
| Branding | `docs/03 - Branding Manual.md` |
| Design system | `docs/04 - Design System.md` |
| Feature list and dependencies | `docs/05 - Feature List.md` |
| Architecture decisions | `docs/ADRs/` |
| Feature specs (todo / done) | `docs/FDRs/ToDo/`, `docs/FDRs/Done/` |
| Feature list & wave status (source of truth) | `docs/05 - Feature List.md` |
| Ralph plan state (ephemeral, gitignored) | `docs/FDRs/ImplementationPlans/` |

Do not invent requirements that contradict these documents.

---

## Stack (team default)

Typical stack across repositories using this setup:

- PHP 8.5+
- Laravel 13+
- Livewire + Flux UI (or Vue + Inertia + Vuetify) — use the matching skill under `.cursor/skills/`
- Pest PHP (including Browser tests; coverage thresholds in `phpunit.xml`)
- Laravel Pint
- Laravel Sail (Docker)
- PostgreSQL, Redis, Laravel Horizon (when async work is in scope)

---

## Coding standards

### PHP

- All code in **English**.
- PSR style: one statement per line.
- No ternary operators in PHP (`condition ? a : b`); use `if` / `else` or early returns.
- Fluent chains: one method call per line; consistent indentation (extra indent for `->` or `.` after assignment).
- For standalone fluent chains, use a single continuation indent level for `->` or `.` lines.
- Thin controllers; business logic in services; Form Requests for validation; service interfaces when useful.
- Every Eloquent model has a factory in `database/factories`.
- Prefer readable code over cleverness.
- Keep controllers thin and move business logic to services.
- Prefer Form Requests for validation.
- Use interfaces for services when appropriate.
- Every Eloquent model must have an equivalent factory in `database/factories`.
- UI: **Livewire** for interactive pages and **Flux** (`flux:*`) for components; follow `docs/04 - Design System.md` and `docs/03 - Branding Manual.md` (dark mode, Tailwind tokens, CRM-first layouts).
- Livewire: **class-based components only** for new work (`app/Livewire/` + `resources/views/livewire/`); see `.cursor/rules/livewire-class-components.mdc`.
- **ALWAYS** Follow Clean Code and treat this motto as non‑negotiable:
    > Any fool can write code that a computer can understand. Good programmers write code that humans can understand. — Robert C. Martin

### UI

- Follow `docs/04 - Design System.md` and `docs/03 - Branding Manual.md` (theme, tokens, layout patterns).
- Feedback via toasts (check stack).

### Tests

- **Pest Browser** for E2E; **do not** use Laravel Dusk.
- Stable selectors: `data-test="..."` (Pest maps `@name` to `[data-test="name"]`) and/or form `name` attributes.
- Each new screen: dedicated browser tests; add routes to smoke coverage (e.g. `tests/Browser/WebRoutesTest.php`).
- Translation tests when the app is localized.
- At least one E2E or Feature test per critical journey—for example: create a record → validate → submit → assert persistence or redirect (e.g. lead → opportunity → pipeline stage).

### Readability

> Any fool can write code that a computer can understand. Good programmers write code that humans can understand. — Robert C. Martin

Prefer clear code over clever shortcuts.

---

## Environment and commands

All commands must run inside Sail. Use the rule in `.cursor/rules/starting-environment.mdc` as the source of truth for setup and test commands.

Key commands:

```
./vendor/bin/sail up -d
./vendor/bin/sail artisan test --parallel --coverage --min=90
./vendor/bin/sail artisan test --type-coverage --min=90 --parallel
./vendor/bin/sail exec laravel.test vendor/bin/pint --parallel
```

Before the full suite (including browser tests): `./vendor/bin/sail npm run build` once, or keep `./vendor/bin/sail npm run dev` running.

---

## Queues and background jobs

Default: **Redis** queue driver (`QUEUE_CONNECTION=redis`), Redis as a Sail service when defined in `compose.yaml`.

**Development (Sail):**

```bash
./vendor/bin/sail artisan queue:work redis --queue=default
```

**Production:** Use a process manager (supervisor, systemd, or Laravel Cloud) to keep the worker alive. Example flags (tune per job type and ADRs):

```bash
php artisan queue:work redis --queue=default --tries=3 --timeout=120 --sleep=3
```

Preferred tuning reference (adjust per workload):

| Flag / setting | Example | Purpose |
|----------------|---------|---------|
| `--tries` | `3` | Max attempts per job |
| `--backoff` | `300` | Seconds between retries |
| `--timeout` | `120` | Kill job after N seconds (e.g. long external API calls) |
| `--sleep` | `3` | Poll interval when queue is empty |
| `retry_after` in `config/queue.php` | `600` | Avoid re-queueing a job still running within the timeout window |

### Horizon (when installed)

The project uses **Laravel Horizon** for Redis queue workers and a dashboard. Horizon is started by Supervisor inside the Sail container (see `docker/8.5/supervisord.conf`).

- **Dashboard (local):** `http://localhost/horizon` (or your app URL + `/horizon`). In non-local environments, access is restricted by the gate in `App\Providers\HorizonServiceProvider` using `config('horizon.allowed_emails')` from env `HORIZON_ALLOWED_EMAILS` (comma-separated emails).
- **Manual run (dev):** `./vendor/bin/sail artisan horizon`
- **Terminate (deploy):** `./vendor/bin/sail artisan horizon:terminate` so the process manager restarts Horizon with new code.
- **Config:** `config/horizon.php` (environments, tries, timeout, backoff). Tune per job type and ADRs.

---

## Pull requests

When a feature branch is complete and pushed:

When a feature is complete and the branch is pushed, **create the PR using the GitHub MCP server** (MCP tools), not the `gh` CLI. If MCP is unavailable, push the branch and tell the user to open the PR manually (branch name + repo URL).

Target the repository’s default branch (usually `main`).

---

## Notes

- Use `docs/` for product, architecture, feature and setup specifications.
- Update this guide only when **team-wide** defaults change (stack versions, coverage gates, queue conventions, PR workflow).
