# ADR-014: Dashboard and observability scope

## Status

Accepted

## Context

The PRD defines a focused operational dashboard. The HLD explicitly excludes AI metrics, queue health on the product dashboard, failed job monitoring UI, AI cost tracking, and agent observability from the CRM dashboard.

## Decision

**In scope for the product dashboard:**

- Cards: leads created today, opportunities created today
- Charts (last 30 days): leads/day, opportunities/day, sales/day
- Tables: pending tasks, follow-ups

**Operational observability (not product dashboard):**

- Standard Laravel **logs**
- **Laravel Horizon** for queue monitoring

**Explicitly out of scope for MVP dashboard:**

- AI metrics, queue health widgets, failed job UI, AI cost tracking, agent observability feeds

References:

- [PRD Dashboard](../01%20PRD.md#9-dashboard)
- [HLD §8 Dashboard Architecture](../02%20HLD.md#8-dashboard-architecture)
- [HLD §12 Observability](../02%20HLD.md#observability)

## Consequences

- **Positive:**
  - Dashboard stays action-oriented for sales ops.
- **Negative:**
  - Operators use Horizon/logs for queue/AI issues, not the CRM home screen.
- **Neutral:**
  - Future observability could be a separate admin area without changing this ADR.
