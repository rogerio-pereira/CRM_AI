# FDR-021: Follow-up email sequence

**Feature:** 21  
**Status:** Implemented  
**Reference:** [21 Follow-up email sequence](../../05%20-%20Feature%20List.md#f21-follow-up-email-sequence), [06 Follow-up management](../../05%20-%20Feature%20List.md#f06-follow-up-management), [05 Opportunity management and Kanban pipeline](../../05%20-%20Feature%20List.md#f05-opportunity-kanban-pipeline), [19 Opportunity notes](../../05%20-%20Feature%20List.md#f19-opportunity-notes), [ADR-021](../../ADRs/ADR-021-follow-up-email-sequence.md), [ADR-005](../../ADRs/ADR-005-fixed-sales-pipeline.md), [ADR-019](../../ADRs/ADR-019-human-controlled-proposal-delivery.md)

---

## How it works

### Already in the product (extend, do not duplicate)

Sending the introduction from the opportunity AI panel already:

1. Sends SMTP (`FirstContactOutreachMail`).
2. Dispatches `ContactWithFollowUp`.
3. Moves the opportunity to **Contact Sent**.
4. Creates a follow-up reminder (+3 days at 09:00, medium priority).
5. Writes an opportunity note that the first email was sent.

This FDR adds a **Send follow-up** action, a **copywriter job**, and the terminal stage **No Response**. It does not add a sequence number on reminder rows.

### Sequence storytelling

Same voice as the first-contact prompt (Gustavo Ferreira / Allison Hardy; Roger Pereira; Front Porch Creative). Stay on the **same problem** as the introduction. Each follow-up **stands alone** (does not depend on the reader having opened the previous email). New subject each time (not `Re:`).

| Email | Job | CTA |
| ----- | --- | --- |
| Introduction (existing) | Observation + quick win | Reply with 3 dates and times for a 1-hour online discovery meeting |
| Follow-up 1 | **New** insight + quick win, same problem | Same CTA |
| Follow-up 2 | **New** insight + quick win, and state clearly this is the **last** email | Same CTA, last-email tone |

The copywriter receives the introduction `contact_example` as `previous_emails`, plus existing opportunity notes, so it does not repeat an insight or quick win.

Prompt: `docs/prompts/laravel_tools/write-follow-up-email.md`.

### Reminders vs send

- **Reminder:** system-created after introduction and follow-up 1.
- **Send:** never automatic. Only when a user clicks **Send follow-up** on the Follow-ups page.

### Data

1. Keep using existing `follow_ups` reminders. Do not store FU1 vs FU2 on the row.
2. Add pipeline stage `No Response` (`no_response`): terminal, Kanban order after Lost and before Disqualified, color token `neutral`, sets `OpportunityStatus::Lost`.

### Introduction (listener)

`HandleContactWithFollowUp` after the introduction: move to Contact Sent, “first email sent” note, create a reminder (+3 days at 09:00).

### Follow-ups page

On `[follow-ups.index](../../../app/Livewire/FollowUps/Index.php)`, each pending row with an opportunity may show **Send follow-up**. Any pipeline stage is allowed (Contact Sent drip, later stages such as waiting for a signed contract).

Use `data-test="follow-ups-send-email"` (include the follow-up id in the selector, e.g. `follow-ups-send-email-{id}`).

### Send click (no preview)

1. Dispatch a queued job.
2. The job checks whether the note **Follow-up 1 sent** already exists. That decides `sequence_step` `1` or `2` for the copywriter. The copywriter does not infer the step from notes.
3. Save the sent email (subject and body) as an opportunity note.
4. Send SMTP to the client contact email (same mail stack as first-contact outreach).
5. Mark the follow-up reminder completed.
6. **First follow-up:** note “Follow-up 1 sent” → create the next reminder (+3 days at 09:00).
7. **Second follow-up:** short note that follow-up 2 was sent. If the opportunity is still Contact Sent, `moveToStage(NoResponse)`. Do not create another reminder. Later stages keep their current column.

Failed SMTP: do not complete the reminder, do not create the next reminder, do not move to No Response.

```mermaid
flowchart TD
  intro[Introduction Send]
  fu1[Reminder]
  click1[Send follow-up]
  job1[Job write note SMTP]
  fu2[Next reminder]
  click2[Send follow-up]
  job2[Job write note SMTP]
  nr[No Response]

  intro --> fu1
  fu1 --> click1
  click1 --> job1
  job1 --> fu2
  fu2 --> click2
  click2 --> job2
  job2 --> nr
```

---

## How to test

- **Introduction:** Send first-contact email; opportunity is Contact Sent; note exists; pending follow-up due +3 days at 09:00.
- **Send FU1:** Button visible on a pending row with an opportunity at any stage; click queues job; note contains subject/body; SMTP sent; reminder completed; note “Follow-up 1 sent”; new pending follow-up.
- **Send FU2:** SMTP + notes; if still Contact Sent, opportunity moves to No Response and status Lost; no third reminder. Later stages stay in their column.
- **Later stage:** Opportunity in Meeting Scheduled (or Proposal Sent waiting on a signature): button visible; job sends; stage unchanged.
- **SMTP failure:** Reminder stays pending; stage unchanged.
- **Copywriter:** FU2 output states it is the last email; FU1 does not claim that; subjects are not `Re:`; previously used insights are not reused (feature test with fake agent).
- **Kanban:** No Response column exists, is terminal, sits between Lost and Disqualified.
- **Browser:** Follow-ups index send button (`data-test`); opportunity can be moved to No Response; notes timeline shows the saved email.
- **Translations:** Button and stage labels covered for each app locale in Feature tests.

---

## Acceptance criteria

- [x] Introduction send creates a normal reminder (+3 days 09:00).
- [x] Follow-ups index **Send follow-up** for pending rows with an opportunity (`data-test` stable).
- [x] Click dispatches a job: copywriter → opportunity note with subject/body → SMTP → complete reminder.
- [x] FU1 writes **Follow-up 1 sent** and creates the next reminder.
- [x] FU2 notes the send; moves to **No Response** only when the opportunity is still Contact Sent.
- [x] No Response is terminal, ordered after Lost and before Disqualified, `OpportunityStatus::Lost`, color token `neutral`.
- [x] No due-date auto-send. No bulk-delete of reminders on stage change.
- [x] Follow-up prompt documents the storytelling table (new insight per step; FU2 last-email; same CTA).
- [x] Feature + Pest Browser coverage for the flows above.

---

## Deployment notes

- Horizon / Redis workers must run; size job timeout for LLM latency like other AI jobs.
- SMTP as today (Mailpit locally).
