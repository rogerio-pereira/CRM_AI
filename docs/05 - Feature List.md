# Internal AI-Assisted CRM — Feature List

**Version:** 1.0  
**Date:** 2026-05-27  
**References:** [PRD](01%20PRD.md), [HLD](02%20HLD.md), [Branding Manual](03%20-%20Branding%20Manual.md), [Design System](04%20-%20Design%20System.md), [ADRs](ADRs/)

**Convention:** Every cross-reference to a feature in this file uses `[NN Short title](#fNN-slug)`.

**FDR locations:** Active specs live in `docs/FDRs/ToDo/`; completed features in `docs/FDRs/Done/`; archived or superseded specs in `docs/FDRs/Closed/`.

**ADR status:** [ADR-015](ADRs/ADR-015-prospecting-discovery-undefined-mvp.md) and [ADR-016](ADRs/ADR-016-proposal-generation-undefined-mvp.md) are **Proposed** (awaiting stakeholder approval). Features [10](#f10-automated-prospecting), [11](#f11-automated-lead-qualification) (partial), [13](#f13-proposal-assistance), and [14](#f14-pipeline-stage-automation) (partial) depend on those decisions — see linked FDRs.

---

<a id="feature-index"></a>

## Feature index

| NN | Feature | FDR |
| -- | ------- | --- |
| 01 | [01 Platform foundation](#f01-platform-foundation) | [FDR-001](FDRs/ToDo/FDR-001-platform-foundation.md) |
| 02 | [02 Authentication](#f02-authentication) | [FDR-002](FDRs/ToDo/FDR-002-authentication.md) |
| 03 | [03 Application shell and design system](#f03-application-shell-design-system) | [FDR-003](FDRs/ToDo/FDR-003-application-shell-design-system.md) |
| 04 | [04 Lead and client management](#f04-lead-client-management) | [FDR-004](FDRs/Done/FDR-004-lead-client-management.md) |
| 05 | [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) | [FDR-005](FDRs/ToDo/FDR-005-opportunity-kanban-pipeline.md) |
| 06 | [06 Follow-up management](#f06-follow-up-management) | [FDR-006](FDRs/ToDo/FDR-006-follow-up-management.md) |
| 07 | [07 Task management](#f07-task-management) | [FDR-007](FDRs/ToDo/FDR-007-task-management.md) |
| 08 | [08 Operational dashboard](#f08-operational-dashboard) | [FDR-008](FDRs/ToDo/FDR-008-operational-dashboard.md) |
| 09 | [09 AI provider layer and orchestration](#f09-ai-orchestration) | [FDR-009](FDRs/ToDo/FDR-009-ai-orchestration.md) |
| 10 | [10 Automated prospecting](#f10-automated-prospecting) | [FDR-010](FDRs/ToDo/FDR-010-automated-prospecting.md) |
| 11 | [11 Automated lead qualification](#f11-automated-lead-qualification) | [FDR-011](FDRs/ToDo/FDR-011-automated-lead-qualification.md) |
| 12 | [12 AI recommendations and insights](#f12-ai-recommendations) | [FDR-012](FDRs/ToDo/FDR-012-ai-recommendations.md) |
| 13 | [13 Proposal assistance](#f13-proposal-assistance) | [FDR-013](FDRs/ToDo/FDR-013-proposal-assistance.md) |
| 14 | [14 Pipeline stage-based automation](#f14-pipeline-stage-automation) | [FDR-014](FDRs/ToDo/FDR-014-pipeline-stage-automation.md) |
| 15 | [15 Slack notifications](#f15-slack-notifications) | [FDR-015](FDRs/ToDo/FDR-015-slack-notifications.md) |
| 16 | [16 Google Calendar integration](#f16-google-calendar) | [FDR-016](FDRs/ToDo/FDR-016-google-calendar.md) |
| 17 | [17 Integration settings](#f17-integration-settings) **Closed** | [FDR-017](FDRs/Closed/FDR-017-integration-settings.md) |

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
- Entries in qualification queue

**ADRs:** [ADR-003](ADRs/ADR-003-ai-orchestration-architecture.md), [ADR-007](ADRs/ADR-007-scheduled-prospecting.md), [ADR-015](ADRs/ADR-015-prospecting-discovery-undefined-mvp.md) (**Proposed** — blocks discovery adapter; see [FDR-010](FDRs/ToDo/FDR-010-automated-prospecting.md))

**Implementation note:** Do not implement real lead discovery until ADR-015 is **Accepted**.

---

<a id="f11-automated-lead-qualification"></a>

### 11 · Automated lead qualification

**Objective:** Qualify leads asynchronously when they enter the queue: digital presence analysis, pain points, CRM enrichment, stage advancement after analysis.

**Dependencies:** [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration)

**Consumes:**

- [04 Lead and client management](#f04-lead-client-management)
- [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) — Qualification stage
- [09 AI provider layer and orchestration](#f09-ai-orchestration) — qualification agent

**Produces:**

- Updated lead records with qualification notes and AI insights
- Stage moves after qualification (per pipeline rules)

**ADRs:** [ADR-003](ADRs/ADR-003-ai-orchestration-architecture.md), [ADR-006](ADRs/ADR-006-queue-async-processing.md), [ADR-015](ADRs/ADR-015-prospecting-discovery-undefined-mvp.md) (**Proposed**, via prospecting)

**Implementation note:** Core qualification can proceed with manual/mock leads; prospecting handoff and post-qualification stage target need confirmation ([FDR-011](FDRs/ToDo/FDR-011-automated-lead-qualification.md)).

---

<a id="f12-ai-recommendations"></a>

### 12 · AI recommendations and insights

**Objective:** Generate outreach suggestions, next-step recommendations, opportunity analysis, and labeled AI insight panels for qualified leads.

**Dependencies:** [04 Lead and client management](#f04-lead-client-management), [11 Automated lead qualification](#f11-automated-lead-qualification)

**Consumes:**

- [11 Automated lead qualification](#f11-automated-lead-qualification) — qualification context
- [04 Lead and client management](#f04-lead-client-management) — display on lead/opportunity UI

**Produces:**

- AI-generated summaries, pain points, strategies (human-reviewed)

**ADRs:** [ADR-003](ADRs/ADR-003-ai-orchestration-architecture.md), [ADR-011](ADRs/ADR-011-human-approval-commercial-actions.md), [ADR-013](ADRs/ADR-013-dark-mode-design-system.md)

---

<a id="f13-proposal-assistance"></a>

### 13 · Proposal assistance

**Objective:** Assist users in proposal generation and analysis stages using prospecting and qualification data plus manual input (implementation format TBD per HLD).

**Dependencies:** [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration), [11 Automated lead qualification](#f11-automated-lead-qualification)

**Consumes:**

- [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) — Proposal Generation / Analysis stages
- [09 AI provider layer and orchestration](#f09-ai-orchestration) — proposal assistant agent
- [11 Automated lead qualification](#f11-automated-lead-qualification) — analysis data

**Produces:**

- Proposal assistance content and recommendations (human approval before send)

**ADRs:** [ADR-011](ADRs/ADR-011-human-approval-commercial-actions.md), [ADR-016](ADRs/ADR-016-proposal-generation-undefined-mvp.md) (**Proposed** — blocks output format/storage; see [FDR-013](FDRs/ToDo/FDR-013-proposal-assistance.md))

**Implementation note:** Do not finalize proposal UI/persistence until ADR-016 is **Accepted**.

---

<a id="f14-pipeline-stage-automation"></a>

### 14 · Pipeline stage-based automation

**Objective:** On pipeline stage changes, trigger configured actions: AI jobs, follow-ups, tasks, and notification hooks (without assuming job chaining in MVP).

**Dependencies:** [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management), [09 AI provider layer and orchestration](#f09-ai-orchestration)

**Consumes:**

- [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) — stage change events
- [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management) — auto-create records
- [09 AI provider layer and orchestration](#f09-ai-orchestration) — stage-triggered agents

**Produces:**

- Automated follow-ups/tasks and dispatched AI jobs per stage rules

**ADRs:** [ADR-003](ADRs/ADR-003-ai-orchestration-architecture.md), [ADR-005](ADRs/ADR-005-fixed-sales-pipeline.md), [ADR-016](ADRs/ADR-016-proposal-generation-undefined-mvp.md) (**Proposed** — proposal stage action is stub-only until accepted)

**Implementation note:** See [FDR-014](FDRs/ToDo/FDR-014-pipeline-stage-automation.md) for partial build scope.

---

<a id="f15-slack-notifications"></a>

### 15 · Slack notifications

**Objective:** Send simple operational messages to a single Slack channel when user action is required (pending follow-ups, critical tasks, proposal reminders).

**Dependencies:** [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management), [08 Operational dashboard](#f08-operational-dashboard)

**Consumes:**

- [06 Follow-up management](#f06-follow-up-management)
- [07 Task management](#f07-task-management)
- Optional: [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) — proposal reminders

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

## Features relationship

**Foundation (omitted from rows):** [01 Platform foundation](#f01-platform-foundation), [02 Authentication](#f02-authentication), [03 Application shell and design system](#f03-application-shell-design-system) — apply to the whole product; not repeated in matrix cells.

Cross-feature only; vendor/infra (PostgreSQL, Redis, OpenAI, etc.) stay in feature prose and ADRs.

| Feature | Depends on | Consumes | Produces |
| ------- | ---------- | -------- | -------- |
| [04 Lead and client management](#f04-lead-client-management) | — | — | [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [10 Automated prospecting](#f10-automated-prospecting), [11 Automated lead qualification](#f11-automated-lead-qualification) |
| [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) | [04 Lead and client management](#f04-lead-client-management) | [04 Lead and client management](#f04-lead-client-management) | [06 Follow-up management](#f06-follow-up-management), [13 Proposal assistance](#f13-proposal-assistance), [14 Pipeline stage-based automation](#f14-pipeline-stage-automation) |
| [06 Follow-up management](#f06-follow-up-management) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) | [08 Operational dashboard](#f08-operational-dashboard), [15 Slack notifications](#f15-slack-notifications), [16 Google Calendar integration](#f16-google-calendar) |
| [07 Task management](#f07-task-management) | [04 Lead and client management](#f04-lead-client-management) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) | [08 Operational dashboard](#f08-operational-dashboard), [15 Slack notifications](#f15-slack-notifications), [16 Google Calendar integration](#f16-google-calendar) |
| [08 Operational dashboard](#f08-operational-dashboard) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management) | — |
| [09 AI provider layer and orchestration](#f09-ai-orchestration) | — | — | [10 Automated prospecting](#f10-automated-prospecting), [11 Automated lead qualification](#f11-automated-lead-qualification), [12 AI recommendations and insights](#f12-ai-recommendations), [13 Proposal assistance](#f13-proposal-assistance), [14 Pipeline stage-based automation](#f14-pipeline-stage-automation) |
| [10 Automated prospecting](#f10-automated-prospecting) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration) | [11 Automated lead qualification](#f11-automated-lead-qualification) |
| [11 Automated lead qualification](#f11-automated-lead-qualification) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration) | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration) | [12 AI recommendations and insights](#f12-ai-recommendations) |
| [12 AI recommendations and insights](#f12-ai-recommendations) | [11 Automated lead qualification](#f11-automated-lead-qualification) | [11 Automated lead qualification](#f11-automated-lead-qualification), [04 Lead and client management](#f04-lead-client-management) | — |
| [13 Proposal assistance](#f13-proposal-assistance) | [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration) | [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [09 AI provider layer and orchestration](#f09-ai-orchestration), [11 Automated lead qualification](#f11-automated-lead-qualification) | — |
| [14 Pipeline stage-based automation](#f14-pipeline-stage-automation) | [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management), [09 AI provider layer and orchestration](#f09-ai-orchestration) | [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management), [09 AI provider layer and orchestration](#f09-ai-orchestration) | [15 Slack notifications](#f15-slack-notifications) |
| [15 Slack notifications](#f15-slack-notifications) | [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management) | [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline) | — |
| [16 Google Calendar integration](#f16-google-calendar) | [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management) | [06 Follow-up management](#f06-follow-up-management), [07 Task management](#f07-task-management) | — |

---

## Development waves

At most **three** features per wave. Order respects dependencies (no feature appears before its prerequisites).

**Status values:** `Pending` (not started) · `In progress` (active implementation) · `Done` (all features in the wave delivered; FDRs in `docs/FDRs/Done/`). Ralph Building sets a wave to `Done` when the last feature is complete and deletes local plan files in `docs/FDRs/ImplementationPlans/` (gitignored).

| Wave | Status | Features |
| ---- | ------ | -------- |
| **1** | Done | [01 Platform foundation](#f01-platform-foundation), [02 Authentication](#f02-authentication), [03 Application shell and design system](#f03-application-shell-design-system) |
| **2** | Pending | [04 Lead and client management](#f04-lead-client-management), [05 Opportunity management and Kanban pipeline](#f05-opportunity-kanban-pipeline), [06 Follow-up management](#f06-follow-up-management) |
| **3** | Pending | [07 Task management](#f07-task-management), [08 Operational dashboard](#f08-operational-dashboard), [09 AI provider layer and orchestration](#f09-ai-orchestration) |
| **4** | Pending | [10 Automated prospecting](#f10-automated-prospecting), [11 Automated lead qualification](#f11-automated-lead-qualification), [12 AI recommendations and insights](#f12-ai-recommendations) |
| **5** | Pending | [13 Proposal assistance](#f13-proposal-assistance), [14 Pipeline stage-based automation](#f14-pipeline-stage-automation), [15 Slack notifications](#f15-slack-notifications) |
| **6** | Pending | [16 Google Calendar integration](#f16-google-calendar) |

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
| [ADR-011](ADRs/ADR-011-human-approval-commercial-actions.md) | Accepted | Human approval for commercial actions |
| [ADR-012](ADRs/ADR-012-integration-failure-isolation.md) | Accepted | Integration failure isolation |
| [ADR-013](ADRs/ADR-013-dark-mode-design-system.md) | Accepted | Dark mode design system |
| [ADR-014](ADRs/ADR-014-dashboard-observability-scope.md) | Accepted | Dashboard and observability scope |
| [ADR-015](ADRs/ADR-015-prospecting-discovery-undefined-mvp.md) | **Proposed** | Prospecting discovery undefined in MVP |
| [ADR-016](ADRs/ADR-016-proposal-generation-undefined-mvp.md) | **Proposed** | Proposal generation undefined in MVP |
