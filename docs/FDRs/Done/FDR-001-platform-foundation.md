# FDR-001: Platform foundation

**Feature:** 01  
**Status:** Approved / Verified  
**Reference:** [01 Platform foundation](../../05%20-%20Feature%20List.md#f01-platform-foundation), [ADR-001](../../ADRs/ADR-001-technology-stack.md), [ADR-006](../../ADRs/ADR-006-queue-async-processing.md)

---

## How it works

1. Configure Sail services: **PostgreSQL**, **Redis**, application container.
2. Ensure Laravel 13 app runs migrations, cache, session, and queue connections against PostgreSQL/Redis.
3. Install and configure **Laravel Horizon** for queue monitoring.
4. Document Laravel Cloud deployment baseline (app, workers, scheduler, Redis, PostgreSQL).
5. Enable Laravel **scheduler** in production (cron → `schedule:run`).

```mermaid
flowchart TD
    Dev[Sail local] --> PG[(PostgreSQL)]
    Dev --> Redis[(Redis)]
    Cloud[Laravel Cloud] --> PG
    Cloud --> Redis
    Cloud --> Workers[Queue workers + Horizon]
    Cloud --> Sched[Scheduler]
```

---

## How to test

- `sail up` — all services healthy; `migrate` succeeds.
- Dispatch a test job — processed by worker; visible in Horizon.
- Scheduler list includes prospecting scheduler placeholder comment (feature 10).

---

## Acceptance criteria

- [x] PostgreSQL is the default DB; migrations run cleanly.
- [x] Redis queue configured; test job completes.
- [x] Horizon dashboard accessible to authenticated operators.
- [x] Sail `docker-compose` documents PG + Redis + app.
- [x] Deployment notes for Laravel Cloud (workers, scheduler, env) documented in README.
- [x] Scheduler contains a feature-10 prospecting placeholder comment until `prospecting:run` is implemented.

Verification notes:

- Verified on 2026-07-31 with Sail runtime drivers: database `pgsql`, queue/cache/session `redis`, timezone `America/New_York`.
- Ran targeted platform/auth checks and browser navigation checks.
- Ran pending migrations successfully.

---

## Deployment notes

- Production requires dedicated queue workers and Horizon; scheduler must run every minute.
- Set `APP_TIMEZONE` explicitly for weekday 08:00 prospecting ([ADR-007](../../ADRs/ADR-007-scheduled-prospecting.md)).
