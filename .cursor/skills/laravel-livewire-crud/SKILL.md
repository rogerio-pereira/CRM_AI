---
name: laravel-livewire-crud
description: >
  Guides the creation and maintenance of full Laravel + Livewire (+ Flux) CRUDs
  in Laravel projects using this workflow. Use when implementing or updating CRUD-style
  resources, ensuring consistent backend workflow (migration, model, factory,
  seed, routes, controller, form requests, tests) and frontend workflow
  (Livewire pages, Flux UI, shared toasts, delete modal, menu integration) with
  complete automated test coverage (Pest Browser for E2E; no Dusk).
---

# Laravel + Livewire CRUD Workflow

This skill defines the standard end-to-end process for building CRUD features
in the repository, from database to frontend and tests.

Always keep code in **English**, follow PSRs, and respect the existing
branding and frontend standards (see `frontend-livewire-flux` skill).

---

## When to use this skill

Use this skill whenever:

- You are adding a **new resource** (e.g. Lead, Opportunity, Task, etc.).
- You are extending an existing resource with **create/read/update/delete**
  capabilities.
- You are refactoring an ad-hoc implementation into a **proper CRUD**.

If the task touches a CRUD-like resource (database + HTTP + Livewire UI), follow
this checklist.

---

## High-level workflow overview

Backend:

1. Read `.cursor/skills/backend-laravel/SKILL.md` for Laravel conventions (controllers, Form Requests, services, tests).
2. Create **Migration**.
3. Create **Model**.
4. Create **Factory**.
5. Create **Seed** (own file, wired in `DatabaseSeeder`).
6. Create **Routes** using `Route::resource` and/or `Route::livewire` when appropriate.
7. Create **Controller** (if the resource uses classic HTTP actions).
8. Create **Form Requests** (Create/Update may share one if they have the same validations).
9. Write **automated tests** (validation, database, edge cases).

Frontend:

1. Read frontend skill `frontend-livewire-flux/SKILL.md`.
2. Define **routes** (`Route::livewire` or controller + Livewire views).
3. Create **Index** Livewire page.
4. Create **Create/Update** Livewire page (single component when possible).
5. Implement **feedback** with `Flux::toast` (or project toast pattern).
6. Implement **shared delete modal** (single reusable Flux modal).
7. Update **sidebar/menu** to link to the Index page.
8. Keep Livewire components focused: UI in the component, domain logic in services.

Testing:

1. Ensure all tests are written and passing for both backend and frontend
   (including Pest Browser tests when the project uses them).

---

## Backend Workflow (Laravel)

### 1. Migration

- Create a dedicated migration for the new resource in `database/migrations`.
- Follow Laravel naming conventions (e.g. `create_leads_table`).
- Include:
  - Primary key (typically `id`).
  - Foreign keys with proper constraints where relevant.
  - Timestamps (`timestamps()`).
  - Soft deletes (`softDeletes()`) if the domain requires it.
- Use explicit column types and reasonable defaults.

### 2. Model

- Create an Eloquent model under `app/Models`.
- Ensure:
  - Correct table name if non-standard.
  - `$fillable` / `$guarded` are set appropriately.
  - Relationships are declared clearly.
  - Casts (`$casts`) defined for dates, booleans, JSON, etc.
- Every model must have a matching **factory** (see next step).

### 3. Factory

- Create a factory under `database/factories` for the model.
- Use `Faker` data consistent with the domain.
- Ensure factory supports:
  - Valid default state.
  - Any important variations (states) if needed.
- Factories should be used in tests and seeds, not hardcoded `Model::create`
  everywhere.

### 4. Seed

- Create a **dedicated seeder** file per resource under `database/seeders`
  (e.g. `LeadSeeder`).
- Seeder should:
  - Use the resource’s factory.
  - Provide a reasonable baseline dataset for development and demos.
- Wire the seeder in `database/seeders/DatabaseSeeder.php`:
  - Call the specific seeder class from `run()` (do not inline logic there).

### 5. Routes (`routes/web.php`)

