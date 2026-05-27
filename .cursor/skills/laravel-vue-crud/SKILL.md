---
name: laravel-vue-crud
description: >
  Guides the creation and maintenance of full Laravel + Vue (Inertia + Vuetify)
  CRUDs in this project. Use this skill when implementing or updating
  CRUD-style resources, ensuring consistent backend workflow (migration, model,
  factory, seed, routes, controller, form requests, tests) and frontend
  workflow (Vue 3 + Vuetify 3 pages, shared snackbar, delete dialog, menu
  integration) with complete automated test coverage (Pest Browser for E2E; no Dusk).
---

# Laravel + Vue CRUD Workflow (GoViral)

This skill defines the standard end-to-end process for building CRUD features
in this project, from database to frontend and tests.

Always keep code in **English**, follow PSRs, and respect the existing
branding and frontend standards (see `frontend-vue-vuetify` skill).

---

## When to use this skill

Use this skill whenever:

- You are adding a **new resource** (e.g. DiscountCoupon, Campaign, Plan, etc.).
- You are extending an existing resource with **create/read/update/delete**
  capabilities.
- You are refactoring an ad-hoc implementation into a **proper CRUD**.

If the task touches a CRUD-like resource (database + HTTP + Vue UI), follow
this checklist.

---

## High-level workflow overview

Backend:

1. Read `.cursor/skills/backend-laravel/SKILL.md` for Laravel conventions (controllers, Form Requests, services, tests).
2. Create **Migration**.
3. Create **Model**.
4. Create **Factory**.
5. Create **Seed** (own file, wired in `DatabaseSeeder`).
6. Create **Routes** using `Route::resource` when possible.
7. Create **Controller**.
8. Create **Form Requests** (Create/Update may share one if has same validations).
9. Write **automated tests** (validation, database, edge cases).

Frontend:

1. Read frontend skill `frontend-vue-vuetify/SKILL.md`.
2. Define **routes** (if needed on the frontend side / Inertia).
3. Create **Index page**.
4. Create **Create/Update page** (single file).
5. Implement **shared snackbar** using Pinia store.
6. Implement **shared delete dialog** (single reusable dialog).
7. Update **sidebar/menu** to link to the Index page.
8. Keep frontend components as **dumb** as possible (minimal logic).

Testing:

1. Ensure all tests are written and passing for both backend and frontend
   (including Pest Browser tests).

---

## Backend Workflow (Laravel)

### 1. Migration

- Create a dedicated migration for the new resource in `database/migrations`.
- Follow Laravel naming conventions (e.g. `create_discount_coupons_table`).
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
  (e.g. `DiscountCouponSeeder`).
- Seeder should:
  - Use the resource’s factory.
  - Provide a reasonable baseline dataset for development and demos.
- Wire the seeder in `database/seeders/DatabaseSeeder.php`:
  - Call the specific seeder class from `run()` (do not inline logic there).

### 5. Routes (`routes/web.php`)

- Prefer resourceful routing:

  - Use `Route::resource(ResourceController::class)` when the RESTful pattern
    fits.
  - Only fall back to `Route::get`, `Route::post`, etc. when the resource
    pattern does not apply or you are adding non-CRUD endpoints.

- For Inertia/Vue pages:
  - Routes should return Inertia responses from the controller.
  - Apply middleware and naming conventions consistent with the project.

### 6. Controller

- Create a dedicated controller (e.g. `DiscountCouponController`) under
  `app/Http/Controllers`.
- Use dependency injection and keep controllers **thin**:
  - Move complex business logic into services where appropriate.
  - Use Form Requests for validation instead of inline validation.
