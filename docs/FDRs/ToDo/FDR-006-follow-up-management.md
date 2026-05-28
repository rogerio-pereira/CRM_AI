# FDR-006: Follow-up management

**Feature:** 06  
**Status:** Approved  
**Reference:** [06 Follow-up management](../../05%20-%20Feature%20List.md#f06-follow-up-management), [ADR-009](../../ADRs/ADR-009-slack-integration.md), [ADR-010](../../ADRs/ADR-010-google-calendar-integration.md)

---

## How it works

1. **FollowUp** model: due date, priority, notes, reminder status, `client_id`, optional `opportunity_id`.
2. CRUD via Livewire: **follow-ups table**, create/edit modal.
3. Mark complete / snooze reminder status.
4. Hooks for features 15–16: on create/update, queue calendar event and evaluate Slack rules.
5. Surface on dashboard table (feature 08).

---

## How to test

- Create follow-up linked to client and opportunity.
- Due date filtering; overdue shows warning styling (`status.warning`).
- Completing follow-up updates reminder status.

---

## Acceptance criteria

- [ ] All PRD follow-up attributes supported.
- [ ] Table UI per Design System; modal create/edit.
- [ ] Relations to client and opportunity enforced.
- [ ] Events/hooks stubbed for Calendar and Slack integrations.
- [ ] Feature tests for CRUD and date validation.

---

## Deployment notes

- Timezone-aware due dates (use app timezone).
