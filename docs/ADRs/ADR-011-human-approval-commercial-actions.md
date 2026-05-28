# ADR-011: Human approval for commercial actions

## Status

Accepted

## Context

The PRD states that human approval is required before execution of sales actions. AI agents may analyze, suggest, and recommend—but users decide contact, archive, schedule follow-up, and send proposals. Conversations happen outside the system.

## Decision

- AI outputs are **recommendations only** until a user explicitly acts.
- **No autonomous** sending of proposals, emails, or messages to leads in MVP.
- UI must **label AI-generated content** and not present it as confirmed human decisions (Design System §17).
- Pipeline stages **Contact** and **Proposal Analysis** are explicitly human-driven per HLD.

References:

- [PRD Automation Rules](../01%20PRD.md#automation-rules)
- [PRD Main Workflow §6–7](../01%20PRD.md#6-human-interaction)
- [HLD Architectural Principles](../02%20HLD.md#14-architectural-principles)

## Consequences

- **Positive:**
  - Reduces legal/reputation risk from autonomous outreach.
  - Clear UX contract for internal users.
- **Negative:**
  - Less “full autopilot” than some AI CRM products.
- **Neutral:**
  - Future email automation remains out of MVP scope per PRD.
