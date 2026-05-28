# ADR-013: Dark mode design system

## Status

Accepted

## Context

The Branding Manual and Design System define a **dark-mode-only**, minimalist, technical CRM UI with explicit color tokens, Inter typography, mobile-first layout, and AI accent usage. This is not a public marketing brand.

## Decision

- **Dark mode only** — no light mode.
- Implement **official design tokens** in Tailwind (see Design System §4–6): `bg-app`, `primary`, `accent`, `ai`, status colors, pipeline stage colors, etc.
- **Typography:** Inter; weight 700 for titles/buttons/metrics/Links; 300 for body/tables.
- **Layout:** Sidebar (240px / 72px collapsed) + header (64px) + main content; navigation items per Design System §10.
- **AI UI:** Purple `ai` token for badges/panels; stages Qualification and Proposal Generation use `ai` color.
- **Components:** Tables, Kanban, modals, dashboard cards per Design System; Livewire + Flux aligned with starter kit.

References:

- [Branding Manual](../03%20-%20Branding%20Manual.md)
- [Design System](../04%20-%20Design%20System.md)

## Consequences

- **Positive:**
  - Consistent UI for humans and AI UI generation tools (Figma/Stitch prompts in Design System §20).
- **Negative:**
  - No user preference for light theme.
- **Neutral:**
  - Follow `.cursor/skills/frontend-livewire-flux` and `docs/04 - Design System.md`; stack is Livewire + Flux per [ADR-001](ADR-001-technology-stack.md).
