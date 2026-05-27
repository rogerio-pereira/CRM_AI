---
name: laravel-livewire-crud
description: >
  Guides the creation and maintenance of full Laravel + Livewire (+ Flux) CRUDs
  in this project. Use when implementing CRUD-style resources, ensuring
  consistent backend workflow (migration, model, factory, seed, routes, controller
  or Livewire page, form requests, tests) and frontend workflow (Livewire pages,
  Flux UI, toasts, delete modal, menu integration) with complete test coverage
  (Pest Browser for E2E where needed; no Dusk).
---

# Laravel + Livewire CRUD Workflow (Internal CRM)

End-to-end process for CRUD features from database to UI and tests.

Always keep code in **English** (unless noted otherwise), follow PSRs, and respect `frontend-livewire-flux` and `backend-laravel` skills.

---

## When to use this skill

- Adding a **new resource** (e.g. Lead, Opportunity, Task).
- Extending a resource with full **create/read/update/delete**.
- Refactoring ad-hoc code into a proper CRUD.

---

## High-level workflow

**Backend:**

1. Read `.cursor/skills/backend-laravel/SKILL.md`.
2. Migration → Model → Factory → Seeder → Routes → Controller (and/or Livewire) → Form Requests → tests.

**Frontend:**

1. Read `.cursor/skills/frontend-livewire-flux/SKILL.md`.
2. Livewire **index** page (list + actions).
3. Livewire **form** page for create/edit.
4. Delete confirmation via **Flux modal**.
5. **Flux::toast** for success/error feedback.
6. Update **sidebar/menu** navigation.

**Testing:**

1. Feature tests: validation, authorization, database assertions.
2. Pest Browser tests for critical UI flows when appropriate.
3. Update Smoke Test

---

## Backend Workflow (Laravel)

Follow `backend-laravel` for migrations, models, factories, seeders, Form Requests, thin controllers, and services.

- Use `Route::resource` or explicit routes pointing to controllers and/or `Route::livewire`.
- Controllers return views or delegate to Livewire; avoid Inertia.

---

## Frontend Workflow (Livewire + Flux)

### Index page

- Livewire component listing records (table or Flux table).
- Actions: create, edit, delete (with modal confirm).
- Pagination and filters as required by FDR.

### Form (create / edit)

- Single Livewire form component or shared form with mode flag.
- Validate with Livewire `validate()` or Form Request patterns.
- On success: redirect + `Flux::toast` or flash.

### Delete

- Reusable Flux modal pattern; confirm before `delete()`.

### Navigation

- Add entry to app sidebar (`layouts/app/sidebar.blade.php` or equivalent).

---

## Testing

### Feature tests

- HTTP/Livewire tests for CRUD endpoints and validation.
- `assertDatabaseHas` / `assertDatabaseMissing` for persistence.

### Browser tests (Pest)

- Visit index, create record, edit, delete smoke.
- Use `@data-test` selectors per `frontend-livewire-flux` skill.
- Ensure no smoke is detected. See [Smoke Testing](https://pestphp.com/docs/browser-testing#content-assertnosmoke)

---

## Quick checklist

- [ ] Migration, model, factory, seeder wired in `DatabaseSeeder`.
- [ ] Routes and authorization in place.
- [ ] Form Requests or Livewire validation rules.
- [ ] Livewire index + form with Flux components.
- [ ] Toasts and delete modal.
- [ ] Menu updated.
- [ ] Feature (+ browser if needed) tests passing.
- [ ] Pint clean.
