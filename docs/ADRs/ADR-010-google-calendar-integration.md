# ADR-010: Google Calendar integration

## Status

Accepted

## Context

The PRD requires Google Calendar for follow-ups and important tasks. The HLD specifies a thin API wrapper without an internal calendar model or bidirectional sync.

## Decision

- Implement a **wrapper over Google Calendar API** to **create events** for:
  - Follow-ups
  - Important tasks
- **No internal calendar model** in the database for MVP.
- **No bidirectional synchronization** (Calendar is source of truth for scheduling intent; CRM is a mirror).
- MVP uses a **single company calendar** (calendar ID configured in settings).

References:

- [PRD Integrations](../01%20PRD.md#integrations)
- [HLD Google Calendar Integration](../02%20HLD.md#google-calendar-integration)

## Consequences

- **Positive:**
  - Users see reminders in existing Google Calendar workflow.
  - Minimal data duplication and sync complexity.
- **Negative:**
  - Edits/deletes in Google Calendar are not reflected in CRM automatically.
- **Neutral:**
  - OAuth / service account setup documented in feature 17 FDR.
