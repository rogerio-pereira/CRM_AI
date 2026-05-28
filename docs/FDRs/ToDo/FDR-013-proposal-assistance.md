# FDR-013: Proposal assistance

**Feature:** 13  
**Status:** Approved  
**Reference:** [13 Proposal assistance](../../05%20-%20Feature%20List.md#f13-proposal-assistance), [ADR-016](../../ADRs/ADR-016-proposal-generation-undefined-mvp.md) (**Proposed**), [ADR-011](../../ADRs/ADR-011-human-approval-commercial-actions.md)

---

## Implementation readiness

| Dependency | ADR status | Impact on this FDR |
| ---------- | ----------- | ------------------ |
| [ADR-016 — Proposal format](../../ADRs/ADR-016-proposal-generation-undefined-mvp.md) | **Proposed** | **Blocked:** proposal output UI, persistence, and agent response parsing must not be finalized until ADR-016 is **Accepted**. |
| [ADR-011 — Human approval](../../ADRs/ADR-011-human-approval-commercial-actions.md) | Accepted | No auto-send; human review stages apply regardless. |

### Decisions required before build (confirm with stakeholder)

| # | Topic | Status |
| - | ----- | ------ |
| 1 | Output format (markdown, sections JSON, clipboard, etc.) | ☐ Not confirmed — see ADR-016 |
| 2 | Storage model (single field vs structured sections) | ☐ Not confirmed — see ADR-016 |
| 3 | MVP scope (in-app draft only vs export) | ☐ Not confirmed — see ADR-016 |

Until ADR-016 is **Accepted**, implement only: orchestration hook on **Proposal Generation** stage and a **stub agent** returning fixture content for tests.

---

## How it works

1. When opportunity enters **Proposal Generation** (manual move or automation feature 14), dispatch **Proposal Assistant Agent**.
2. Agent inputs: prospecting data, qualification analysis, user notes from opportunity modal.
3. Output format **TBD** per ADR-016 (options: markdown block in CRM, structured sections, copy-to-clipboard)—**decide before implementation**.
4. **Proposal Analysis** stage: user edits/reviews; no auto-send.
5. Moving to **Proposal Sent** is explicit user action only.

---

## How to test

- Stage transition to Proposal Generation triggers job.
- Draft appears in opportunity UI; user can edit before Sent.
- AI failure does not corrupt opportunity record.
- No PDF/signature unless explicitly added later.

---

## Acceptance criteria

- [ ] ADR-016 status is **Accepted**; format/storage rows in ADR-016 checked; this FDR updated with chosen options.
- [ ] Proposal assistant integrated via orchestration.
- [ ] Human review stage supported in Kanban workflow.
- [ ] Output format documented in this FDR **after** ADR-016 **Accepted** (not before).
- [ ] No autonomous client delivery.
- [ ] Tests with mocked agent output.

---

## Deployment notes

- Long-running jobs: timeout > LLM latency.
