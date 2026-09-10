# ADR-021: Human-triggered follow-up email sequence

## Status

Accepted (2026-09-10)

## Partially supersedes

- The ordered eight-stage list in [ADR-005](ADR-005-fixed-sales-pipeline.md). The pipeline remains **fixed** (not admin-configurable). This ADR is authoritative for the **current ordered stages**, including stages already in code (`Contact Sent`, `Meeting Scheduled`, `Disqualified`) and the new terminal stage **No Response**.
- The “email sequences out of scope” bullet in [ADR-019](ADR-019-human-controlled-proposal-delivery.md). Inbox sync, conversation threads, and `In-Reply-To` remain out of scope. A **human-triggered** sequence of two follow-up emails is now in scope for [21 Follow-up email sequence](../05%20-%20Feature%20List.md#f21-follow-up-email-sequence).

ADR-019 still stands for: AI must never send client email autonomously; every outbound send is an explicit user action.

## Context

After the first-contact email is sent, the CRM already creates a follow-up **reminder** (+3 days at 09:00) and a short opportunity note. That reminder is not a send. There is no sequence step on the follow-up record, no Send action on the Follow-ups page, and no copywriter for follow-up emails.

The sales motion needs a two-step value-drip after the introduction: each email is a new insight on the **same** problem, written only when a user clicks **Send follow-up**. After the second follow-up with no reply, the opportunity should leave the active pipeline as **No Response** (silence, not a lost deal and not a disqualification).

## Decision

### 1. Reminders are automatic; sends are not

1. Creating the next follow-up **reminder** after a successful send is a system action.
2. Sending the follow-up **email** is never automatic (no due-date cron, no Horizon auto-send).
3. The email goes out only when an authenticated user clicks **Send follow-up** on the Follow-ups page.

### 2. Follow-up reminders stay generic

Do **not** store “follow-up 1” or “follow-up 2” on the reminder row. `follow_ups` remains the existing reminder list (due date, priority, complete).

Whether the next send is FU1 or FU2 is decided at send time: if the opportunity already has the note **Follow-up 1 sent**, this send is the last email; otherwise it is the first follow-up. The copywriter receives that step as `sequence_step` in its dossier. It does not infer the step from notes.

### 3. `ContactWithFollowUp` handles introduction and follow-up 1

Keep the existing event:

- **Introduction** (opportunity not yet Contact Sent): move to Contact Sent, note first email sent, create a reminder (+3 days at 09:00).
- **Follow-up 1** (opportunity already Contact Sent): note **Follow-up 1 sent**, create the last reminder.

Follow-up 2 **does not** dispatch this event. After a successful FU2 send: persist the email as a note, write that follow-up 2 was sent, and `moveToStage(NoResponse)`. Do not create a third reminder.

### 4. Send follow-up job

The Follow-ups index button is visible only when all of these are true:

- `reminder_status` is Pending
- the linked opportunity exists and is in `Contact Sent`

Click:

1. Mark the follow-up reminder completed, then dispatch `SendFollowUpEmailJob` (Horizon / Redis). Do **not** add an `AgentType`, orchestration wrapper, or extra domain agent.
2. The job decides FU1 vs FU2 from the **Follow-up 1 sent** note, calls `WriteFollowUpEmailAgent` with that `sequence_step`, persists subject and body as an opportunity note, and sends SMTP (same mail stack as first-contact outreach).
3. If FU1: dispatch `ContactWithFollowUp`. If FU2: note + move to **No Response**.

There is **no** draft preview or regenerate on this path (unlike first-contact). The click is the human confirmation to write and send.

If the opportunity is no longer `Contact Sent` when the job runs, refuse: do not send, do not move stage, leave the reminder as the user left it. Do **not** bulk-delete sequence reminders when the stage changes.

### 5. Pipeline: No Response is a terminal stage

Canonical **ordered** stages:

1. Lead
2. Qualification
3. Contact
4. Contact Sent
5. Meeting Scheduled
6. Proposal Generation
7. Proposal Analysis
8. Proposal Sent
9. Won
10. Lost
11. **No Response**
12. Disqualified

**No Response** (`no_response`):

- Terminal (`isTerminal()` true), same as Won, Lost, and Disqualified.
- Kanban column after Lost and before Disqualified.
- Color token: `neutral` (silence, not a lost negotiation and not a bad-fit skip).
- `OpportunityStatus::Lost` when entering this stage (closed, not Open).
- Does not require user action (not a “needs click” column).

Won / Lost / Disqualified behavior is unchanged. Disqualified remains the skip/unfit terminal used by prospecting.

```mermaid
flowchart TD
  contactSent[ContactSent]
  sendIntro[Human sends introduction]
  fu1[Reminder after introduction]
  sendFu1[Human Send follow-up]
  fu2[Reminder after follow-up 1]
  sendFu2[Human Send follow-up]
  noResponse[NoResponse terminal]

  sendIntro --> contactSent
  sendIntro --> fu1
  fu1 --> sendFu1
  sendFu1 --> fu2
  fu2 --> sendFu2
  sendFu2 --> noResponse
```

### 6. Copywriter contract

- Reuse first-contact tone: Gustavo Ferreira / Allison Hardy, Roger Pereira, Front Porch Creative.
- Same commercial problem as the introduction. Standalone emails (do not require reading the previous message).
- New unique subject each time (not `Re:`). One emoji in the subject; none in the body.
- Value drip: each follow-up uses a **new** insight and quick win. The copywriter receives the introduction `contact_example` as `previous_emails` (opportunity notes stay in the dossier).
- CTA for FU1 and FU2: reply with 3 dates and times for a 1-hour online discovery meeting.
- FU2 also states clearly that this is the **last** email.

Prompt asset: `docs/prompts/laravel_tools/write-follow-up-email.md`.

### Out of scope

- Auto-send when the reminder is due.
- Inbox, threads, `In-Reply-To`, bounce handling.
- Preview / regenerate of follow-up copy before send.
- Automatic completion or deletion of pending reminders when the opportunity leaves Contact Sent.

## Consequences

- **Positive:**
  - Humans stay in control of every client email.
  - Sequence progress is the **Follow-up 1 sent** note, not a column on the reminder.
  - Silence has a distinct terminal stage instead of overloading Lost or Disqualified.
- **Negative:**
  - Follow-up copy is not reviewed in-app before SMTP (the click is the approval).
  - ADR-005’s original eight-stage table is no longer the full list.
- **Neutral:**
  - First-contact still uses draft + Send on the opportunity AI panel.
  - Manual follow-ups remain available for calls and other work.

## References

- [21 Follow-up email sequence](../05%20-%20Feature%20List.md#f21-follow-up-email-sequence)
- [FDR-021](../FDRs/Done/FDR-021-follow-up-email-sequence.md)
- [ADR-005 Fixed sales pipeline](ADR-005-fixed-sales-pipeline.md)
- [ADR-019 Human-controlled proposal delivery](ADR-019-human-controlled-proposal-delivery.md)
- [ADR-017 First-contact email job](ADR-017-wave-4-ai-qualification-schema.md)
- [06 Follow-up management](../05%20-%20Feature%20List.md#f06-follow-up-management)
