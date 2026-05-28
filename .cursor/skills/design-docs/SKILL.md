---
name: design-docs
description: >
  Guides creation of product documentation—feature list, ADRs, and FDRs—from PRD/HLD/Branding.
  Use when bootstrapping or revising docs/ structure for a greenfield or existing project.
---

You're a Senior Business Analyst specialized in Software Documentation and Design Docs.

Your work:

# Phase 1 - Understanding the project

1. Read all documents in the `docs` folder relevant to the product, for example:
    - `docs/01 - PRD.md` (or `docs/01 PRD.md`)
    - `docs/02 - HLD.md`
    - `docs/03 - Branding Manual.md`
    - `docs/04 - Design System.md`
    - `docs/05 - Feature List.md`
    - `docs/ADRs/*`
    - `docs/FDRs/*`

# Phase 2 - Extraction

1. Based on all docs extract all features required for the project.
2. Deep think to understand feature relationship (consumes and provides) and its ADRs (Architecture Decision Records).
3. Create **development waves** from dependencies (`consumes` / `provides`): each wave must only contain work whose prerequisites are satisfied in **previous** waves.
4. **Wave size:** at most **three (3) features per wave** (fewer is allowed). Split large batches; do not exceed three in a single wave line or table row.
5. Write `docs/05 - Feature List.md` with all features and waves (see Reference below).
6. **Feature linking (mandatory in `docs/05 - Feature List.md`):**
    - Assign each feature a stable anchor, e.g. `<a id="f07-customer-profiles-configuration"></a>` immediately before the feature heading.
    - Use **three-digit** numbering (`01` … `NNN`) in headings and prose.
    - **Every cross-reference** to another feature in that file must use: **`[NN Short description](#anchor-id)`** (same label and `#anchor-id` as in the **feature index** table at the top of the same file).
    - Do not mix bare numbers, "Feature 7", or names without the link form inside that document.
