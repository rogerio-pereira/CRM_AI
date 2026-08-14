# Product Requirements Document (PRD)

## Product Overview

This document describes the requirements for a lightweight internal CRM focused on freelance lead generation, opportunity management, sales pipeline tracking, and AI-assisted prospecting automation.

The platform is designed for internal operational use and prioritizes simplicity, automation, and AI-assisted workflows over traditional enterprise CRM complexity.

---

# Objectives

The primary goals of the system are:

- Manage leads and clients as a unified entity
- Track opportunities through a sales pipeline
- Organize follow-ups and tasks
- Automate lead discovery and qualification
- Assist with proposal generation
- Centralize prospecting activities in a single dashboard
- Reduce manual operational work through autonomous AI agents

---

# Scope

## In Scope

- Lead/client management
- Opportunity management
- Kanban sales pipeline
- AI-powered lead prospecting
- AI-powered lead qualification
- AI-assisted proposal generation
- Follow-up scheduling
- Task management
- Dashboard and analytics
- Slack notifications
- Google Calendar integration

## Out of Scope

- Project management
- Financial management (accounts receivable, general ledger, tax)
- Billing/invoicing
- Customer support ticketing

Commercial **proposal pricing** (catalog defaults and per-proposal line amounts) is in scope for sales assistance. It is **not** billing, invoicing, or financial management.

---

# Core Entities

## Lead / Client

Leads and clients are treated as the same entity throughout the system lifecycle.

### Main Attributes
- Company name
- Contact information
- Website
- Social links
- Lead source
- Qualification notes
- Opportunity history
- Follow-up history
- AI-generated insights

---

## Opportunity

Represents a potential business deal.

### Main Attributes
- Opportunity title
- Pipeline stage
- Estimated value
- Status
- Related client
- Follow-up tasks
- AI recommendations
- Opportunity notes timeline (author, timestamp, body)
- Related proposal (at most one)

---

## Commercial service (catalog)

Sellable line items used when building proposals.

### Main Attributes
- Name
- Description
- Default unit price
- Category reference aligned with `docs/services/` general service briefs (qualification categories)

Catalog prices support proposal drafting only; the product does not invoice or collect payment.

---

## Opportunity note

Internal timeline notes attached to an opportunity.

### Main Attributes
- Body
- Author (user)
- Created at

---

## Proposal

Commercial proposal for a single opportunity (one proposal per opportunity).

### Main Attributes
- Related opportunity (unique)
- Line items (catalog service, quantity, unit price override, notes)
- Approval state (approved by / at when human approves)
- Commercial text (editable)
- Contract text (editable)
- Slide content used for PDF render
- Send metadata as needed for delivery tracking

---

## Follow-Up

Tracks reminders and future actions related to opportunities and clients.

### Main Attributes
- Due date
- Reminder status
- Related client
- Related opportunity
- Notes
- Priority

---

# Main Workflow

## 1. Automated Prospecting Initialization

At configured times (08:00 on weekdays), the system automatically starts prospecting agents.

---

## 2. Lead Collection

AI agents search public sources including:
- Google Maps
- Instagram
- Facebook
- Business websites
- Local directories

The goal is to identify companies with potential service opportunities.

---

## 3. Lead Registration

Discovered leads are:
- Stored in the CRM database
- Added to a qualification queue for AI processing

---

## 4. Lead Qualification

Qualification agents analyze leads by identifying:
- Website issues
- Weak digital presence
- Manual operational processes
- Potential business opportunities
- Possible customer pain points

The CRM record is updated automatically after analysis.

---

## 5. AI-Generated Suggestions

For each qualified lead, the system generates:
- Company summary
- Potential pain points
- Opportunity analysis
- Suggested outreach strategy

---

## 6. Human Interaction

Users manually decide whether to:
- Contact the lead
- Ignore the lead
- Archive the lead
- Schedule follow-up
- Edit and approve a proposal
- Download or send approved proposal artifacts by email from the CRM

