# FDR-016: Google Calendar integration

**Feature:** 16  
**Status:** Approved  
**Reference:** [16 Google Calendar integration](../../05%20-%20Feature%20List.md#f16-google-calendar), [ADR-010](../../ADRs/ADR-010-google-calendar-integration.md), [ADR-012](../../ADRs/ADR-012-integration-failure-isolation.md)

---

## How it works

1. **GoogleCalendarService** wraps Calendar API `events.insert` for follow-ups and important tasks.
2. On create/update of eligible records, queue **CreateCalendarEventJob** with title, description, start/end from due date.
3. Store returned `google_event_id` on record for updates/deletes (one-way sync from CRM).
4. **Single calendar ID** from `.env` (see deployment notes).
5. No bidirectional sync; no internal calendar tables.

```mermaid
flowchart TD
    Save[CRM follow-up saved] --> Job[CreateCalendarEventJob]
    Job --> GCal[Google Calendar API]
    GCal --> Job
    Job --> Store[Store google_event_id on record]
```

---

## How to test

- Mock Google API; event created with correct times/timezone.
- Update follow-up updates or recreates event (strategy TBD: patch vs delete+create).
- API failure does not rollback CRM save.

---

## Acceptance criteria

- [ ] Events created for follow-ups and important tasks.
- [ ] Single company calendar ID configurable.
- [ ] No bidirectional sync.
- [ ] Failures isolated and retried.
- [ ] OAuth/service account setup documented.

---

## Deployment notes

- Google Cloud project credentials in secrets; restrict calendar scope.
