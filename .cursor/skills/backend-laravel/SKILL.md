---
name: backend-laravel
description: >
  Guides all backend development in this Laravel project. Use this skill when
  creating or updating backend features, including routes, controllers,
  services, models, database structure, validation, queues, and tests, ensuring
  consistency with project architecture, coding standards, and testing
  requirements.
---

# Backend Development (Laravel) – GoViral

This skill defines how to design and implement backend features in this
Laravel-based project, keeping architecture clean, testable, and aligned with
the project’s conventions.

Combine this skill with:

- `laravel-vue-crud` for full CRUD flows.
- `frontend-vue-vuetify` for UI concerns.

All backend code must be in **English** and follow PSRs.

- **Conditional style:**
  - Do not use ternary operators (`condition ? a : b`) in backend PHP code.
  - Prefer explicit `if` / `else` blocks or early returns for clarity.

- **Formatting convention (controllers, requests, commands, models):**
  - Use one statement per line (including fluent chains).
  - Match the indentation / line-break style used in `app/Http/Controllers/Core/DiscountCouponController.php`:
    - continuation lines aligned to the surrounding block;
    - one method call per line in chained calls;
    - multi-line argument lists and arrays formatted consistently and readably.

---

## When to use this skill

Use this skill whenever you:

- Add or change **routes** or HTTP endpoints.
- Create or refactor **controllers**, **Form Requests**, **services**, etc.
- Add or change **models**, **migrations**, **factories**, or **seeders**.
- Work on **queues**, **jobs**, **events**, **listeners**, or **Horizon**.
- Implement or update **tests** (Feature, Unit, Browser-related backend setup).

If work touches Laravel backend logic or data, follow these guidelines.

---

## Architectural principles

- Keep **controllers thin**:
  - Controllers should orchestrate, not contain complex business logic.
  - Move domain rules and workflows into services or dedicated classes.

- Prefer **services with interfaces** when:
  - They encapsulate important domain logic.
  - They may need swapping or mocking in tests.

- Use **Form Requests** for validation:
  - Avoid inline `$request->validate()` in controllers for non-trivial cases.
  - Centralize validation and authorization in Form Request classes.

- Keep **models focused**:
  - Business rules live in services; models expose relationships and simple
    helpers.

- Follow **single responsibility**:
  - One class should have a clear, narrow purpose.
  - Avoid “God” classes or controllers.

---

## Routes and HTTP layer

### Web routes

- Define HTTP routes in `routes/web.php`.
- Prefer:
  - `Route::resource` for CRUD resources.
  - Grouping by middleware, namespace, or prefix where appropriate.
- Name routes consistently:
  - Use Laravel’s resource names or explicit `->name()` for clarity.
- For Inertia pages:
  - Return Inertia responses in controllers, not in the route file.

### API routes (if applicable)

- Place API endpoints in `routes/api.php` (or consistent project location).
- Use proper HTTP verbs and status codes.
- Apply auth/middleware suited to API consumption.

---

## Controllers

- Place controllers under `app/Http/Controllers`.
- Keep them **small and readable**:
  - Inject services via constructor where needed.
  - Delegate heavy logic to services, actions, or domain classes.
- Patterns to follow:
  - For simple reads/writes: use Eloquent directly with validation guarded by
    Form Requests.
  - For complex flows: call a service (e.g. `ProcessAnalysisRequestService`).
- Avoid:
  - Large methods.
  - Deeply nested `if/else` trees.
  - Mixing concerns (validation, business logic, formatting, side effects)
    inside controller methods.

---

## Validation and Form Requests

- Always use **Form Request classes** for non-trivial validation:
  - Store them in `app/Http/Requests`.
  - Use meaningful names (e.g. `StoreDiscountCouponRequest`,
    `UpdateDiscountCouponRequest`).
- In Form Requests:
  - Implement `rules()` to fully describe expected input.
  - Implement `messages()` for custom messages when helpful.
  - Implement `authorize()` to restrict access, or explicitly return `true`
    when handled elsewhere.
- Controllers should type-hint Form Requests:
  - Example: `public function store(StoreDiscountCouponRequest $request)`.

---

## Models, migrations, factories, and seeders

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
  - Add relationships with clear method names (`user`, `discountCoupons`,
    etc.).
  - Use `$casts` for booleans, dates, enums, JSON, etc.
- Keep heavy business rules in services, not in the model.

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

## Services, jobs, events, and queues

### Services

- Store services under `app/Services` (or a similar organized namespace).
- Each service should:
  - Encapsulate a clear unit of behavior (e.g. processing analysis requests,
    computing metrics).
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

### Events and listeners

- Use events/listeners to decouple side effects:
  - Example: dispatch event when a report is generated; listeners send emails,
    update stats, etc.
- Place events under `app/Events` and listeners under `app/Listeners`.

---

## Error handling and responses

- Use Laravel’s exception handling:
  - Throw domain-specific exceptions where needed.
  - Customize rendering behavior in `app/Exceptions/Handler.php` when required.
- For HTTP responses:
  - Use appropriate status codes (200, 201, 204, 400, 404, 422, 500, etc.).
  - For JSON APIs, return structured JSON with clear error messages.
  - For Inertia pages, rely on redirects and flash messages where appropriate.

---

## Testing (backend)

All backend changes should come with or update appropriate tests.

### Types of tests

- **Unit tests**:
  - For small, isolated classes (services, helpers, pure functions).
- **Feature tests**:
  - For HTTP endpoints, Form Requests, database interactions, and integrated
    flows.
- **Browser tests (Pest Browser)**:
  - For end-to-end flows where the backend and frontend meet; often managed
    under the frontend/testing rules but may require backend setup.
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
- Ensure the project’s coverage requirements are respected (see
  `AGENTS.md` and test commands).

---

## Commands, environment, and tooling

- Run everything via **Laravel Sail**:
  - Do not use local PHP/Composer directly.
- Typical commands (see `.cursor/rules/starting-environment.mdc` for full
  reference):
  - Start environment, install deps, generate key as needed.
  - Run tests:
    - `./vendor/bin/sail artisan test --parallel --coverage --min=90`
    - `./vendor/bin/sail artisan test --type-coverage --min=90 --parallel`
  - Run linter:
    - `./vendor/bin/sail exec laravel.test vendor/bin/pint --parallel`

Ensure tests and Pint pass before considering backend work done.

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
- [ ] Pint passes with no style errors.

