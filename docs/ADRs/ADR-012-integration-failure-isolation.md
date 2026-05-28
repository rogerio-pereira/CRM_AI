# ADR-012: Integration failure isolation

## Status

Accepted

## Context

The PRD and HLD require that external integration failures must not interrupt core CRM functionality. AI and third-party APIs are less reliable than local database operations.

## Decision

- Slack and Google Calendar calls run in **queued jobs** or guarded try/catch paths that **log failures** without rolling back core CRM transactions.
- AI job failures: record error state on entities where applicable; use queue **retries**; do not block lead/opportunity CRUD.
- Users retain full manual control if integrations are down.

References:

- [PRD Non-Functional — Reliability](../01%20PRD.md#non-functional-requirements)
- [HLD §12 Reliability](../02%20HLD.md#reliability)

## Consequences

- **Positive:**
  - CRM remains usable during Slack/Google/AI outages.
- **Negative:**
  - Users may not receive external notifications until retries succeed.
- **Neutral:**
  - Horizon + logs are the primary debug path (see [ADR-014](ADR-014-dashboard-observability-scope.md)).