Day-to-day conversations and negotiation still happen outside the system. The CRM may deliver **approved proposal PDFs** when a user explicitly confirms send (SMTP). AI never sends client-facing mail autonomously.

AI agents may assist with:
- Suggested responses
- Next-step recommendations
- Outreach strategies
- Solution ideas
- Recommended proposal line items and generated draft artifacts

---

## 7. Proposal Assistance

The system assists users in creating commercial proposals based on:
- Prospecting data
- Qualification analysis
- Commercial service catalog (priced line items)
- Opportunity notes timeline
- Manual user edits

Flow:

1. AI recommends services and values into the proposal draft.
2. Human edits and approves the proposal.
3. AI generates commercial text, slide, and contract from fixed templates in the repository.
4. Human reviews editable text/contract in the browser, downloads PDFs, and/or sends email via the CRM; on confirmed send the opportunity moves to Proposal Sent.

There is one proposal record per opportunity. Regenerating overwrites the current draft content. Electronic signature is out of scope.
---

## 8. Follow-Up Automation

The system automatically manages:
- Follow-up reminders
- Important task scheduling

Slack notifications are sent only when user action is required, including:
- Pending follow-ups
- Unanswered proposals
- Critical tasks

---

## 9. Dashboard

The dashboard centralizes operational visibility.

### Dashboard Cards
- Leads created today
- Opportunities created today

### Charts (Last 30 Days)
- Leads per day
- Opportunities per day
- Sales per day

### Tables
- Pending tasks
- Follow-ups

---

# User Interface Requirements

## Opportunity Management
- Kanban-style pipeline management

## Client Management
- Table-based overview
- Modal/dialog for detailed information

## Dashboard
- Cards
- Tables
- Charts
- Centralized operational visibility

---

# Automation Rules

## Prospecting
- Runs automatically at 08:00 on weekdays

## Lead Qualification
- Triggered automatically when leads enter the qualification queue

## AI Recommendations
AI agents may:
- Analyze leads
- Suggest actions
- Suggest proposals
- Recommend next steps

Human approval is required before execution of sales actions.

---

# Integrations

## Required Integrations (MVP)
- Google Calendar
- Slack
- AI Provider abstraction layer
- Transactional email via Laravel Mail (SMTP) for **human-confirmed** proposal delivery (local Mailpit in Sail)

The AI provider should remain configurable to support:
- OpenAI
- Gemini

## Possible Future Integrations
- Gmail (provider-specific API beyond SMTP)
- Google Drive
- SMS services

---

# Functional Requirements

## CRM
- Create and edit leads/clients
- View client history
- Manage opportunities
- Move opportunities between pipeline stages
- Maintain commercial service catalog
- Maintain opportunity notes timeline
- Create, edit, approve, download, and send proposals

## AI Agents
- Execute automated prospecting
- Qualify leads automatically
- Generate outreach suggestions
- Recommend proposal line items and generate proposal artifacts after approval

## Automation
- Schedule follow-ups automatically
- Schedule important tasks automatically
- Trigger Slack notifications
- Trigger proposal assistance on relevant pipeline stages (without autonomous send)

## Dashboard
- Display operational metrics
- Display recent activity
- Show pending actions

---

# Non-Functional Requirements

## Scalability
- Architecture should support future AI workflows and integrations

## Maintainability
- Modular AI agent architecture
- Configurable automation workflows

## Reliability
- Scheduled jobs must support retry mechanisms
- External integration failures should not interrupt core CRM functionality

---

# Success Metrics

- Reduced manual prospecting effort
- Increased qualified leads per week
- Faster follow-up execution
- Improved proposal turnaround time
- Centralized visibility of opportunities and tasks

---

# Future Considerations

Potential future expansions:
- Project management
- Billing
- Finances
- Broader email automation (threads, sequences) beyond human-confirmed proposal send
- Electronic signature for contracts
