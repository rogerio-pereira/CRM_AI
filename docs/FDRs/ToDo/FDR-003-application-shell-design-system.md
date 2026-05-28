# FDR-003: Application shell and design system

**Feature:** 03  
**Status:** Approved  
**Reference:** [03 Application shell and design system](../../05%20-%20Feature%20List.md#f03-application-shell-design-system), [ADR-013](../../ADRs/ADR-013-dark-mode-design-system.md), [Design System](../../04%20-%20Design%20System.md)

---

## How it works

1. Extend app layout: **sidebar** (240px / 72px collapsed), **header** (64px), **main** (`bg-app`, 24px padding).
2. Map Design System color tokens to **Tailwind** `theme.extend.colors` (exact hex from Design System §6).
3. Load **Inter** font; apply weight 700/300 rules globally.
4. Navigation items: Dashboard, Leads/Clients, Opportunities (Kanban), Follow-ups, Tasks, Settings.
5. Sidebar states: default / hover / active (`bg-active` `#3730A3`).
6. Provide empty-state placeholder pages for routes not yet implemented.

---

## How to test

- Visual check: dark mode only; no light theme toggle exposed.
- Navigation highlights active route; collapsed sidebar works on desktop.
- Mobile-first: sidebar collapses appropriately on small viewports.
- Token spot-check: primary `#6366F1`, AI `#8B5CF6`, app bg `#0F172A`.

---

## Acceptance criteria

- [ ] App shell matches Design System §10 layout.
- [ ] Tailwind config includes all official tokens from Design System §6.
- [ ] Inter typography weights applied per branding rules.
- [ ] All primary nav entries exist and route correctly (stub pages allowed).
- [ ] No marketing/landing page in authenticated area.

---

## Deployment notes

- Run `npm run build` after token changes for production assets.
