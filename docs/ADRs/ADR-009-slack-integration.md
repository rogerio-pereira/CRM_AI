# ADR-009: Slack integration

## Status

Accepted

## Context

The PRD lists Slack as a required MVP integration for operational notifications when user action is required. The HLD limits MVP scope to a single channel and simple text messages.

## Decision

- Integrate with **Slack** for **operational notifications only** (not a chat CRM).
- MVP: **one Slack channel**, **simple messages** (no interactive buttons, no multi-channel routing).
- Notify when user action is required, including:
  - Pending follow-ups
  - Important / critical tasks
  - Proposal-related reminders (e.g. unanswered proposals)
- Do **not** send Slack for every AI completion—only actionable items per PRD.

References:

- [PRD Integrations](../01%20PRD.md#integrations)
- [HLD §9 Slack Integration](../02%20HLD.md#slack-integration)

## Consequences

- **Positive:**
  - Low-noise alerts; fits internal ops workflow.
- **Negative:**
  - No Slack-driven actions (approve/snooze) in MVP.
- **Neutral:**
  - Failures must not break CRM (see [ADR-012](ADR-012-integration-failure-isolation.md)).
