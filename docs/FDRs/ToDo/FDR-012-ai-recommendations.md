# FDR-012: AI recommendations and insights

**Feature:** 12  
**Status:** Approved  
**Reference:** [12 AI recommendations and insights](../../05%20-%20Feature%20List.md#f12-ai-recommendations), [ADR-011](../../ADRs/ADR-011-human-approval-commercial-actions.md), [ADR-013](../../ADRs/ADR-013-dark-mode-design-system.md), [ADR-017](../../ADRs/ADR-017-wave-4-ai-qualification-schema.md)

---

## How it works

1. **Recommendation Agent** generates: company summary, pain points, opportunity analysis, and general outreach strategy.
2. Persist on lead/opportunity AI fields using schema version 1 from ADR-017; render **AI Suggestion Panel** in detail modals (border `ai`, badge “AI Insight”).
3. Suggestions are **read-only recommendations** until user acts ([ADR-011](../../ADRs/ADR-011-human-approval-commercial-actions.md)).
4. Triggered after successful qualification (and on demand button “Refresh AI insights” optional).

---

## How to test

- After qualification job, panel shows structured sections.
- UI labels all content as AI-generated.
- Refresh re-queues job; idempotent display update.
- No automatic emails/messages sent.

---

## Acceptance criteria

- [ ] PRD suggestion types covered.
- [ ] Recommendations follow schema version 1 from ADR-017.
- [ ] AI UI patterns per Design System §17.
- [ ] Human approval model respected (no autonomous outreach).
- [ ] Livewire components render insights on lead and opportunity views.
- [ ] Tests with fixture AI JSON.

---

## Deployment notes

- Rate-limit manual refresh to prevent API abuse.
