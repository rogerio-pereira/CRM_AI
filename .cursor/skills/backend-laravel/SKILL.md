---
name: backend-laravel
description: >
  Guides backend development in Laravel projects using this workflow. Use when
  creating or updating backend features, including routes, controllers,
  services, models, database structure, validation, queues, and tests, ensuring
  consistency with project architecture, coding standards, and testing
  requirements.
---

# Backend Development (Laravel)

Design and implement backend features aligned with project conventions.

Combine this skill with the stack the project uses:

- **Livewire + Flux:** `laravel-livewire-crud`, `frontend-livewire-flux`
- **Vue + Inertia + Vuetify:** `laravel-vue-crud`, `frontend-vue-vuetify`

All backend code must be in **English** and follow PSRs.

- **Conditional style:**
  - Do not use ternary operators (`condition ? a : b`) in backend PHP code.
  - Prefer explicit `if` / `else` blocks or early returns for clarity.

- **Formatting convention:**
  - Use one statement per line (including fluent chains).
  - One method call per line in chained calls.
  - Multi-line argument lists and arrays formatted consistently and readably.

---

## When to use this skill

- Add or change **routes** or HTTP endpoints.
- Create or refactor **controllers**, **Form Requests**, **services**.
- Add or change **models**, **migrations**, **factories**, **seeders**.
- Work on **queues**, **jobs**, **events**, **listeners**, or **Horizon**.
- Implement or update **tests** (Feature, Unit, Browser).

---

## Architectural principles

- Keep **controllers thin**; move domain logic to services.
- Prefer **services with interfaces** when swapping or mocking matters.
- Use **Form Requests** for non-trivial validation.
- Keep **models focused** on relationships and simple helpers.
- **Single responsibility** per class.

---

## Routes and HTTP layer

- Define HTTP routes in `routes/web.php` (and `routes/settings.php`, `routes/api.php` where applicable).
- Prefer `Route::resource` for REST-style resources.
- Grouping by middleware, namespace, or prefix where appropriate.
- Use `Route::livewire()` with **class-based** components (`\App\Livewire\...::class`) for new full-page UI (see `.cursor/rules/livewire-class-components.mdc`).
- Name routes consistently with `->name()`.
- If needed, return Livewire responses in controllers, not in the route file.

### API routes (if applicable)

- Place API endpoints in `routes/api.php` (or consistent project location).
- Use proper HTTP verbs and status codes.
- Apply auth/middleware suited to API consumption.

---

## Controllers

- Place controllers under `app/Http/Controllers`.
- Keep them **small and readable**:
- Inject services via constructor when needed.
- Return views, redirects, or JSON as appropriate; use Livewire for interactive UI.
- Patterns to follow:
  - For simple reads/writes: use Eloquent directly with validation guarded by
    Form Requests.
  - For complex flows: call a service.
- Avoid:
  - Large methods.
  - Deeply nested `if/else` trees.
  - Mixing concerns (validation, business logic, formatting, side effects) inside controller methods.
---

## Validation and Form Requests

- Always use **Form Request classes** for non-trivial validation:
  - Store them in `app/Http/Requests`.
  - Use meaningful names (e.g. `StoreResourceRequest`, `UpdateResourceRequest`).
- In Form Requests:
  - Implement `rules()` to fully describe expected input.
  - Implement `messages()` for custom messages when helpful.
  - Implement `authorize()` to restrict access, or explicitly return `true`
    when handled elsewhere.
- Controllers should type-hint Form Requests:
  - Example: `public function store(StoreResourceRequest $request)`.

---

## Models, migrations, factories, seeders

Follow standard Laravel patterns. Every model must have a factory. Use seeders with factories for demo data.

### Migrations

- Add migrations in `database/migrations`:
  - Use expressive names and `up`/`down` methods.
  - Always include `timestamps()` unless there is a strong reason not to.
  - Use `softDeletes()` when the domain calls for soft deletion.
  - Add indexes and constraints where appropriate (foreign keys, uniques).

### Models

- Place models in `app/Models`.
- For each model:
  - Define table name only when non-standard.
  - Define `$fillable` to protect against mass assignment issues.
  - Add relationships with clear method names (`user`, `discountCoupons`, etc.).
  - Use `$casts` for booleans, dates, enums, JSON, etc.
- Keep heavy business rules in services, not in the model.
- **Eloquent calls:** do not wrap simple operations in `Model::query()`. Use direct static or instance methods (see `.cursor/rules/laravel-eloquent-models.mdc`):
  - Prefer `User::create(...)`, `User::firstOrCreate(...)`, `$model->update(...)`, `$model->delete()`.
  - Avoid `User::query()->create(...)` and similar when no query chain is needed.
  - Use `where()` / query builder only for scoped or bulk operations.

