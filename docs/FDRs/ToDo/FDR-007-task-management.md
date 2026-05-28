# FDR-007: Task management

**Feature:** 07  
**Status:** Approved  
**Reference:** [07 Task management](../../05%20-%20Feature%20List.md#f07-task-management)

---

## How it works

1. **Task** model: title, description, due date, priority, status (pending/done), links to client and optional opportunity.
2. CRUD: **tasks table**, modals for create/edit.
3. “Important task” flag drives Slack (feature 15) and Calendar (feature 16) eligibility.
4. Pending tasks feed dashboard (feature 08).

---

## How to test

- Create task with and without opportunity link.
- Mark done; disappears from pending dashboard query.
- Important flag triggers notification hooks (mocked until features 15–16).

---

## Acceptance criteria

- [ ] Task CRUD with priority and status.
- [ ] Table matches Design System patterns.
- [ ] Important tasks distinguishable in UI.
- [ ] Dashboard can query pending tasks efficiently.
- [ ] Feature tests for lifecycle.

---

## Deployment notes

- None specific.
