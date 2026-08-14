# FDR-019: Opportunity notes

**Feature:** 19  
**Status:** Approved  
**Reference:** [19 Opportunity notes](../../05%20-%20Feature%20List.md#f19-opportunity-notes), [05 Opportunity management and Kanban pipeline](../../05%20-%20Feature%20List.md#f05-opportunity-kanban-pipeline), [ADR-018](../../ADRs/ADR-018-proposal-artifact-rendering-and-delivery.md), [Design System §17A](../../04%20-%20Design%20System.md)

---

## How it works

1. Each opportunity has a **timeline of notes**: multiple entries with body, author (authenticated user), and created timestamp.
2. Users add notes from the opportunity detail UI; notes are append-only in MVP (edit/delete optional only if trivial—prefer append-only for KISS).
3. Notes are included as context input for Proposal Assistant recommendations ([FDR-013](FDR-013-proposal-assistance.md)).
4. Notes are internal; they are not automatically sent to clients.

---

## How to test

- Add multiple notes; order is chronological with author visible.
- Unauthorized users cannot write notes.
- Creating a note does not alter pipeline stage.
- Feature + browser tests on opportunity detail notes section (`data-test` selectors).

---

## Acceptance criteria

- [ ] `opportunity_notes` (or equivalent) persistence with opportunity_id, user_id, body, timestamps.
- [ ] Timeline UI on opportunity detail.
- [ ] Notes available to proposal assistance as context.
- [ ] Tests cover create/list authorization.

---

## Deployment notes

- No external integrations.
