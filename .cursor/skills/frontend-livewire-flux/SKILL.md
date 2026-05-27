---
name: frontend-livewire-flux
description: >
  Guides all frontend development in the project using Laravel Livewire and Flux
  UI aligned with the Design System and Branding Manual. Use this skill whenever
  creating, changing or reviewing screens, Livewire components, Blade layouts,
  navigation, or UI tests, ensuring Flux components, dark CRM theme, Pest Browser
  tests only (no Laravel Dusk), and stable E2E selectors.
---

# Frontend Livewire + Flux (Internal CRM)

This skill defines how to build and maintain UI in this project.

---

## When to use this skill

Use this skill whenever you:

- Create or change **Livewire** pages or components (`resources/views/pages/`, class-based or `Route::livewire`).
- Create or change **Blade layouts** and partials.
- Add or update **navigation** (sidebar, header).
- Add or update **frontend / browser tests** for UI flows.

If the task touches UI/UX, Livewire, Flux, or navigation, follow these instructions.

---

## Mandatory rules

1. **Stack**
   - **Livewire** for interactive UI and form handling.
   - **Flux** (`flux:*` components) for buttons, inputs, tables, modals, toasts, navigation.
   - **Tailwind CSS** tokens per `docs/04 - Design System.md` (do not invent colors or spacing).

2. **Design System**
   - Read `docs/04 - Design System.md` and `docs/03 - Branding Manual.md` before building screens.
   - Dark mode only; soft dark theme; CRM-first layouts (pipeline, leads, tasks).
   - AI insights as labeled support, not the central UI.

3. **Routing**
   - Prefer `Route::livewire('path', 'pages::name')` for full pages (see `routes/settings.php`).
   - Use Livewire layouts under `resources/views/layouts/`.

4. **Feedback**
   - Success/error: `Flux::toast()` or Flux callouts/alerts.
   - Do not use browser `alert()` / `confirm()` / `prompt()`.

5. **Selectors for tests**
   - Add **`data-test="..."`** on interactive elements targeted by E2E tests.
   - Use explicit `name` on form fields where helpful.

---

## UI component rules (Flux)

- Use Flux for buttons, inputs, selects, tables, modals, dropdowns, sidebar, headings.
- Tables: prefer Flux table patterns from the Design System.
- Modals: `flux:modal` for confirmations (e.g. delete).
- Navigation: `flux:sidebar`, `flux:navlist`, existing app layout patterns.

---

## Frontend tests: Pest Browser (mandatory)

- Use **Pest Browser** only for browser/E2E tests. **Do not** use Laravel Dusk.
- Reference: [Pest browser testing](https://pestphp.com/docs/browser-testing)
- Cover: page load, key assertions (`assertSee`), navigation, form submit, validation errors.
- Add or update smoke route tests when introducing new authenticated routes.

---

## Workflow checklist

1. Read Design System + Branding for the screen type.
2. Implement Livewire page/component with Flux markup.
3. Wire route and menu entry.
4. Add `data-test` hooks for critical actions.
5. Add Feature and/or Pest Browser tests.
6. Run `./vendor/bin/sail npm run build` (or dev server) before full test suite when browser tests are included.

---

## Quick checklist

- [ ] Livewire + Flux (no raw HTML where a Flux component exists)?
- [ ] Matches Design System (dark, CRM density, tokens)?
- [ ] Toasts/modals via Flux (no native dialogs)?
- [ ] `data-test` on elements used in tests?
- [ ] Pest Browser / Feature tests updated?
- [ ] No Dusk?
