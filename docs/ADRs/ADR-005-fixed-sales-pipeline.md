# ADR-005: Fixed eight-stage sales pipeline

## Status

Accepted (amended 2026-09-10)

The **fixed, not admin-configurable** rule in this ADR still stands. The **ordered stage list** is superseded by [ADR-021](ADR-021-follow-up-email-sequence.md) (Contact Sent, Meeting Scheduled, No Response, Disqualified, plus the original eight).

## Context

The HLD defines a **fixed** pipeline that is **not dynamically configurable** in MVP. Eight stages drive automation, UI colors, and agent behavior.

## Decision

Use exactly these stages, in order:

1. Lead  
2. Qualification  
3. Contact  
4. Proposal Generation  
5. Proposal Analysis  
6. Proposal Sent  
7. Won  
8. Lost  

**Behavior highlights (per HLD):**

| Stage | Primary actor / automation |
| ----- | --------------------------- |
| Lead | Prospecting agent creates leads here |
| Qualification | Qualification agent runs async; advance after analysis |
| Contact | Human-driven; external conversations |
| Proposal Generation | Proposal assistant agent triggered |
| Proposal Analysis | Human review before send |
| Proposal Sent | Awaiting outcome |
| Won / Lost | Terminal states |

**UI:** Kanban is the primary opportunity interface; stage colors per [ADR-013](ADR-013-dark-mode-design-system.md) / Design System §5.

References:

- [HLD §5 Sales Pipeline](../02%20HLD.md#5-sales-pipeline)
- [Design System §5 Pipeline Stage Colors](../04%20-%20Design%20System.md#5-pipeline-stage-colors)
- [ADR-021 Follow-up email sequence](ADR-021-follow-up-email-sequence.md) — current ordered stages

## Consequences

- **Positive:**
  - Predictable automation rules and UI; no admin pipeline builder in MVP.
- **Negative:**
  - Cannot customize stages per team without a future feature.
- **Neutral:**
  - Stage change events feed [feature 14](../05%20-%20Feature%20List.md#f14-pipeline-stage-automation) automation.
  - 2026-09-10: current ordered stages and **No Response** live in [ADR-021](ADR-021-follow-up-email-sequence.md).