7. Include a **feature index** table at the top of `docs/05 - Feature List.md` listing `NN` + link for quick navigation.
8. **Features relationship** matrix (near the end of the same file): **rows only for non-foundation features** (product-specific; exclude platform/auth/shell-type foundation features your project defines). **Foundation** features apply to the whole product — **do not** give them rows and **do not** repeat foundation IDs inside matrix cells. Columns: **`Feature`**, **`Depends on`**, **`Consumes`**, **`Produces`** — each cell lists only **other product features** in the non-foundation set (links `[NNN Short title](#fNNN-slug)`). **Do not** list databases, queues, third-party APIs, or hosting in this matrix (keep those in each feature's prose and in `docs/ADRs/`).

# Phase 3 - ADRs documentation

0. (This phase can be done with parallel agents)
1. Write all ADRs in folder `docs/ADRs` (see Reference below)
2. Update `docs/05 - Feature List.md` to include links to each ADR (ADRs stay linked by path as today; features use the `[NN Title](#fNN-slug)` convention above).

**IMPORTANT**:
- All ADRs must be documented, don't skip any ADR
- Don't fabricate ADRs, if something is not documented in PRD, HLD, or Branding, ask the human to explain it; only then document that ADR

# Phase 4 - FDR documentation

0. (This phase can be done with parallel agents)
1. For each feature use Plan mode and deep thinking to plan the development
2. Break down features in small tasks (5-10 tasks per feature). If a feature has more than 10 tasks, it is probably too complicated and should be break down into smaller features
3. Document each feature in folder `docs/FDRs/ToDo` (see Reference below). Each FDR must cite `docs/05 - Feature List.md` and the feature number; when pointing at a feature from markdown in-repo, use the same `[NN Short title](#fNN-slug)` form **if** the link target is `docs/05 - Feature List.md` (relative path from the FDR file: `../../05%20-%20Feature%20List.md#fNN-…`).
4. If needed you can include mermaid diagrams

**IMPORTANT**:
- All FDRs must be documented, don't skip any
- Don't fabricate new features; PRD, HLD, and Branding are the source of truth—if something is not there, ask the human

# Phase 05 - Final Revision

1. Double check **ALL** files, looking for inconsistencies:
    - All features were extracted into `docs/05 - Feature List.md` and use the **`[NN Title](#fNN-slug)`** cross-reference convention throughout (plus anchors and index table).
    - The **Features relationship** matrix lists non-foundation features with **Depends on / Consumes / Produces** between those features only; foundation features are omitted from rows and cells; infra/vendors stay in feature prose and ADRs.
    - Each feature has its own ADR(s) in `docs/ADRs`
    - Each feature has its own FDR in `docs/FDRs/ToDo`
    - Nothing was fabricated (hallucination prevention)
    - All files are created and written

# File References (examples)

## docs/05 - Feature List.md (reference)

```markdown
# <Project> — Feature List

**Version:** 1.0  
**Date:** YYYY-MM-DD  
**References:** PRD, HLD, Branding Manual, ADRs

**Convention:** Every cross-reference to a feature uses `[NN Short title](#fNN-slug)` (anchors and index below).

---

<a id="feature-index"></a>

## Feature index

| NN | Link |
| -- | ---- |
| 01 | [01 Short title](#f01-slug-example) |
| 02 | [02 Short title](#f02-slug-example) |

---

## Features

<a id="f01-slug-example"></a>

### 01 · Short title

**Objective:** …

**Dependencies:** —

**Related to:** [02 Short title](#f02-slug-example)

**Consumes:**

- [02 Short title](#f02-slug-example) — what it consumes

**Produces:**

- …

**ADRs:** [ADR-001](ADRs/ADR-001_….md)

---

<a id="f02-slug-example"></a>

### 02 · Short title
…

---

## Features relationship

**Foundation (omitted):** e.g. features **01–03** (platform, auth, shell/design system) — no rows; not repeated in cells. Matrix rows start at the first non-foundation feature ID for your project.

Cross-feature only; no PostgreSQL, Redis, external APIs, etc. in cells.

| Feature | Depends on | Consumes | Produces |
| ------- | ---------- | -------- | -------- |
| [04 Short title](#f04-slug-example) | — | — | [05 Short title](#f05-slug-example) |
| [05 Short title](#f05-slug-example) | [04 Short title](#f04-slug-example) | [04 Short title](#f04-slug-example) | … |

---

## Development waves

At most **three** features per wave. Example:

| Wave | Features |
| ---- | -------- |
| **1** | [01 Short title](#f01-slug-example), [02 Short title](#f02-slug-example), [03 Short title](#f03-slug-example) |
| **2** | [04 Short title](#f04-slug-example), [05 Short title](#f05-slug-example) |

```

## docs/ADRs/ADRXXX_Description.md

- Use mermaid diagrams when necessary to explain complex workflows

```markdown
# ADR-001: Description

## Status

Approved

## Context

Description.

## Decision

Decision

References:
- [Link 1](https://test.com)

## Consequences

- **Positive:** 
    - Consequence
    - Consequence
- **Negative:** 
    - Consequence
    - Consequence
- **Neutral:** 
    - Consequence
    - Consequence

```

## docs/FDRs/ToDo/FDR-XXX-description.md (example)

```markdown
# FDR-011: Automated lead qualification

**Feature:** 11  
**Reference:** [11 Automated lead qualification](../../05%20-%20Feature%20List.md#f11-automated-lead-qualification), [ADR-003](../../ADRs/ADR-003-ai-orchestration-architecture.md)

---

## How it works

- When a lead is created or updated, dispatch `QualifyLeadJob` to the Redis queue.
- Job loads the lead, calls the AI orchestration service (ADR-003), persists qualification notes and score, and records failures for retry per ADR-006.

---

## How to test

- **Happy path:** Lead queued; job runs; qualification fields persisted.
- **AI failure:** Transient error triggers retry; permanent failure logged without blocking HTTP.
- **Edge cases:** Missing lead id; duplicate job idempotency.

---

## Acceptance criteria

- [ ] Job dispatched on defined triggers.
- [ ] Qualification results stored on the lead record.
- [ ] Retries and Horizon visibility per ADR-006.

---

## Deployment notes

- Redis queue workers and Horizon running. Tune job timeout for LLM latency.

```
