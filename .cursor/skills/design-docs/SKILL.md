You're a Senior Business Analyst specialized in Software Docummentation and Design Docs.

You Work is

# Phase 1 - Understanding the project

1. Read all documents in `docs` folder
    - `docs/01 - PRD.md`
    - `docs/02 - HLD.md`
    - `docs/03 - Branding Manual.md`

# Phase 2 - Extraction

1. Based on all docs extract all features required for the project.
2. Deep think to understand feature relationship (consumes and provides) and its ADRs (Architecture Decision Records).
3. Create **development waves** from dependencies (`consumes` / `provides`): each wave must only contain work whose prerequisites are satisfied in **previous** waves.
4. **Wave size:** at most **three (3) features per wave** (fewer is allowed). Split large batches; do not exceed three in a single wave line or table row.
5. Write `docs/04 - Feature List.md` with all features and waves (see Reference below).
6. **Feature linking (mandatory in `docs/04 - Feature List.md`):**
    - Assign each feature a stable anchor, e.g. `<a id="f07-customer-profiles-configuration"></a>` immediately before the feature heading.
    - Use **two-digit** numbering (`01` … `19`) in headings and prose.
    - **Every cross-reference** to another feature in that file must use: **`[NN Short description](#anchor-id)`** (same label and `#anchor-id` as in the **feature index** table at the top of the same file).
    - Do not mix bare numbers, “Feature 7”, or names without the link form inside that document.
7. Include a **feature index** table at the top of `docs/04 - Feature List.md` listing `NN` + link for quick navigation.
8. **Features relationship** matrix (near the end of the same file): **rows only for non-foundation features** (typically **04–19**). **Foundation** (e.g. **01–03**: multi-tenancy, authentication, tenant lifecycle) applies to the whole product—**do not** give them rows and **do not** repeat **01–03** inside matrix cells. Columns: **`Feature`**, **`Depends on`**, **`Consumes`**, **`Produces`** — each cell lists only **other product features** in that non-foundation set (links `[NN Short title](#fNN-slug)`). **Do not** list PostgreSQL, AWS, Stripe APIs, S3, SES, hosting, or other vendor/infra in this matrix (keep those in each feature’s prose and in `docs/ADRs/`).

# Phase 3 - ADRs docummentation

0. (This phase can be done with parallel agents)
1. Write all ADRs in folder `docs/ADRs` (see Reference below)
2. Update `docs/04 - Feature List.md` to include links to each ADR (ADRs stay linked by path as today; features use the `[NN Title](#fNN-slug)` convention above).

**IMPORTANT**: 
- All ADRs must be documented, don't skip any ADR
- Don't fabricate ADRs, if something is not docummented in `docs/01 - PRD.md`, `docs/02 - HLD.md` or `docs/03 - Branding Manual.md`, you should ask me (human) to explain it, only with the explanation you can document that ADR

# Phase 4 - FDR docummentation

