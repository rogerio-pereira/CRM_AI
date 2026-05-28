# ADR-004: Unified lead/client entity

## Status

Accepted

## Context

The PRD states that leads and clients are treated as the **same entity** throughout the system lifecycle. The HLD CRM domain repeats this model with shared attributes (company, contacts, website, social links, source, qualification notes, AI insights, histories).

## Decision

- Model **one** `Lead/Client` entity (single table or equivalent aggregate) rather than separate lead and customer tables with conversion workflows in MVP.
- Lifecycle is expressed through **pipeline stage**, **qualification state**, and **user actions** (contact, archive, ignore) — not through entity type changes.
- UI presents “Leads / Clients” as one module (table + detail modal per PRD and Design System).

References:

- [PRD Core Entities — Lead / Client](../01%20PRD.md#lead--client)
- [HLD §4 CRM Domain](../02%20HLD.md#4-core-domains)

## Consequences

- **Positive:**
  - Simpler data model and UI; matches internal operational reality.
  - AI enrichment writes to one record; history stays centralized.
- **Negative:**
  - If future B2B customer hierarchies are needed, model may require extension.
- **Neutral:**
  - “Won” opportunities still link to the same client record.