### Factories

- Every model must have a corresponding factory in `database/factories`.
- Factories should:
  - Provide a valid default state.
  - Use realistic data within project constraints.
  - Offer additional states when necessary (e.g. `expired`, `active`).
- Use factories in:
  - Tests (Feature/Unit).
  - Seeders (instead of hard-coded arrays).

### Seeders

- Each significant resource should have its own seeder in `database/seeders`.
- Seeders should:
  - Use factories to generate test/demo data.
  - Avoid long, hard-coded data lists when simple factories suffice.
- Register each seeder in `DatabaseSeeder`:
  - Call the seeder class from `run()`.

---

## Services, jobs, events, queues

### Services

- Store services under `app/Services` (or a similar organized namespace).
- Each service should:
  - Encapsulate a clear unit of behavior (e.g. processing a record, transitioning workflow state, computing dashboard metrics).
  - Be dependency-injection friendly (inject repositories/clients instead of
    using facades everywhere).
- When appropriate, define an interface and bind it in a service provider.

### Jobs and queues

- Use jobs for work that may be slow or should run asynchronously.
- Jobs live under `app/Jobs`.
- Each job should:
  - Implement `handle()` with clear, idempotent logic if possible.
  - Respect configured `tries`, `timeout`, and `backoff` policies.
- Use the project’s queue configuration:
  - `QUEUE_CONNECTION=redis`.
  - Queue worker / Horizon configs as described in `AGENTS.md` and ADRs.
- Horizon for queue monitoring when installed.

### Events and listeners

- Use events/listeners to decouple side effects:
  - Example: dispatch event when a report is generated; listeners send emails, update stats, etc.
- Place events under `app/Events` and listeners under `app/Listeners`.

---

## Error handling and responses

- Use Laravel’s exception handling:
  - Throw domain-specific exceptions where needed.
  - Customize rendering behavior in `app/Exceptions/Handler.php` when required.
- For HTTP responses:
  - Use appropriate status codes (200, 201, 204, 400, 404, 422, 500, etc.).
  - For JSON APIs, return structured JSON with clear error messages.
  - For Livewire UI: redirects, `Flux::toast`, validation errors on the component.

---

## Testing (backend)

All backend changes should come with or update appropriate tests.

### Types of tests

- **Unit tests**:
  - For small, isolated classes (services, helpers, pure functions).
- **Feature tests**:
  - For HTTP endpoints, Form Requests, database interactions, and integrated flows.
- **Browser tests (Pest Browser)**:
  - For end-to-end flows where the backend and frontend meet; often managed under the frontend/testing rules but may require backend setup.
  - Use **Pest Browser** only; **do not** use Laravel Dusk for this project.

### What to test for

- **Validation**:
  - Required fields, formats, min/max constraints, uniqueness, etc.
  - Ensure validation messages are as expected for critical user paths.

- **Authorization**:
  - Users without permission cannot access/perform actions.
  - Authorized users can perform all expected operations.

- **Database state**:
  - Use:
    - `$this->assertDatabaseHas(...)`
    - `$this->assertDatabaseMissing(...)`
    - `$this->assertDatabaseCount(...)`
  - Ensure records are created, updated, deleted as designed.

- **Edge cases**:
  - Non-existent resources (404 or graceful handling).
  - Boundary values.
  - Multiple concurrent updates when applicable.

### Test quality

- Keep tests:
  - Clear and readable.
  - Focused on one concern per test.
  - Using factories and helpers instead of manual fixtures.
- Ensure the project’s coverage requirements are respected (see `AGENTS.md` and test commands).

---

## Quick checklist

- [ ] Routes added/updated in the correct file with proper HTTP verbs and names.
- [ ] Controllers thin, delegating complex logic to services or domain classes.
- [ ] Form Requests created and used for validation where appropriate.
- [ ] Models correctly configured (fillable/guarded, casts, relationships).
- [ ] Migrations created with proper schemas, indexes, and constraints.
- [ ] Factories present and used in tests and seeders.
- [ ] Seeders created per resource and wired in `DatabaseSeeder`.
- [ ] Services/jobs/events used to encapsulate complex or async behavior.
- [ ] Error handling and responses use appropriate status codes and messages.
- [ ] Backend tests added/updated:
  - [ ] Validation.
  - [ ] Authorization (if applicable).
  - [ ] Database assertions.
  - [ ] Edge cases.
- [ ] All tests passing via Sail test commands.
- [ ] All type tests passing via Sail test commands.
- [ ] Pint passes with no style errors.
