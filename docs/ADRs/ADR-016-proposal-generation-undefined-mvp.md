# ADR-016: Proposal generation format undefined in MVP

## Status

Proposed

## Pending approval

This ADR records an **intentional gap** in the HLD. It must be reviewed and **Accepted** before implementing proposal output/storage in [FDR-013](../FDRs/ToDo/FDR-013-proposal-assistance.md) and the Proposal Generation branch in [FDR-014](../FDRs/ToDo/FDR-014-pipeline-stage-automation.md).

| # | Decision | Options (examples) | Confirmed |
| - | -------- | ------------------ | --------- |
| 1 | Proposal output format | Markdown in CRM, structured sections (JSON), copy-to-clipboard only, external doc link | ☐ |
| 2 | Storage model | Single text field, multi-section JSON, versioned drafts | ☐ |
| 3 | In-scope for MVP | Assistance text only vs export (PDF/DOCX explicitly out unless approved) | ☐ |

**Approver:** _pending_ · **Date:** _pending_

## Context

The HLD states that **proposal generation implementation remains undefined** in MVP and lists non-assumptions: proposal format, PDF generation, template engines, e-signature, export workflows.

## Decision

- MVP delivers **proposal assistance** (analysis, recommendations, draft support) via the Proposal Assistant agent—not a full document lifecycle product.
- **No assumption** of PDF engine, templates, or e-signature in architecture.
- Output format (markdown in CRM, external doc link, copy-paste block, etc.) to be defined in [feature 13](../05%20-%20Feature%20List.md#f13-proposal-assistance) FDR before implementation.
- Human approval required before any client-facing send ([ADR-011](ADR-011-human-approval-commercial-actions.md)).

References:

- [HLD Proposal Assistant Agent](../02%20HLD.md#proposal-assistant-agent)
- [PRD Proposal Assistance](../01%20PRD.md#7-proposal-assistance)

## Consequences

- **Positive:**
  - Scope stays aligned with “assist” not “document management system.”
- **Negative:**
  - Users may need external tools (Docs/Word) for final formatting initially.
- **Neutral:**
  - Pipeline stages Proposal Generation / Analysis / Sent still apply for workflow tracking.
