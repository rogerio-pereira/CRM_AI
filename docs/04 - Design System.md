# Design System

## Project
Internal AI-Assisted CRM Platform

## Purpose
This Design System defines the exact UI rules, tokens, layouts, components, and screen generation instructions for the internal CRM platform.

It is intended for:

- Figma
- Google Stitch
- AI UI generation tools
- Laravel 13
- Laravel Livewire
- Tailwind CSS

An AI generating screens from this document must not guess colors, layout, component behavior, or screen priorities.

---

# 1. Product Context

## Product Type
Internal AI-assisted CRM.

## Users
Internal users only (this is not a SaaS product).

## Core Workflows
The UI must support:

- Dashboard visibility
- Lead/client management
- Opportunity management
- Kanban sales pipeline
- Follow-up tracking
- Task tracking
- AI-assisted lead qualification
- AI-generated recommendations
- Proposal assistance
- Slack and Google Calendar integration settings

---

# 2. Design Principles

## Functional Over Decorative
Every UI element must support a workflow.

## CRM-First
Prioritize pipeline visibility, lead data, opportunities, follow-ups, and tasks.

## Modern Minimalism
The UI should look modern and refined, but not decorative.

## Balanced Density
The interface should show useful operational information without feeling crowded.

## AI as Support Layer
AI should appear as labeled insights, suggestions, and recommendations.

AI must not become the central UI.

---

# 3. Theme

## Mode
Dark mode only.

## Style
Soft dark theme.

Do not use:

- Pure black
- Light mode
- Neon colors
- Heavy gradients
- Glassmorphism
- Decorative illustrations
- Marketing landing page patterns

---

# 4. Official Color Tokens

## Background Colors

| Token | Hex | Usage |
|---|---:|---|
| `bg-app` | `#0F172A` | Main application background |
| `bg-sidebar` | `#111827` | Sidebar background |
| `bg-surface` | `#1E293B` | Cards, tables, kanban columns |
| `bg-elevated` | `#273449` | Modals, popovers, dropdowns |
| `bg-hover` | `#334155` | Hover state |
| `bg-active` | `#3730A3` | Active navigation or selected state |

## Primary Colors

| Token | Hex | Usage |
|---|---:|---|
| `primary` | `#6366F1` | Primary actions, CTAs, focus rings; optional info status |
| `primary-hover` | `#4F46E5` | Primary button hover |
| `primary-active` | `#4338CA` | Primary button pressed state |
| `primary-focus` | `#818CF8` | Focus rings and focus borders |

## Accent Colors

| Token | Hex | Usage |
|---|---:|---|
| `accent` | `#0EA5E9` | Links, secondary highlights |
| `accent-hover` | `#0284C7` | Link hover, secondary action hover |
| `accent-soft` | `#38BDF8` | Informational highlights |

## AI Colors

| Token | Hex | Usage |
|---|---:|---|
| `ai` | `#8B5CF6` | AI badges, AI labels, AI insight markers; pipeline stages Qualification and Proposal Generation |
| `ai-hover` | `#7C3AED` | AI hover or active states |
| `ai-soft` | `#A78BFA` | Subtle AI highlights |

## Text Colors

| Token | Hex | Usage |
|---|---:|---|
| `text-primary` | `#F8FAFC` | Page titles, primary text |
| `text-secondary` | `#CBD5E1` | Body text, labels |
| `text-muted` | `#94A3B8` | Metadata, helper text |
| `text-disabled` | `#64748B` | Disabled text |

## Border Colors

| Token | Hex | Usage |
|---|---:|---|
| `border-subtle` | `#1E293B` | Subtle dividers |
| `border-default` | `#334155` | Default component border |
| `border-strong` | `#475569` | Strong divider or hover border |
| `border-focus` | `#818CF8` | Focus state border |

## Status Colors

Use intuitive functional colors (not decorative brand colors):

| Token | Hex | Usage |
|---|---:|---|
| `success` | `#10B981` | Won, completed, successful (green) |
| `warning` | `#F59E0B` | Pending, due soon, attention needed (amber) |
| `danger` | `#EF4444` | Lost, overdue, destructive (red) |
| `info` | `#6366F1` or `#0EA5E9` | Informational; use `primary` or `accent` |
| `neutral` | `#94A3B8` | Archived, inactive, default state |