- Prefer resourceful routing when it fits:

  - Use `Route::resource(LeadController::class)` (or equivalent) for REST-style
    endpoints.
  - Only fall back to `Route::get`, `Route::post`, etc. when the resource
    pattern does not apply or you are adding non-CRUD endpoints.

- For Livewire full pages:

  - Use class-based components: `Route::livewire('leads', \App\Livewire\Leads\Index::class)`.
  - Apply middleware and route names consistent with the rest of the app.

### 6. Controller (when used)

- Create a dedicated controller (e.g. `LeadController`) under
  `app/Http/Controllers` when actions are not fully handled by Livewire.
- Use dependency injection and keep controllers **thin**:
  - Move complex business logic into services where appropriate.
  - Use Form Requests for validation instead of inline validation.
- For a classic resource controller, implement at least:
  - `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
    (or the subset required by the feature).
- Return Blade views, redirects, or delegate to Livewire pages as designed.

### 7. Form Requests

- Create one or two Form Request classes under `app/Http/Requests`:
  - If create and update share the same rules, use **one** Form Request and
    reuse it (e.g. `LeadRequest`).
  - If rules differ significantly, split into `Store` and `Update` requests.
- Implement:
  - `rules()` with explicit, comprehensive validation rules.
  - `messages()` when custom messages are important.
- Use them in controller actions (`store`, `update`) or validate in Livewire via
  dedicated rules / Form Request patterns.

### 8. Automated backend tests

Use Pest (or PHPUnit, per project setup) and Laravel’s testing utilities to cover:

1. **Form validations and messages**
   - Test invalid payloads:
     - Missing required fields.
     - Wrong formats.
     - Domain-specific constraints (e.g. unique, date ranges, numeric bounds).
   - Assert correct validation errors and messages:
     - Validate the exact fields and messages expected.

2. **Database assertions**
   - Use helpers such as:
     - `$this->assertDatabaseHas(...)`
     - `$this->assertDatabaseMissing(...)`
     - `$this->assertDatabaseCount(...)`
   - After `store`/`update`/`destroy` actions, assert that the database
     reflects the expected state.

3. **Edge cases**
   - Unauthorized access or missing permissions where relevant.
   - Trying to update or delete non-existing records (404 / graceful handling).
   - Boundary values (e.g. min/max lengths, date boundaries).

Ensure tests are deterministic and isolated, using database refresh utilities
provided by the test stack.

---

## Frontend Workflow (Livewire + Flux)

### 1. Apply frontend standards

- **Always** start by reading:
  - `.cursor/skills/frontend-livewire-flux/SKILL.md`
- Follow:
  - **Livewire** for interactive UI and forms.
  - **Flux** (`flux:*`) for buttons, inputs, tables, modals, toasts, layout.
  - Project branding and Design System (theme, tokens, layout patterns per project docs).
  - No native `alert`/`confirm`/`prompt` (use Flux modal / toast).
  - Pest Browser tests for frontend flows when the project uses them.

### 2. Frontend routes

- Use **class-based** Livewire components (`app/Livewire/`, views in `resources/views/livewire/`).
  Create with `./vendor/bin/sail artisan make:livewire Resource/Index` (see `config/livewire.php` and `.cursor/rules/livewire-class-components.mdc`).
- Use the project routing pattern:
  - `Route::livewire('uri', \App\Livewire\Resource\Index::class)` for full-page components, and/or
  - Controller routes that return views embedding Livewire.
- Map backend resource routes to Livewire pages, for example:
  - Index → `\App\Livewire\Leads\Index::class`.
  - Create/Edit → `\App\Livewire\Leads\Form::class` (single component for both modes when possible).

### 3. Index page

- Implement an Index Livewire page that:
  - Uses Flux layout components (`flux:card`, table patterns, etc.).
  - Lists records (Flux table or project table component).
  - Provides actions (create, edit, delete) via Flux buttons.
  - Respects dark theme and design tokens.
- Keep component logic thin:
  - Load data via Eloquent queries or dedicated query objects/services.
  - Delegate complex rules to backend services.

### 4. Create/Update page (single component when possible)

- Implement one Livewire form component used for both:
  - **Create** (empty state).
  - **Update** (prefilled with resource data).
- Requirements:
  - Reuse the same component for both operations (route param / property for mode).
  - Use Flux form fields (`flux:input`, `flux:select`, etc.).
  - Each field must show validation errors:
    - Bind `$errors` / Livewire validation messages to fields.
  - After create/update/error:
    - Show **`Flux::toast`** (success or error variant).

### 4.1 Shared toast feedback

- Use a **consistent toast pattern** across the application:
  - Prefer `Flux::toast(variant: 'success' | 'danger', text: '...')` after mutations.
  - Keep messages short and actionable.
- Usage pattern:
  - After successful create/update:
    - `Flux::toast(variant: 'success', text: __('Resource created successfully.'))`.
  - After error:
    - `Flux::toast(variant: 'danger', text: __('Failed to save resource.'))`.

### 5. Shared delete modal

- There should be **one shared delete confirmation pattern** used by CRUD pages:
  - Reuse an existing `flux:modal` component or create a single shared partial.
- The modal:
  - Receives or binds:
    - The item/context to delete.
    - Confirm/cancel actions (Livewire methods).
  - Uses clear labels (e.g. "Cancel", "Delete").
- CRUD pages should integrate with this shared modal instead of implementing
  one-off confirmation UIs.

### 6. Sidebar/menu integration

- Update the sidebar (or global navigation) to include a menu item:
  - Label consistent with domain (e.g. "Leads", "Opportunities").
  - Link that routes to the resource Index page.
- Ensure:
  - Active state styling works in dark mode.
  - Icons/colors follow branding guidelines.

### 7. Prefer thin Livewire components

- Keep components **focused on UI and orchestration**:
  - Render state and handle user events.
  - Move heavy logic (workflows, integrations, AI calls) into:
    - Laravel services.
    - Queued jobs.
- This makes CRUD UIs easier to test and maintain.

---

## Testing (Backend + Frontend)

### 1. Backend tests

- Ensure:
  - Validation tests cover success and failure paths.
  - Database tests assert correct persistence and deletion.
  - Edge cases are exercised (permissions, not found, invalid states, etc.).
- Tests must be green before considering the CRUD implementation complete.

### 2. Frontend Browser tests (Pest)

- Use **Pest Browser** only for browser tests when the project uses Pest. **Do not** add Laravel Dusk.
- Add **`data-test`** (or other stable hooks) on interactive elements that tests target; see `.cursor/skills/frontend-livewire-flux/SKILL.md`.
- For each CRUD UI:
  - Add **Pest Browser tests** following the official docs:
    - [`https://pestphp.com/docs/browser-testing`](https://pestphp.com/docs/browser-testing)
  - Cover at least:
    - Navigation to Index page.
    - Presence of main UI elements and CTAs.
    - Basic create/update flow:
      - Fill form.
      - Submit.
      - See success toast and resulting state.
    - Basic delete flow:
      - Open delete modal.
      - Confirm delete.
      - See success feedback and removal from list.
- Integrate with existing **route smoke tests** and overall test strategy when present.
- Use smoke helpers (e.g. `assertNoSmoke`) per Pest Browser docs when applicable.

- All tests (unit, feature, browser) must pass before the CRUD is considered done.

---

## Quick checklist

- [ ] Migration created and applied.
- [ ] Model created (with factory, casts, relations).
- [ ] Factory and dedicated seeder created and wired in `DatabaseSeeder`.
- [ ] Routes defined (`Route::resource` and/or `Route::livewire` as applicable).
- [ ] Controller implemented with thin actions (if used).
- [ ] Form Request(s) created and used for `store`/`update` (or Livewire validation equivalent).
- [ ] Backend tests:
  - [ ] Validation and messages.
  - [ ] Database assertions.
  - [ ] Edge cases.
- [ ] Frontend uses class-based Livewire + Flux following `frontend-livewire-flux` skill.
- [ ] Index page with table/list and proper actions.
- [ ] Single Create/Update component with field validation and toast feedback.
- [ ] Shared delete modal reused across CRUD pages.
- [ ] Sidebar/menu updated with link to Index page.
- [ ] Browser tests (Pest) covering navigation, CRUD flows, and basic smoke (when used).
- [ ] All tests passing.
