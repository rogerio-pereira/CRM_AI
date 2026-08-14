# ADR-019: Human-controlled proposal delivery

## Status

Accepted (2026-08-14)

## Supersedes

[ADR-011](ADR-011-human-approval-commercial-actions.md).

ADR-011 established human approval and prohibited autonomous outreach, but treated email automation as outside the MVP. This ADR preserves those safety rules while explicitly allowing human-confirmed delivery of approved proposal artifacts through the CRM.

## Context

The CRM must let a user download approved proposal PDFs and send them through configured email infrastructure. This is artifact delivery, not autonomous prospecting, conversational email automation, or an AI-triggered outbound action.

Local development already provides Laravel Mail with Mailpit. Production uses configurable SMTP through standard Laravel mail environment variables.

## Decision

1. AI outputs remain recommendations/drafts until a user explicitly reviews and approves them.
2. Users remain responsible for deciding whether to contact, archive, schedule follow-up, approve a proposal, and perform any client-facing action.
3. Pipeline stages **Contact** and **Proposal Analysis** remain human-driven.
4. AI must never send proposals, emails, or messages autonomously.
5. After approval, a user may:
   - Download generated proposal PDFs.
   - Open a send form in the CRM.
   - Edit recipients, subject, and body.
   - Explicitly confirm SMTP delivery with selected PDF attachments.
6. Default recipient may use the client contact email when available.
7. Only after a successful confirmed send does the opportunity move to **Proposal Sent**.
8. Failed email delivery leaves the opportunity stage unchanged and does not corrupt proposal data.
9. AI-generated content remains labeled in the UI.

### Out of scope

- Autonomous outreach.
- Gmail-specific API integration.
- Email sequences, inbox synchronization, or conversation threads.
- Electronic signature.

## Consequences

- **Positive:**
  - Keeps the user in control of every client-facing action.
  - Allows approved proposal delivery without leaving the CRM.
- **Negative:**
  - Requires an explicit review/confirmation step.
- **Neutral:**
  - Day-to-day negotiation may still happen outside the system.
  - Laravel Mail/SMTP remains provider-neutral; Mailpit supports local testing.

## References

- [ADR-011 Superseded human approval decision](ADR-011-human-approval-commercial-actions.md)
- [ADR-018 Proposal domain and artifacts](ADR-018-proposal-artifact-rendering-and-delivery.md)
- [FDR-020 Proposal artifacts and delivery](../FDRs/ToDo/FDR-020-proposal-artifacts-and-delivery.md)