---

# 5. Pipeline Stage Colors

Use exactly these colors for the sales pipeline.

| Stage | Color Token | Hex |
|---|---|---:|
| Lead | `neutral` | `#94A3B8` |
| Qualification | `ai` | `#8B5CF6` |
| Contact | `accent` | `#0EA5E9` |
| Proposal Generation | `ai` | `#8B5CF6` |
| Proposal Analysis | `accent` | `#0EA5E9` |
| Proposal Sent | `neutral` | `#94A3B8` |
| Won | `success` | `#10B981` |
| Lost | `danger` | `#EF4444` |

---

# 6. Tailwind Mapping

Use this exact Tailwind color mapping.

```js
theme: {
  extend: {
    colors: {
      app: {
        DEFAULT: "#0F172A",
        sidebar: "#111827",
        surface: "#1E293B",
        elevated: "#273449",
        hover: "#334155",
        active: "#3730A3",
      },
      primary: {
        DEFAULT: "#6366F1",
        hover: "#4F46E5",
        active: "#4338CA",
        focus: "#818CF8",
      },
      accent: {
        DEFAULT: "#0EA5E9",
        hover: "#0284C7",
        soft: "#38BDF8",
      },
      ai: {
        DEFAULT: "#8B5CF6",
        hover: "#7C3AED",
        soft: "#A78BFA",
      },
      text: {
        primary: "#F8FAFC",
        secondary: "#CBD5E1",
        muted: "#94A3B8",
        disabled: "#64748B",
      },
      border: {
        subtle: "#1E293B",
        DEFAULT: "#334155",
        strong: "#475569",
        focus: "#818CF8",
      },
      status: {
        success: "#10B981",
        warning: "#F59E0B",
        danger: "#EF4444",
        info: "#0EA5E9", // or primary "#6366F1"
        neutral: "#94A3B8",
      },
    },
  },
}
```

---

# 7. Typography

## Font Family

Use Inter.

## Typography weights
- 700 (`font-bold`) for titles, buttons, active navigation labels, CTAs, badges, and metric values
- 300 (`font-light`) for body text, table content, labels, helper text, and metadata

## Type Scale

| Token | Size | Line Height | Weight | Usage |
|---|---:|---:|---:|---|
| `text-xs` | 14px | 20px | 300 / 700 | Metadata, badges |
| `text-sm` | 14px | 20px | 300 / 700 | Tables, labels, body |
| `text-base` | 16px | 24px | 300 / 700 | Standard text |
| `text-lg` | 18px | 28px | 300 / 700 | Section titles |
| `text-xl` | 20px | 28px | 300 / 700 | Page titles |
| `text-2xl` | 24px | 32px | 300 / 700 | Dashboard values |

---

# 8. Spacing

## Spacing Scale

| Token | Value | Usage |
|---|---:|---|
| `space-1` | 4px | Tight inline spacing |
| `space-2` | 8px | Small gaps |
| `space-3` | 12px | Form field spacing |
| `space-4` | 16px | Default component padding |
| `space-5` | 20px | Card padding |
| `space-6` | 24px | Section spacing |
| `space-8` | 32px | Large layout separation |

## Layout Density

Use:

- App shell padding: 24px
- Card padding: 16px to 20px
- Table row height: 48px
- Kanban card padding: 12px to 16px
- Modal padding: 24px

---

# 9. Border Radius

| Token | Value | Usage |
|---|---:|---|
| `radius-sm` | 6px | Inputs, badges |
| `radius-md` | 8px | Buttons, table containers |
| `radius-lg` | 12px | Cards, kanban cards |
| `radius-xl` | 16px | Modals, large panels |

Avoid fully rounded pill shapes except for badges.

---

# 10. Application Layout

## Mobile-First Layout

The default layout must include:

1. Left sidebar
2. Top header
3. Main content area

## Sidebar

Width:

- Expanded: 240px
- Collapsed state: 72px

Background:

