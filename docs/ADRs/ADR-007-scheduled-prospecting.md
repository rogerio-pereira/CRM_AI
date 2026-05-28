# ADR-007: Scheduled prospecting (weekdays 08:00)

## Status

Accepted

## Context

The PRD and HLD require automated prospecting to start at **08:00 on weekdays**. Prospecting is implemented via Laravel scheduled commands that trigger prospecting agents.

## Decision

- Register a **Laravel Scheduled Command** that runs **Monday–Friday at 08:00** (application timezone to be configured in deployment).
- The command invokes the **AI orchestration service** to start prospecting agents (see [ADR-003](ADR-003-ai-orchestration-architecture.md)).
- Discovered leads are persisted and enqueued for qualification per main workflow in the PRD.

References:

- [PRD Automation Rules — Prospecting](../01%20PRD.md#prospecting)
- [HLD Scheduled Workflows](../02%20HLD.md#scheduled-workflows)

## Consequences

- **Positive:**
  - Predictable operational rhythm for internal users.
  - Decouples cron from individual agent implementations.
- **Negative:**
  - Single schedule for all users; no per-user timezone customization in MVP.
- **Neutral:**
  - Manual “run prospecting now” may be added later without changing the schedule contract.