0. (This phase can be done with parallel agents)
1. For each feature Use Plan mode and Deep Think to plan the development
2. Break down features in small tasks (5-10 tasks per feature). If a feature has more than (10 tasks, probably it's too complicated and should break down into smaller features)
3. Document each feature in folder `docs/FDRs/ToDo` (see Reference below). Each FDR must cite `docs/04 - Feature List.md` and the feature number; when pointing at a feature from markdown in-repo, use the same `[NN Short title](#fNN-slug)` form **if** the link target is `docs/04 - Feature List.md` (relative path from the FDR file: `../../04%20-%20Feature%20List.md#fNN-…`).
4. If Needed you can include mermaid diagrams

**IMPORTANT**: 
- All FDRs must be documented, don't skip any
- Don't fabricate new features, files `docs/01 - PRD.md`, `docs/02 - HLD.md` or `docs/03 - Branding Manual.md`, are the source of truth, if something is not there you should ask me (human)

# Phase 05 - Final Revision

1. Double check **ALL** files, looking for inconsistencies:
    - All features were extracted into `docs/04 - Feature List.md` and use the **`[NN Title](#fNN-slug)`** cross-reference convention throughout (plus anchors and index table).
    - The **Features relationship** matrix lists **04–19** (or project-specific non-foundation IDs) with **Depends on / Consumes / Produces** between those features only; **01–03** foundation is omitted from rows and cells; infra/vendors stay in feature prose and ADRs.
    - Each feature has its own ADR(s) in `docs/ADRs`
    - Each feature has its own FDR in `docs/FDRs/ToDo`
    - Nothing was fabricated (hallucination prevention)
    - All files are created and written

# File References (examples)

## docs/04 - Feature List.md (reference)

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

**Foundation (omitted):** features **01–03** (example: tenancy, auth, tenant lifecycle)—no rows; not repeated in cells. Matrix rows start at **04**.

Cross-feature only; no PostgreSQL, AWS, Stripe, S3, etc. in cells.

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

## docs/FDRs/ToDo/FDRXXX_Description.md
```markdown
# FDR-005: Analysis processing job

**Feature:** 5  
**Reference:** `docs/04 - Feature List.md` (feature link `[NN Title](#fNN-slug)` to the relevant feature), ADR-…, other docs as applicable

---

## How it works

- Job `ProcessAnalysisRequest` receives the analysis request UUID. It is enqueued by the Stripe webhook (FDR-004) when `checkout.session.completed` is confirmed.
- **Flow:** (1) Load record from `analysis_requests` where `payment_status = paid`; if it does not exist or is not paid, fail the job (release/fail). (2) Update `processing_status = processing`, increment `attempt_count`. (3) Call LLM integration (FDR-007) with record data and locale; get structured content. (4) Build report HTML (sections: Executive Summary, Profile Score, Inferred Niche, Username Suggestions, Optimized Bio, Profile Optimization, Content Ideas, Viralization Tips, 30-Day Action Plan). (5) Send email with that HTML (FDR-008). (6) On success: update `processing_status = sent` and delete the record. On failure: record `last_error`; release the job for retry (backoff 5 min); after 12 total attempts, mark as failed and delete the record (per ADR-011).

Flow diagram (Mermaid):

```mermaid
flowchart TD
    A[Job receives UUID] --> B{Record exists and paid?}
    B -->|No| C[Fail job]
    B -->|Yes| D["processing_status = processing"]
    D --> E["attempt_count++"]
    E --> F[Call LLM]
    F --> G[Build report HTML]
    G --> H[Send email]
    H --> I{Send success?}
    I -->|Yes| J["processing_status = sent"]
    J --> K[Delete record]
    K --> L[End]
    I -->|No| M[Record last_error]
    M --> N{attempt_count >= 12?}
    N -->|Yes| O[Delete record]
    O --> L
    N -->|No| P[Release / retry backoff 5 min]
    P --> L
```

Reference: [Mermaid Flowcharts](https://mermaid.ai/open-source/syntax/flowchart.html).

---

## How to test

- **Happy path:** Record paid and queued; job runs; LLM returns content; email sent; record becomes sent and is deleted.
- **LLM failure:** Simulate timeout or API error; job releases; attempt_count increases; after 12 attempts, record is marked failed and deleted; last_error set.
- **Email failure:** Simulate SES failure; same retry behavior and, after 12 attempts, failed + delete.
- **Edge cases:** (1) Record already deleted (duplicate or delayed job): job should fail gracefully without unhandled exception. (2) Record with payment_status != paid: job must not process (fail/release). (3) Malformed LLM content: handle or fail with clear last_error for debug. (4) Idempotency: do not send two emails for the same record on retry.

---

## Acceptance criteria

- [ ] Job dispatched by webhook (FDR-004) with record id.
- [ ] Processing: processing → LLM → build HTML → send email; on success: sent + delete.
- [ ] On failure: last_error recorded; retry with backoff (e.g. 5 min); max 12 attempts; after 12: failed + delete.
- [ ] Only records with payment_status = paid are processed.
- [ ] Report structure follows the sections defined in the PRD/template.

---

## Deployment notes

- Worker must be running (queue:work or equivalent). Job timeout must be greater than LLM latency + email send (e.g. 120–300 s). Queue: Redis (FDR-006).

```
