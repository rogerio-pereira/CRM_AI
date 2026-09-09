---
name: frontend-livewire-flux
description: >
  Guides frontend development using Laravel Livewire and Flux UI aligned with the
  project's Design System and Branding Manual. Use when creating or reviewing
  screens, Livewire components, Blade layouts, navigation, or UI tests—Flux
  components, project theme, Pest Browser only (no Laravel Dusk), stable E2E selectors.
---

# Frontend Livewire + Flux

This skill defines how to build and maintain UI when the project uses **Livewire + Flux**.

---

## When to use this skill

Use this skill whenever you:

- Create or change **Livewire** pages or components (`app/Livewire/`, `resources/views/livewire/`).
- Create or change **Blade layouts** and partials.
- Add or update **navigation** (sidebar, header).
- Add or update **frontend / browser tests** for UI flows.

If the task touches UI/UX, Livewire, Flux, or navigation, follow these instructions.

---

## Mandatory rules

1. **Component type (class-based only)**
   - **Always** use **class-based** Livewire components for new work (PHP class + Blade view).
   - Class: `app/Livewire/{Path}/{Name}.php` (`App\Livewire\...`).
   - View: `resources/views/livewire/{path}/{name}.blade.php`.
   - Create via `./vendor/bin/sail artisan make:livewire Path/Name` (default `type` is `class` in `config/livewire.php`).
   - **Do not** add new SFC (`⚡*.blade.php` with anonymous class in Blade) or MFC-only components.
   - Full-page routes: `Route::livewire('uri', \App\Livewire\Path\Name::class)` (or `Route::get` with the class).
   - Legacy `pages::` SFC under `resources/views/pages/` may remain until migrated; see `.cursor/rules/livewire-class-components.mdc`.

2. **Stack**
   - **Livewire** for interactive UI and form handling.
   - **Flux** (`flux:*` components) for buttons, inputs, tables, modals, toasts, navigation.
   - **Tailwind CSS** tokens per the project's Design System (do not invent colors or spacing).

3. **Design System**
   - Read the project's Design System and Branding docs before building screens (e.g. `docs/04 - Design System.md`, `docs/03 - Branding Manual.md` — adapt paths).
   - Follow the project's theme (e.g. dark-only, density, layout patterns for lists, boards, dashboards).
   - Secondary insights (e.g. AI labels) should support the main task UI, not replace it.

4. **Routing**
   - Prefer `Route::livewire('path', \App\Livewire\Path\Page::class)` for new full pages.
   - Use `config/livewire.php` `component_layout` (`layouts::app`) for page layout.
   - Use Livewire layouts under `resources/views/layouts/`.

5. **Feedback**
   - Success/error: `Flux::toast()` or Flux callouts/alerts.
   - Do not use browser `alert()` / `confirm()` / `prompt()`.

6. **Selectors for tests**
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

- [ ] Class-based Livewire (PHP class in `app/Livewire/`, view in `resources/views/livewire/`)?
- [ ] Livewire + Flux (no raw HTML where a Flux component exists)?
- [ ] Matches Design System (theme, density, tokens)?
- [ ] Toasts/modals via Flux (no native dialogs)?
- [ ] `data-test` on elements used in tests?
- [ ] Pest Browser / Feature tests updated?
- [ ] No Dusk?
