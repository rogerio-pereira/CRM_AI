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
- Financial management
- Billing/invoicing
- Customer support ticketing

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
- Proposal information
- Related client
- Follow-up tasks
- AI recommendations

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

Conversations happen outside the system.

AI agents may assist with:
- Suggested responses
- Next-step recommendations
- Outreach strategies
- Solution ideas

---

## 7. Proposal Assistance

The system assists users in creating commercial proposals based on:
- Prospecting data
- Qualification analysis
- Manual user input

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

The AI provider should remain configurable to support:
- OpenAI
- Gemini

## Possible Future Integrations
- Gmail
- Google Drive
- SMS services

---

# Functional Requirements

## CRM
- Create and edit leads/clients
- View client history
- Manage opportunities
- Move opportunities between pipeline stages

## AI Agents
- Execute automated prospecting
- Qualify leads automatically
- Generate outreach suggestions
- Assist proposal generation

## Automation
- Schedule follow-ups automatically
- Schedule important tasks automatically
- Trigger Slack notifications

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
    - Project management
    - Billing
    - Finances
- Email automation