- For a classic resource controller, implement at least:
  - `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
    (or the subset required by the feature).
- Return Inertia views (Vue pages) for UI endpoints.

### 7. Form Requests

- Create one or two Form Request classes under `app/Http/Requests`:
  - If create and update share the same rules, use **one** Form Request and
    reuse it (e.g. `DiscountCouponRequest`).
  - If rules differ significantly, split into `Store` and `Update` requests.
- Implement:
  - `rules()` with explicit, comprehensive validation rules.
  - `messages()` when custom messages are important.
- Use them in controller actions (`store`, `update`) instead of manual
  validation.

### 8. Automated backend tests

Use Pest (and Laravel’s testing utilities) to cover:

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
provided by Pest/Laravel.

---

## Frontend Workflow (Vue 3 + Vuetify 3)

### 1. Apply frontend standards

- **Always** start by reading:
  - `.cursor/skills/frontend-vue-vuetify/SKILL.md`
- Follow:
  - Vue 3 (Composition API preferred).
  - Vuetify 3 only (for all UI elements).
  - GoViral branding (dark mode, neon accents, typography, microcopy).
  - No native `alert`/`confirm`/`prompt` (use snackbar/dialog, etc.).
  - Pest Browser tests for all frontend flows.

### 2. Frontend routes (if needed)

- Use the existing routing pattern (Inertia + Laravel routes).
- Map backend resource routes to specific Vue pages:
  - Example: `ResourceController@index` → `Resources/Index.vue`.
  - Example: `ResourceController@create` / `@edit` → `Resources/Form.vue`.

### 3. Index page

- Implement an Index page (e.g. `Resources/Index.vue`) that:
  - Uses Vuetify layout components (`v-container`, `v-card`, etc.).
  - Displays tabular data using `v-data-table`.
  - Provides actions (create, edit, delete) via buttons or icons.
  - Respects dark theme and brand colors.
- Keep component logic thin:
  - Data and actions can be wired through simple composables/stores/services.
  - Components should primarily focus on rendering and simple event emitting.

### 4. Create/Update page (single file)

- Implement a single page component (e.g. `Resources/Form.vue`) used for both:
  - **Create** (empty state).
  - **Update** (prefilled with resource data).
- Requirements:
  - Reuse the same component for both operations (driven by props/route).
  - Use Vuetify form components: `v-form`, `v-text-field`, etc.
  - Each field must show validation errors:
    - Bind error messages from backend (Form Request) to the form fields.
  - After create/update/error:
    - Show a **snackbar** using the shared snackbar component (see next step).

### 4.1 Shared snackbar component (Pinia-based)

- Create a single **snackbar component** reused across the entire application.
- Implement a **Pinia store** (e.g. `useSnackbarStore`) that:
  - Holds snackbar state (visible, message, type, timeout).
  - Exposes a single method:
    - `show(message, type, timeout?)`.
    - `timeout` default must be `3000` ms.
  - `type` can indicate variants (e.g. success, error, info) to style the
    snackbar with Vuetify.
- The snackbar component:
  - Listens to the Pinia store.
  - Uses `v-snackbar` to display the message with correct style.
  - Is mounted once at a global level (e.g. in `AppLayout` or main layout).
- Usage pattern:
  - After successful create/update:
    - `snackbar.show('Resource created successfully.', 'success')`.
  - After error:
    - `snackbar.show('Failed to create resource.', 'error')`.

### 5. Shared delete dialog

- There should be **one shared delete dialog** used by all CRUD pages:
  - If a generic delete dialog does not exist, create it.
  - Use `v-dialog` for confirmation.
- The dialog:
  - Receives or accesses:
    - The item/context to delete.
    - Callbacks or events for confirm/cancel.
  - Shows clear microcopy aligned with branding.
  - Uses primary/secondary buttons with clear labels (e.g. "Cancel", "Delete").
- CRUD pages should integrate with this shared dialog instead of implementing
  their own confirmation UIs.

### 6. Sidebar/menu integration

- Update the sidebar (or global navigation) to include a menu item:
  - Label consistent with brand voice and domain (e.g. "Discount Coupons").
  - Link that routes to the resource Index page.
- Ensure:
  - Active state styling works in dark mode.
  - Icons/colors follow branding guidelines.

### 7. Prefer dumb frontend components

- Keep components as **dumb** as possible:
  - UI components focus on rendering props and emitting events.
  - Move heavy logic (data fetching, transformations, business rules) into:
    - Composables.
    - Stores.
    - Backend services (Laravel).
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

- Use **Pest Browser** only for browser tests. **Do not** add Laravel Dusk.
- Add **`data-test`** (or other stable hooks) on interactive elements that tests target; see `.cursor/skills/frontend-vue-vuetify/SKILL.md`.
- For each CRUD UI:
  - Add **Pest Browser tests** following the official docs:
    - [`https://pestphp.com/docs/browser-testing`](https://pestphp.com/docs/browser-testing)
  - Cover at least:
    - Navigation to Index page.
    - Presence of main UI elements and CTAs.
    - Basic create/update flow:
      - Fill form.
      - Submit.
      - See success snackbar and resulting state.
    - Basic delete flow:
      - Open delete dialog.
      - Confirm delete.
      - See success snackbar and removal from list.
- Integrate with existing **route smoke tests** and overall test strategy.

- All tests (unit, feature, browser) must pass before the CRUD is considered
  done.

---

## Quick checklist

- [ ] Migration created and applied.
- [ ] Model created (with factory, casts, relations).
- [ ] Factory and dedicated seeder created and wired in `DatabaseSeeder`.
- [ ] Routes defined, using `Route::resource` where applicable.
- [ ] Controller implemented with thin actions and Inertia responses.
- [ ] Form Request(s) created and used for `store`/`update`.
- [ ] Backend tests:
  - [ ] Validation and messages.
  - [ ] Database assertions.
  - [ ] Edge cases.
- [ ] Frontend uses Vue 3 + Vuetify 3 following `frontend-vue-vuetify` skill.
- [ ] Index page with `v-data-table` and proper actions.
- [ ] Single Create/Update page with field validation and snackbar feedback.
- [ ] Shared snackbar component + Pinia store (`show(message, type, timeout = 3000)`).
- [ ] Shared delete dialog reused across CRUD pages.
- [ ] Sidebar/menu updated with link to Index page.
- [ ] Browser tests (Pest) covering navigation, CRUD flows, and basic smoke.
- [ ] All tests passing.

