# ADR-015: Prospecting discovery — AI-led public sources (MVP)

## Status

Accepted

## Approved decisions

| # | Decision | Chosen for MVP | Confirmed |
| - | -------- | -------------- | --------- |
| 1 | Discovery mechanism | **AI-led active prospecting** — the agent searches for leads autonomously, like an outbound salesperson, using only **public and free** sources. Behavior is bounded by a **stakeholder-approved system prompt** (to be supplied), applicable laws, and the ethics constraints below. Implementation uses a pluggable `DiscoveryAdapter` backed by the Prospecting Agent and Laravel AI SDK (no paid third-party **data** APIs). | ☑ |
| 2 | Lead deduplication | Treat as duplicate when **normalized company name** or **website domain** matches an existing lead. When present, also match **normalized email** and **phone** (secondary signals). | ☑ |
| 3 | Allowed data sources | **Public, free** sources only: Google (search), Google Maps (public listings/pages), business websites, social networks (e.g. Instagram, Facebook), local directories — as listed in PRD/HLD. **No paid data APIs** (e.g. paid Places/API tiers). | ☑ |

**Approver:** Rogerio Pereira · **Date:** 2026-05-29

### Ethics and compliance (product constraints)

- The agent must **not** break applicable laws, platform terms, or data-protection rules (**LGPD**, **GDPR**, and equivalent).
- **Sales tone:** subtle to moderate techniques allowed; **no** aggressive, intimidating, or high-pressure tactics.
- **Persuasion:** mental triggers may be used when appropriate to guide and influence the prospect, within the approved prompt and ethics bounds.
- **Human oversight:** commercial outreach to prospects remains subject to human approval elsewhere ([ADR-011](ADR-011-human-approval-commercial-actions.md)); this ADR covers **discovery and CRM registration** only.

### Explicitly out of scope for MVP

- Paid third-party lead/data APIs.
- Commitment to custom scraping infrastructure, browser automation, or crawling frameworks **unless** chosen later as the technical means to satisfy this ADR within legal/compliance bounds (implementation detail in FDR-010, not a separate product scope change).
- Autonomous client-facing send (email/DM) without human approval.

### Stakeholder deliverable (blocking full adapter)

- **Approved prospecting prompt** — versioned text provided by the product owner and referenced from implementation (path TBD; see action items in FDR-010).

## Context

The HLD documents possible lead sources (Google Maps, Instagram, Facebook, websites, directories) and states that the **browsing/navigation strategy remained undefined** in MVP. It forbade assuming browser automation, crawling, scraping, or paid integrations without a decision.

Stakeholder review (2026-05-29) closed that gap: discovery is **AI-led** on **public free sources**, with **no paid data APIs**, strict **privacy law** compliance, and an **approved prompt** governing agent behavior.

References:

- [HLD Prospecting Agent](../02%20HLD.md#prospecting-agent)
- [PRD Lead Collection](../01%20PRD.md#2-lead-collection)
- [FDR-010](../FDRs/ToDo/FDR-010-automated-prospecting.md)

## Decision

- **Accept** AI-led active prospecting as the MVP discovery mechanism, implemented via the Prospecting Agent and a concrete `DiscoveryAdapter` in feature 10.
- **Accept** deduplication rules: company name + website domain (primary); email and phone when available (secondary).
- **Accept** data sources: public/free web properties only; **no paid data APIs**.
- Record ethics, compliance, and prompt requirements in this ADR and mirror them in FDR-010.
- Schedule (weekdays 08:00) unchanged ([ADR-007](ADR-007-scheduled-prospecting.md)).

## Consequences

- **Positive:**
  - Clear product direction for FDR-010; Building can implement a real adapter (not only stub) once the approved prompt is available.
  - Aligns with HLD “commission-driven outbound” persona without mandating fragile paid data contracts.
- **Negative:**
  - Implementation must validate that the chosen AI provider/tools can access public information legally without paid data APIs; may require iteration on adapter design.
  - Legal/compliance review remains the owner’s responsibility; engineering encodes constraints but does not replace counsel.
- **Neutral:**
  - AI **inference** cost (OpenAI/Gemini via existing stack) is separate from “paid data APIs” and remains configured via `.env` ([ADR-002](ADR-002-ai-provider-abstraction.md)).
