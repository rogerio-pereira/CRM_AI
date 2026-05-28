# ADR-015: Prospecting discovery mechanism undefined in MVP

## Status

Proposed

## Pending approval

This ADR records an **intentional gap** in the HLD. It must be reviewed and **Accepted** before implementing the discovery adapter in [FDR-010](../FDRs/ToDo/FDR-010-automated-prospecting.md).

| # | Decision | Options (examples) | Confirmed |
| - | -------- | ------------------ | --------- |
| 1 | Technical discovery mechanism | Official APIs, curated seed list, manual-assisted research, other (explicitly **not** chosen in docs) | ☐ |
| 2 | Lead deduplication rules | By domain, company name, both, fuzzy match threshold | ☐ |
| 3 | Allowed data sources for MVP | Subset of PRD list (Maps, Instagram, etc.) | ☐ |

**Approver:** _pending_ · **Date:** _pending_

## Context

The HLD documents possible lead sources (Google Maps, Instagram, Facebook, websites, directories) and states that the **browsing/navigation strategy remains undefined** in MVP. It explicitly forbids assuming browser automation, crawling frameworks, scraping engines, or specialized integrations without a separate decision.

## Decision

- **Document** that MVP accepts prospecting agent behavior but **does not prescribe** the technical discovery mechanism in architecture docs.
- Implementation of discovery (APIs, manual seed lists, assisted research flows, etc.) must be chosen during [feature 10](../05%20-%20Feature%20List.md#f10-automated-prospecting) delivery and recorded in the FDR—not invented silently in code.
- Until decided, agents may use **pluggable discovery interfaces** with a minimal viable adapter agreed with stakeholders.

References:

- [HLD Prospecting Agent](../02%20HLD.md#prospecting-agent)
- [PRD Lead Collection](../01%20PRD.md#2-lead-collection)

## Consequences

- **Positive:**
  - Avoids premature commitment to fragile scraping infrastructure.
- **Negative:**
  - Feature 10 cannot be fully estimated until discovery approach is chosen.
- **Neutral:**
  - Schedule (weekdays 08:00) still applies regardless of discovery mechanism ([ADR-007](ADR-007-scheduled-prospecting.md)).