- `bg-sidebar` / `#111827`

Navigation items:

- Dashboard
- Leads / Clients
- Opportunities
    - View in Kanban
- Follow-ups
- Tasks
- Services (commercial catalog)
- Settings

Sidebar item states:

| State | Background | Text |
|---|---|---|
| Default | Transparent | `text-muted` |
| Hover | `bg-hover` | `text-primary` |
| Active | `bg-active` | `text-primary` |

## Header

Height:

- 64px

Header must contain:

- Page title
- Optional search
- Primary action button
- User/account menu

## Main Content

Use:

- Background: `bg-app`
- Padding: 24px
- Mobile-first layout; scale up for tablet and desktop

---

# 11. Buttons

## Primary Button

Use for:

- Add Lead
- Add Opportunity
- Create Follow-Up
- Add Service
- Generate Proposal Draft
- Approve Proposal
- Send Proposal

Style:

- Background: `primary`
- Hover: `primary-hover`
- Active: `primary-active`
- Text: `text-primary`
- Radius: `radius-md`
- Height: 40px
- Padding: 12px 16px
- Font: `text-sm`, `font-bold` (700)

## Secondary Button

Style:

- Background: `bg-surface`
- Hover: `bg-hover`
- Border: `border-default`
- Text: `text-secondary`
- Font: `text-sm`, `font-bold` (700)

## Ghost Button

Style:

- Background: transparent
- Hover: `bg-hover`
- Text: `text-muted`
- Hover text: `text-primary`

## Destructive Button

Style:

- Background: `danger`
- Text: `text-primary`
- Font: `text-sm`, `font-bold` (700)

---

# 12. Forms and Inputs

## Input Style

Default input:

- Background: `bg-surface`
- Border: `border-default`
- Text: `text-primary`
- Placeholder: `text-muted`
- Focus border: `border-focus`
- Focus ring: `primary-focus`
- Radius: `radius-md`
- Height: 40px
- Padding: 12px

## Labels

- Text: `text-secondary`
- Font size: `text-sm`
- Font weight: `font-light` (300)

## Helper Text

- Text: `text-muted`
- Font size: `text-xs`

## Error Text

- Text: `danger`
- Font size: `text-xs`

---

# 13. Tables

Tables are primary UI components.

## Table Container

- Background: `bg-surface`
- Border: `border-default`
- Radius: `radius-lg`

## Table Header

- Text: `text-muted`
- Font: `text-xs`, `font-bold` (700)
- Border bottom: `border-default`
- Height: 40px

## Table Row

- Height: 48px
- Text: `text-secondary`
- Font: `font-light` (300)
- Hover background: `bg-hover`
- Border bottom: `border-subtle`

## Required Tables

Generate table patterns for:

- Leads / Clients
- Opportunities table view, if needed
- Pending tasks
- Follow-ups

All tables lines should be stripped

---

# 14. Cards

## Default Card

Use for dashboard metrics and summaries.

Style:

- Background: `bg-surface`
- Border: `border-default`
- Radius: `radius-lg`
- Padding: 16px or 20px

## Metric Card Structure

1. Label
2. Main value
3. Optional trend/supporting text

---

# 15. Kanban

Kanban is the primary opportunity pipeline interface.

## Required Pipeline Stages

Use exactly:

1. Lead
2. Qualification
3. Contact
4. Proposal Generation
5. Proposal Analysis
6. Proposal Sent
7. Won
8. Lost

## Column Style

- Background: `bg-surface`
- Border: `border-default`
- Radius: `radius-lg`
- Padding: 12px
- Gap: 16px
- Minimum column width: 280px

## Kanban Card

Must display:

- Opportunity title
- Company/client name
- Estimated value
- Next follow-up date
- AI insight indicator, if available

Style:

- Background: `bg-elevated`
- Border: `border-default`
- Radius: `radius-lg`
- Padding: 12px
- Hover border: `border-strong`

---

# 16. Modals

Use modals for:

- Lead/client details
- Opportunity details (including notes timeline and proposal summary)
- Quick edit forms
- Follow-up creation
- Task creation
- Service catalog create/edit
- Proposal line-item edit
- Proposal artifact preview / confirm send email

