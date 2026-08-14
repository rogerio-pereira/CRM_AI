# Internal AI-Assisted CRM — Feature List

**Version:** 1.1
**Date:** 2026-08-14
**References:** [PRD](01%20PRD.md), [HLD](02%20HLD.md), [Branding Manual](03%20-%20Branding%20Manual.md), [Design System](04%20-%20Design%20System.md), [ADRs](ADRs/)

**Convention:** Every cross-reference to a feature in this file uses `[NN Short title](#fNN-slug)`.

**FDR locations:** Active specs live in `docs/FDRs/ToDo/`; completed features in `docs/FDRs/Done/`; archived or superseded specs in `docs/FDRs/Closed/`.

**ADR status:** [ADR-016](ADRs/ADR-016-proposal-generation-undefined-mvp.md) is **Superseded** by [ADR-018](ADRs/ADR-018-proposal-artifact-rendering-and-delivery.md), which defines the proposal domain, generation, templates, and PDFs. [ADR-011](ADRs/ADR-011-human-approval-commercial-actions.md) is **Superseded** by [ADR-019](ADRs/ADR-019-human-controlled-proposal-delivery.md), which defines confirmed SMTP delivery without autonomous send. [ADR-020](ADRs/ADR-020-commercial-service-catalog-boundary.md) partially supersedes the service-source wording in [ADR-017](ADRs/ADR-017-wave-4-ai-qualification-schema.md): `docs/services/` are qualification categories and priced sellable items live in the commercial catalog ([18](#f18-commercial-service-catalog)). [ADR-015](ADRs/ADR-015-prospecting-discovery-undefined-mvp.md) remains **Accepted** (2026-05-29). Feature [10](#f10-automated-prospecting) implements ADR-015; [11](#f11-automated-lead-qualification) follows ADR-017 (see [FDR-011](FDRs/Done/FDR-011-automated-lead-qualification.md)).

---

<a id="feature-index"></a>

## Feature index

| NN | Feature | FDR |
| -- | ------- | --- |
| 01 | [01 Platform foundation](#f01-platform-foundation) | [FDR-001](FDRs/Done/FDR-001-platform-foundation.md) |
| 02 | [02 Authentication](#f02-authentication) | [FDR-002](FDRs/Done/FDR-002-authentication.md) |
| 03 | [03 Application shell and design system](#f03-application-shell-design-system) | [FDR-003](FDRs/Done/FDR-003-application-shell-design-system.md) |
| 04 | [04 Lead and client management](#f04-lead-client-management) | [FDR-004](FDRs/Done/FDR-004-lead-client-management.md) |
| 05 | [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) | [FDR-005](FDRs/Done/FDR-005-opportunity-kanban-pipeline.md) |
| 06 | [06 Follow-up management](#f06-follow-up-management) | [FDR-006](FDRs/Done/FDR-006-follow-up-management.md) |
| 07 | [07 Task management](#f07-task-management) | [FDR-007](FDRs/Done/FDR-007-task-management.md) |
| 08 | [08 Operational dashboard](#f08-operational-dashboard) | [FDR-008](FDRs/Done/FDR-008-operational-dashboard.md) |
| 09 | [09 AI provider layer and orchestration](#f09-ai-orchestration) | [FDR-009](FDRs/Done/FDR-009-ai-orchestration.md) |
| 10 | [10 Automated prospecting](#f10-automated-prospecting) | [FDR-010](FDRs/Done/FDR-010-automated-prospecting.md) |
| 11 | [11 Automated lead qualification](#f11-automated-lead-qualification) | [FDR-011](FDRs/Done/FDR-011-automated-lead-qualification.md) |
| 12 | [12 AI recommendations and insights](#f12-ai-recommendations) | [FDR-012](FDRs/Done/FDR-012-ai-recommendations.md) |
| 13 | [13 Proposal assistance](#f13-proposal-assistance) | [FDR-013](FDRs/ToDo/FDR-013-proposal-assistance.md) |
| 14 | [14 Pipeline stage-based automation](#f14-pipeline-stage-automation) | [FDR-014](FDRs/ToDo/FDR-014-pipeline-stage-automation.md) |
| 15 | [15 Slack notifications](#f15-slack-notifications) | [FDR-015](FDRs/ToDo/FDR-015-slack-notifications.md) |
| 16 | [16 Google Calendar integration](#f16-google-calendar) | [FDR-016](FDRs/ToDo/FDR-016-google-calendar.md) |
| 17 | [17 Integration settings](#f17-integration-settings) **Closed** | [FDR-017](FDRs/Closed/FDR-017-integration-settings.md) |
| 18 | [18 Commercial service catalog](#f18-commercial-service-catalog) | [FDR-018](FDRs/Done/FDR-018-commercial-service-catalog.md) |
| 19 | [19 Opportunity notes](#f19-opportunity-notes) | [FDR-019](FDRs/ToDo/FDR-019-opportunity-notes.md) |
| 20 | [20 Proposal artifacts and delivery](#f20-proposal-artifacts-and-delivery) | [FDR-020](FDRs/ToDo/FDR-020-proposal-artifacts-and-delivery.md) |

---

## Features

<a id="f01-platform-foundation"></a>

### 01 · Platform foundation

**Objective:** Establish the core runtime stack (Laravel 13, PostgreSQL, Redis queues, Horizon, Sail, Laravel Cloud baseline) so all domains and agents can run reliably.

**Dependencies:** —

**Related to:** [02 Authentication](#f02-authentication), [09 AI provider layer and orchestration](#f09-ai-orchestration)

**Consumes:** —

**Produces:**

- Database connectivity and migrations baseline
- Redis queue workers and Horizon monitoring
- Scheduled command infrastructure
- Deployment baseline for Laravel Cloud

**ADRs:** [ADR-001](ADRs/ADR-001-technology-stack.md), [ADR-006](ADRs/ADR-006-queue-async-processing.md), [ADR-014](ADRs/ADR-014-dashboard-observability-scope.md)

---

<a id="f02-authentication"></a>

### 02 · Authentication

**Objective:** Enable multiple internal users to access the CRM with standard Laravel authentication (login, session, password flows).

**Dependencies:** [01 Platform foundation](#f01-platform-foundation)

**Related to:** [03 Application shell and design system](#f03-application-shell-design-system)

**Consumes:**

- [01 Platform foundation](#f01-platform-foundation) — app runtime, database, session

**Produces:**

- Authenticated user sessions for all protected UI and APIs

**ADRs:** [ADR-008](ADRs/ADR-008-authentication-internal-users.md)

---

<a id="f03-application-shell-design-system"></a>

### 03 · Application shell and design system

**Objective:** Deliver the dark-mode CRM shell (sidebar, header, navigation, design tokens, Tailwind mapping) per Branding Manual and Design System.

**Dependencies:** [02 Authentication](#f02-authentication)

**Related to:** All UI features (04–08)

**Consumes:**

- [02 Authentication](#f02-authentication) — user menu, protected layout

**Produces:**

- Reusable layout, navigation, and UI tokens for CRM screens

**ADRs:** [ADR-013](ADRs/ADR-013-dark-mode-design-system.md)

---

<a id="f04-lead-client-management"></a>

### 04 · Lead and client management

**Objective:** CRUD and table/modal views for leads and clients as a unified entity, including history and AI insight placeholders.

**Dependencies:** [03 Application shell and design system](#f03-application-shell-design-system)

**Related to:** [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [10 Automated prospecting](#f10-automated-prospecting), [11 Automated lead qualification](#f11-automated-lead-qualification)

**Consumes:**

- [03 Application shell and design system](#f03-application-shell-design-system) — tables, modals, forms

**Produces:**

- Lead/client records usable by pipeline, AI agents, and follow-ups

**ADRs:** [ADR-004](ADRs/ADR-004-unified-lead-client-entity.md)

---

<a id="f05-opportunity-kanban-pipeline"></a>

### 05 · Opportunity management and Kanban pipeline

**Objective:** Manage commercial opportunities on a fixed eight-stage Kanban board with stage colors and drag/move between stages.

**Dependencies:** [04 Lead and client management](#f04-lead-client-management)

**Related to:** [14 Pipeline stage-based automation](#f14-pipeline-stage-automation), [13 Proposal assistance](#f13-proposal-assistance)

**Consumes:**

- [04 Lead and client management](#f04-lead-client-management) — related client on each opportunity

**Produces:**

- Opportunities in pipeline stages (Lead → Lost/Won)
- Stage change events for automation

**ADRs:** [ADR-005](ADRs/ADR-005-fixed-sales-pipeline.md)

---

<a id="f06-follow-up-management"></a>

### 06 · Follow-up management

**Objective:** Schedule and track follow-ups linked to clients and opportunities (due date, priority, notes, reminder status).

**Dependencies:** [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline)

**Related to:** [15 Slack notifications](#f15-slack-notifications), [16 Google Calendar integration](#f16-google-calendar)

**Consumes:**

- [04 Lead and client management](#f04-lead-client-management)
- [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline)

**Produces:**

- Follow-up records for dashboard, Slack, and Calendar

**ADRs:** [ADR-009](ADRs/ADR-009-slack-integration.md), [ADR-010](ADRs/ADR-010-google-calendar-integration.md)

---

<a id="f07-task-management"></a>

### 07 · Task management

**Objective:** Create and manage important tasks linked to clients/opportunities, surfaced on the dashboard and in notifications.

**Dependencies:** [04 Lead and client management](#f04-lead-client-management)

**Related to:** [08 Operational dashboard](#f08-operational-dashboard), [15 Slack notifications](#f15-slack-notifications)

**Consumes:**

- [04 Lead and client management](#f04-lead-client-management)
- Optional: [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline)

**Produces:**

- Task records for dashboard, Slack, and Calendar

**ADRs:** [ADR-009](ADRs/ADR-009-slack-integration.md), [ADR-010](ADRs/ADR-010-google-calendar-integration.md)

---

<a id="f08-operational-dashboard"></a>

### 08 · Operational dashboard

**Objective:** Centralize operational visibility with metric cards, 30-day charts, and pending tasks/follow-ups tables.

**Dependencies:** [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management)

**Consumes:**

- [04 Lead and client management](#f04-lead-client-management) — lead metrics
- [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) — opportunity and sales metrics
- [06 Follow-up management](#f06-follow-up-management) — follow-ups table
- [07 Task management](#f07-task-management) — pending tasks table

**Produces:**

- Operational KPIs and actionable lists for internal users

**ADRs:** [ADR-014](ADRs/ADR-014-dashboard-observability-scope.md)

---

<a id="f09-ai-orchestration"></a>

### 09 · AI provider layer and orchestration

**Objective:** Implement Laravel AI SDK abstraction, configurable OpenAI/Gemini providers, and a central orchestration service dispatching responsibility-specific agents via events/queues.

**Dependencies:** [01 Platform foundation](#f01-platform-foundation)

**Related to:** [10 Automated prospecting](#f10-automated-prospecting) through [13 Proposal assistance](#f13-proposal-assistance)

**Consumes:**

- [01 Platform foundation](#f01-platform-foundation) — Redis queues, configuration

**Produces:**

- AI invocation layer and orchestration hooks for all agents

**ADRs:** [ADR-002](ADRs/ADR-002-ai-provider-abstraction.md), [ADR-003](ADRs/ADR-003-ai-orchestration-architecture.md), [ADR-006](ADRs/ADR-006-queue-async-processing.md)

---

<a id="f10-automated-prospecting"></a>

### 10 · Automated prospecting

**Objective:** Run prospecting agents on weekdays at 08:00 to discover leads from public sources and register them in the CRM qualification queue.

**Dependencies:** [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration)

**Consumes:**

- [04 Lead and client management](#f04-lead-client-management) — persist discovered leads
- [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) — initial Lead stage
- [09 AI provider layer and orchestration](#f09-ai-orchestration) — prospecting agent

**Produces:**

- New leads in pipeline stage Lead
- New opportunities that enter the qualification queue

**ADRs:** [ADR-003](ADRs/ADR-003-ai-orchestration-architecture.md), [ADR-007](ADRs/ADR-007-scheduled-prospecting.md), [ADR-015](ADRs/ADR-015-prospecting-discovery-undefined-mvp.md) (**Accepted** — AI-led discovery on public/free sources; see [FDR-010](FDRs/Done/FDR-010-automated-prospecting.md))

**Implementation note:** Discovery per ADR-015: compliant in-repo scraping on public/free sources allowed; **approved prospecting prompt** required before production; no paid data APIs; no external unmanaged discovery code.

---

<a id="f11-automated-lead-qualification"></a>

### 11 · Automated lead qualification

**Objective:** Qualify each **opportunity** asynchronously when it is created: digital presence analysis, pain points, CRM enrichment for that deal, stage advancement after analysis. The same client may have many opportunities over time; each is qualified independently.

**Dependencies:** [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration)

**Consumes:**

- [04 Lead and client management](#f04-lead-client-management)
- [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) — Qualification stage
- [09 AI provider layer and orchestration](#f09-ai-orchestration) — qualification agent

**Produces:**

- Updated opportunity records with qualification notes and AI insights
- Stage moves after qualification (per pipeline rules) for **that** opportunity

**ADRs:** [ADR-003](ADRs/ADR-003-ai-orchestration-architecture.md), [ADR-006](ADRs/ADR-006-queue-async-processing.md), [ADR-015](ADRs/ADR-015-prospecting-discovery-undefined-mvp.md) (**Accepted**, via prospecting), [ADR-017](ADRs/ADR-017-wave-4-ai-qualification-schema.md) (**Accepted**, amended 2026-08-13 and 2026-08-14 — see [FDR-011](FDRs/Done/FDR-011-automated-lead-qualification.md))

**Implementation note:** Per [ADR-017](ADRs/ADR-017-wave-4-ai-qualification-schema.md), all created **opportunities** are qualified automatically. Prospecting creates **one** opportunity and that initial job scores every file in `docs/services/` (no extra opportunities per service). Successful qualification advances **that** opportunity to **Contact**. A later deal on the same client is qualified again as that opportunity only.

---

<a id="f12-ai-recommendations"></a>

### 12 · AI recommendations and insights

**Objective:** Generate outreach suggestions, next-step recommendations, opportunity analysis, and labeled AI insight panels for **qualified opportunities**.

**Dependencies:** [04 Lead and client management](#f04-lead-client-management), [11 Automated lead qualification](#f11-automated-lead-qualification)

**Consumes:**

- [11 Automated lead qualification](#f11-automated-lead-qualification) — qualification context
- [04 Lead and client management](#f04-lead-client-management) — display on lead/opportunity UI

**Produces:**

- AI-generated summaries, pain points, strategies (human-reviewed)

**ADRs:** [ADR-003](ADRs/ADR-003-ai-orchestration-architecture.md), [ADR-019](ADRs/ADR-019-human-controlled-proposal-delivery.md), [ADR-013](ADRs/ADR-013-dark-mode-design-system.md)

---

<a id="f13-proposal-assistance"></a>

### 13 · Proposal assistance

**Objective:** Persist one commercial proposal per opportunity, recommend catalog services and values via AI, let humans edit line items, and record explicit proposal approval before artifact generation.

**Dependencies:** [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration), [11 Automated lead qualification](#f11-automated-lead-qualification), [18 Commercial service catalog](#f18-commercial-service-catalog), [19 Opportunity notes](#f19-opportunity-notes)

**Consumes:**

- [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) — Proposal Generation / Analysis stages
- [09 AI provider layer and orchestration](#f09-ai-orchestration) — proposal assistant agent
- [11 Automated lead qualification](#f11-automated-lead-qualification) — analysis data
- [18 Commercial service catalog](#f18-commercial-service-catalog) — sellable line items and default prices
- [19 Opportunity notes](#f19-opportunity-notes) — timeline context for recommendations

**Produces:**

- Approved proposal record with line items (input to artifact delivery)

**ADRs:** [ADR-018](ADRs/ADR-018-proposal-artifact-rendering-and-delivery.md), [ADR-019](ADRs/ADR-019-human-controlled-proposal-delivery.md), [ADR-003](ADRs/ADR-003-ai-orchestration-architecture.md)

---

<a id="f14-pipeline-stage-automation"></a>

### 14 · Pipeline stage-based automation

**Objective:** On pipeline stage changes, trigger configured actions: AI jobs, follow-ups, tasks, and notification hooks (without assuming job chaining in MVP), including real Proposal Generation recommendation dispatch.

**Dependencies:** [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management), [09 AI provider layer and orchestration](#f09-ai-orchestration), [13 Proposal assistance](#f13-proposal-assistance), [20 Proposal artifacts and delivery](#f20-proposal-artifacts-and-delivery)

**Consumes:**

- [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) — stage change events
- [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management) — auto-create records
- [09 AI provider layer and orchestration](#f09-ai-orchestration) — stage-triggered agents
- [13 Proposal assistance](#f13-proposal-assistance) — ensure/recommend on Proposal Generation
- [20 Proposal artifacts and delivery](#f20-proposal-artifacts-and-delivery) — respects human send as source of Proposal Sent

**Produces:**

- Automated follow-ups/tasks and dispatched AI jobs per stage rules
- Optional hooks consumed by [15 Slack notifications](#f15-slack-notifications)

**ADRs:** [ADR-003](ADRs/ADR-003-ai-orchestration-architecture.md), [ADR-005](ADRs/ADR-005-fixed-sales-pipeline.md), [ADR-018](ADRs/ADR-018-proposal-artifact-rendering-and-delivery.md), [ADR-019](ADRs/ADR-019-human-controlled-proposal-delivery.md)

---

<a id="f15-slack-notifications"></a>

### 15 · Slack notifications

**Objective:** Send simple operational messages to a single Slack channel when user action is required (pending follow-ups, critical tasks, unanswered proposals after Proposal Sent).

**Dependencies:** [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management), [08 Operational dashboard](#f08-operational-dashboard)

**Consumes:**

- [06 Follow-up management](#f06-follow-up-management)
- [07 Task management](#f07-task-management)
- Optional: [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) — Proposal Sent reminders
- Optional: [20 Proposal artifacts and delivery](#f20-proposal-artifacts-and-delivery) — send/stage timing for unanswered proposals

**Produces:**

- Slack messages for actionable operational events

**ADRs:** [ADR-009](ADRs/ADR-009-slack-integration.md), [ADR-012](ADRs/ADR-012-integration-failure-isolation.md)

---

<a id="f16-google-calendar"></a>

### 16 · Google Calendar integration

**Objective:** Create Google Calendar events for follow-ups and important tasks via API wrapper (single company calendar, no bidirectional sync).

**Dependencies:** [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management)

**Consumes:**

- [06 Follow-up management](#f06-follow-up-management)
- [07 Task management](#f07-task-management)

**Produces:**

- External calendar events for scheduled actions

**ADRs:** [ADR-010](ADRs/ADR-010-google-calendar-integration.md), [ADR-012](ADRs/ADR-012-integration-failure-isolation.md)

---

<a id="f17-integration-settings"></a>

### 17 · Integration settings

**Status:** **Closed** — not implemented. Integration parameters (AI provider, Slack, Google Calendar) are configured via `.env` and deployment secrets instead of an in-app Settings UI. See [FDR-017](FDRs/Closed/FDR-017-integration-settings.md).

**Objective:** *(Superseded)* Settings UI for Slack, Google Calendar, and AI provider configuration (credentials, channel, calendar ID, provider selection).

**Dependencies:** —

**Consumes:** —

**Produces:** —

**ADRs:** [ADR-002](ADRs/ADR-002-ai-provider-abstraction.md), [ADR-009](ADRs/ADR-009-slack-integration.md), [ADR-010](ADRs/ADR-010-google-calendar-integration.md)

---

<a id="f18-commercial-service-catalog"></a>

### 18 · Commercial service catalog

**Objective:** Maintain a global catalog of sellable services (name, description, default price, category aligned with `docs/services/` briefs) for proposal line items.

**Dependencies:** [03 Application shell and design system](#f03-application-shell-design-system)

**Related to:** [13 Proposal assistance](#f13-proposal-assistance), [11 Automated lead qualification](#f11-automated-lead-qualification)

**Consumes:**

- Qualification category identifiers from `docs/services/` (documentation SoT for categories; not auto-synced)

**Produces:**

- Priced catalog rows consumed by [13 Proposal assistance](#f13-proposal-assistance)

**ADRs:** [ADR-018](ADRs/ADR-018-proposal-artifact-rendering-and-delivery.md), [ADR-020](ADRs/ADR-020-commercial-service-catalog-boundary.md)

---

<a id="f19-opportunity-notes"></a>

### 19 · Opportunity notes

**Objective:** Provide an author/timestamp timeline of internal notes on each opportunity for human context and AI proposal recommendations.

**Dependencies:** [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline)

**Related to:** [13 Proposal assistance](#f13-proposal-assistance)

**Consumes:**

- [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) — opportunity detail surface

**Produces:**

- Notes timeline consumed by [13 Proposal assistance](#f13-proposal-assistance)

**ADRs:** [ADR-018](ADRs/ADR-018-proposal-artifact-rendering-and-delivery.md)

---

<a id="f20-proposal-artifacts-and-delivery"></a>

### 20 · Proposal artifacts and delivery

**Objective:** After proposal approval, generate commercial text, slide, and contract from repository templates; allow browser edits for text/contract; render PDFs on demand; support download and human-confirmed SMTP email send that advances the opportunity to Proposal Sent.

**Dependencies:** [13 Proposal assistance](#f13-proposal-assistance)

**Related to:** [14 Pipeline stage-based automation](#f14-pipeline-stage-automation), [15 Slack notifications](#f15-slack-notifications)

**Consumes:**

- [13 Proposal assistance](#f13-proposal-assistance) — approved proposal and line items

**Produces:**

- Editable artifacts, on-demand PDFs, and confirmed delivery moving the opportunity to Proposal Sent
- Timing signals optionally consumed by [15 Slack notifications](#f15-slack-notifications)

**ADRs:** [ADR-018](ADRs/ADR-018-proposal-artifact-rendering-and-delivery.md), [ADR-019](ADRs/ADR-019-human-controlled-proposal-delivery.md)

---

## Features relationship

**Foundation (omitted from rows):** [01 Platform foundation](#f01-platform-foundation), [02 Authentication](#f02-authentication), [03 Application shell and design system](#f03-application-shell-design-system) — apply to the whole product; not repeated in matrix cells.

Cross-feature only; vendor/infra (PostgreSQL, Redis, OpenAI, etc.) stay in feature prose and ADRs.

| Feature | Depends on | Consumes | Produces |
| ------- | ---------- | -------- | -------- |
| [04 Lead and client management](#f04-lead-client-management) | — | — | [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [10 Automated prospecting](#f10-automated-prospecting), [11 Automated lead qualification](#f11-automated-lead-qualification) |
| [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) | [04 Lead and client management](#f04-lead-client-management) | [04 Lead and client management](#f04-lead-client-management) | [06 Follow-up management](#f06-follow-up-management), [13 Proposal assistance](#f13-proposal-assistance), [14 Pipeline stage-based automation](#f14-pipeline-stage-automation), [19 Opportunity notes](#f19-opportunity-notes) |
| [06 Follow-up management](#f06-follow-up-management) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) | [08 Operational dashboard](#f08-operational-dashboard), [15 Slack notifications](#f15-slack-notifications), [16 Google Calendar integration](#f16-google-calendar) |
| [07 Task management](#f07-task-management) | [04 Lead and client management](#f04-lead-client-management) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) | [08 Operational dashboard](#f08-operational-dashboard), [15 Slack notifications](#f15-slack-notifications), [16 Google Calendar integration](#f16-google-calendar) |
| [08 Operational dashboard](#f08-operational-dashboard) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management) | — |
| [09 AI provider layer and orchestration](#f09-ai-orchestration) | — | — | [10 Automated prospecting](#f10-automated-prospecting), [11 Automated lead qualification](#f11-automated-lead-qualification), [12 AI recommendations and insights](#f12-ai-recommendations), [13 Proposal assistance](#f13-proposal-assistance), [14 Pipeline stage-based automation](#f14-pipeline-stage-automation) |
| [10 Automated prospecting](#f10-automated-prospecting) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration) | [11 Automated lead qualification](#f11-automated-lead-qualification) |
| [11 Automated lead qualification](#f11-automated-lead-qualification) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration) | [12 AI recommendations and insights](#f12-ai-recommendations) |
| [12 AI recommendations and insights](#f12-ai-recommendations) | [11 Automated lead qualification](#f11-automated-lead-qualification) | [11 Automated lead qualification](#f11-automated-lead-qualification), [04 Lead and client management](#f04-lead-client-management) | — |
| [18 Commercial service catalog](#f18-commercial-service-catalog) | — | — | [13 Proposal assistance](#f13-proposal-assistance) |
| [19 Opportunity notes](#f19-opportunity-notes) | [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) | [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) | [13 Proposal assistance](#f13-proposal-assistance) |
| [13 Proposal assistance](#f13-proposal-assistance) | [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration), [18 Commercial service catalog](#f18-commercial-service-catalog), [19 Opportunity notes](#f19-opportunity-notes) | [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration), [11 Automated lead qualification](#f11-automated-lead-qualification), [18 Commercial service catalog](#f18-commercial-service-catalog), [19 Opportunity notes](#f19-opportunity-notes) | [20 Proposal artifacts and delivery](#f20-proposal-artifacts-and-delivery) |
| [20 Proposal artifacts and delivery](#f20-proposal-artifacts-and-delivery) | [13 Proposal assistance](#f13-proposal-assistance) | [13 Proposal assistance](#f13-proposal-assistance) | [14 Pipeline stage-based automation](#f14-pipeline-stage-automation), [15 Slack notifications](#f15-slack-notifications) |
| [14 Pipeline stage-based automation](#f14-pipeline-stage-automation) | [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management), [09 AI provider layer and orchestration](#f09-ai-orchestration), [13 Proposal assistance](#f13-proposal-assistance), [20 Proposal artifacts and delivery](#f20-proposal-artifacts-and-delivery) | [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management), [09 AI provider layer and orchestration](#f09-ai-orchestration), [13 Proposal assistance](#f13-proposal-assistance), [20 Proposal artifacts and delivery](#f20-proposal-artifacts-and-delivery) | [15 Slack notifications](#f15-slack-notifications) |
| [15 Slack notifications](#f15-slack-notifications) | [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management) | [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [20 Proposal artifacts and delivery](#f20-proposal-artifacts-and-delivery) | — |
| [16 Google Calendar integration](#f16-google-calendar) | [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management) | [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management) | — |

---

## Development waves

At most **three** features per wave. Order respects dependencies (no feature appears before its prerequisites).

**Status values:** `Pending` (not started) · `In progress` (active implementation) · `Done` (all features in the wave delivered; FDRs in `docs/FDRs/Done/`). Ralph Building sets a wave to `Done` when the last feature is complete and deletes local plan files in `docs/FDRs/ImplementationPlans/` (gitignored).

| Wave | Status | Features |
| ---- | ------ | -------- |
| **1** | Done | [01 Platform foundation](#f01-platform-foundation), [02 Authentication](#f02-authentication), [03 Application shell and design system](#f03-application-shell-design-system) |
| **2** | Done | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [06 Follow-up management](#f06-follow-up-management) |
| **3** | Done | [07 Task management](#f07-task-management), [08 Operational dashboard](#f08-operational-dashboard), [09 AI provider layer and orchestration](#f09-ai-orchestration) |
| **4** | Done | [10 Automated prospecting](#f10-automated-prospecting), [11 Automated lead qualification](#f11-automated-lead-qualification), [12 AI recommendations and insights](#f12-ai-recommendations) |
| **5** | Pending | [18 Commercial service catalog](#f18-commercial-service-catalog), [19 Opportunity notes](#f19-opportunity-notes) |
| **6** | Pending | [13 Proposal assistance](#f13-proposal-assistance) |
| **7** | Pending | [20 Proposal artifacts and delivery](#f20-proposal-artifacts-and-delivery), [14 Pipeline stage-based automation](#f14-pipeline-stage-automation) |
| **8** | Pending | [15 Slack notifications](#f15-slack-notifications), [16 Google Calendar integration](#f16-google-calendar) |

---

## ADR index

| ADR | Status | Title |
| --- | ------ | ----- |
| [ADR-001](ADRs/ADR-001-technology-stack.md) | Accepted | Technology stack |
| [ADR-002](ADRs/ADR-002-ai-provider-abstraction.md) | Accepted | AI provider abstraction |
| [ADR-003](ADRs/ADR-003-ai-orchestration-architecture.md) | Accepted | AI orchestration architecture |
| [ADR-004](ADRs/ADR-004-unified-lead-client-entity.md) | Accepted | Unified lead/client entity |
| [ADR-005](ADRs/ADR-005-fixed-sales-pipeline.md) | Accepted | Fixed sales pipeline |
| [ADR-006](ADRs/ADR-006-queue-async-processing.md) | Accepted | Queue and async processing |
| [ADR-007](ADRs/ADR-007-scheduled-prospecting.md) | Accepted | Scheduled prospecting |
| [ADR-008](ADRs/ADR-008-authentication-internal-users.md) | Accepted | Authentication for internal users |
| [ADR-009](ADRs/ADR-009-slack-integration.md) | Accepted | Slack integration |
| [ADR-010](ADRs/ADR-010-google-calendar-integration.md) | Accepted | Google Calendar integration |
| [ADR-011](ADRs/ADR-011-human-approval-commercial-actions.md) | Superseded by ADR-019 | Human approval for commercial actions |
| [ADR-012](ADRs/ADR-012-integration-failure-isolation.md) | Accepted | Integration failure isolation |
| [ADR-013](ADRs/ADR-013-dark-mode-design-system.md) | Accepted | Dark mode design system |
| [ADR-014](ADRs/ADR-014-dashboard-observability-scope.md) | Accepted | Dashboard and observability scope |
| [ADR-015](ADRs/ADR-015-prospecting-discovery-undefined-mvp.md) | Accepted | AI-led prospecting on public/free sources (no paid data APIs) |
| [ADR-016](ADRs/ADR-016-proposal-generation-undefined-mvp.md) | Superseded by ADR-018 | Proposal generation format undefined in MVP |
| [ADR-017](ADRs/ADR-017-wave-4-ai-qualification-schema.md) | Accepted (service wording partially superseded by ADR-020) | Wave 4 AI qualification flow and insight schema |
| [ADR-018](ADRs/ADR-018-proposal-artifact-rendering-and-delivery.md) | Accepted (2026-08-14) | Proposal domain, generation, and artifacts |
| [ADR-019](ADRs/ADR-019-human-controlled-proposal-delivery.md) | Accepted (2026-08-14) | Human-controlled proposal delivery |
| [ADR-020](ADRs/ADR-020-commercial-service-catalog-boundary.md) | Accepted (2026-08-14) | Commercial service catalog boundary |