## Modal Style

- Background: `bg-elevated`
- Border: `border-default`
- Radius: `radius-xl`
- Padding: 24px

Widths:

- Small: 480px
- Medium: 720px
- Large: 960px

---

# 17. AI UI Patterns

AI must be represented as a secondary assistance layer.

## AI Suggestion Panel

Use for:

- Lead qualification insights
- Outreach suggestions
- Proposal recommendations
- Next-step recommendations

Style:

- Background: `bg-surface`
- Border: `ai`
- Badge: `AI Insight`
- Badge color: `ai`

## AI Rule

AI-generated content must always be labeled as AI-generated.

Do not make AI-generated recommendations look like confirmed human decisions.

---

# 17A. Proposal and catalog UI patterns

## Commercial services catalog

- Table-first CRUD screen reachable from the sidebar **Services** item.
- Columns: name, category, default price, active flag.
- Create/edit via medium modal.

## Opportunity notes timeline

- Inside the opportunity detail modal (or dedicated tab).
- Chronological list with author and timestamp.
- Add-note form at the bottom; newest notes easy to scan.

## Proposal editor

- Entry from opportunity detail / Proposal Generation and Proposal Analysis stages.
- Sections: recommended/selected line items, totals, approval action, commercial text editor, contract editor, slide PDF download, send/download actions.
- Label AI-filled sections with the standard AI Insight badge.
- Approve and Send are primary actions; Send opens a confirm modal (recipients, subject, body, attached PDFs).

---

# 18. Dashboard Screen

## Required Sections

1. Page header
2. Metric cards
3. Charts section
4. Pending tasks table
5. Follow-ups table

## Required Cards

- Leads created today
- Opportunities created today

## Required Charts

Last 30 days:

- Leads per day
- Opportunities per day
- Sales per day

## Required Tables

- Pending tasks
- Follow-ups

Charts must not be visually more important than actionable tables.

---

# 19. Responsive Rules

Primary target:

- Mobile-first
- Desktop

Tablet:

- Layout may stack cards and tables.

Mobile:

- Generate mobile-first screens unless explicitly requested.

---

# 20. Google Stitch Prompt

Use this prompt:

```markdown
Create a dark-mode-only internal AI-assisted CRM interface for two internal users.

Use a modern, minimalist, cold, technical visual style. The interface should be CRM-focused, operational, data-oriented, and mobile-first.

Use this exact palette:
- App background: #0F172A
- Sidebar background: #111827
- Surface: #1E293B
- Elevated surface: #273449
- Hover: #334155
- Active: #3730A3
- Primary action: #6366F1
- Primary hover: #4F46E5
- Primary active: #4338CA
- Accent blue: #0EA5E9
- AI accent: #8B5CF6
- Text primary: #F8FAFC
- Text secondary: #CBD5E1
- Text muted: #94A3B8
- Border default: #334155
- Success: #10B981
- Warning: #F59E0B
- Danger: #EF4444

Use Inter typography.
Use font weight 700 for titles and buttons; font weight 300 for body text.

Generate mobile-first screens for:
1. Dashboard
2. Leads / Clients table
3. Lead / Client detail modal
4. Opportunities kanban board
5. Opportunity detail modal (notes timeline + proposal entry points)
6. Commercial services catalog table
7. Proposal editor (line items, approval, text/contract editors)
8. Proposal send confirmation (recipients + PDF attachments)
9. Follow-ups table
10. Tasks table
11. AI recommendation panel
12. Settings page

Use a left sidebar, top header, and main content area.

Do not create a landing page.
Do not create marketing sections.
Do not use light mode.
Do not use pure black.
Do not use neon effects.
Do not use heavy gradients.
Do not invent additional colors.
Do not add slogans or public branding.
Do not make AI the central UI.
AI should appear as labeled suggestions, insights, and recommendation panels.
```

---

# 21. Final UI Summary

The UI should look like:

> A modern, minimalist, cold, technical, dark-mode CRM workspace with clear tables, kanban workflows, operational dashboards, subtle AI assistance, and exact indigo/blue design tokens.
