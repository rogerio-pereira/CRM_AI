# Ralph — Building Mode

You are running **one iteration** of the Ralph Loop in **BUILDING** mode. Do **exactly one** task from the implementation plan, then validate, update the plan, and commit according to `.cursor/rules/commits-small-incremental.mdc`.

## Phase 0 — Orient

1. Study the project context from `.cursor/AGENTS.md` (stack, standards, commands).
2. Study `docs/FDRs/ToDo/` (feature specs) and `docs/ADRs/` (decisions). Use the rule in `.cursor/rules/starting-environment.mdc` for Sail and test commands.
3. Read `docs/FDRs/IMPLEMENTATION_PLAN.md` and choose the **single most important** task that is not yet done.
4. Before implementing: search the codebase to confirm the current state. Do **not** assume something is not implemented — verify first.
5. Before implementing: sync `main` first (`git checkout main && git fetch && git pull`).
6. Before implementing: use **one branch per feature** (not per task). If the feature branch already exists in `docs/FDRs/IMPLEMENTATION_PLAN.md`, switch to it. If not, create it from updated `main` (for example `feat/<feature-name>`), switch to it, and register it in the plan before coding.

## Phase 1 — Implement

1. Implement **only** the chosen task. Do not do extra tasks in this run.
2. Follow project standards: English code, PSR, **no ternary operators** in PHP, thin controllers, Form Requests for validation, services for business logic, and an equivalent factory for every Eloquent model.
3. In fluent method chains, keep one method call per line and keep indentation consistent.
4. For assigned fluent chains, use a deeper continuation indent for `->` lines; for standalone chains, use one continuation indent level.
5. In tests, chained expectations are acceptable when each method call stays on its own line.
6. Use Sail for all commands (see `.cursor/rules/starting-environment.mdc`).
7. When the change is done, run the relevant tests (e.g. `./vendor/bin/sail artisan test --parallel` or targeted tests). If tests fail, fix the code until they pass.
8. Run the linter: `./vendor/bin/sail exec laravel.test vendor/bin/pint --parallel`.

### Phase 1.2 — Testing

Run all tests (the full suite includes Browser tests per `phpunit.xml`). Use **Pest Browser** only for browser automation; **do not** add or rely on Laravel Dusk.

**Before running tests:** build the frontend or have the dev server running — either `./vendor/bin/sail npm run build` once, or `./vendor/bin/sail npm run dev` in a background terminal.

```
./vendor/bin/sail npm run build
./vendor/bin/sail artisan test --parallel --coverage --min=90
./vendor/bin/sail artisan test --type-coverage --min=90 --parallel
```

### Phase 1.3 — Linting

Run Pint:

```
./vendor/bin/sail exec laravel.test vendor/bin/pint --parallel
```

**IMPORTANT:** Do not move forward before Phase 1.2 and 1.3 are passing.

## Phase 2 — Update plan and repo

1. Update `docs/FDRs/IMPLEMENTATION_PLAN.md`: mark the task you did as done (e.g. strikethrough or "- [x]"), keep the feature→branch mapping updated, and add any discoveries or follow-up tasks.
2. If an entire FDR is now complete (all acceptance criteria met), **move** that FDR file from `docs/FDRs/ToDo/` to `docs/FDRs/Done/` (e.g. move `FDR_001_configure_vue_vuetify_branding.md` to `docs/FDRs/Done/`). Do **not** move specs to `docs/FDRs/Closed/`; that folder is for human-archived discarded work only.
3. If you learned something operational (how to run/build/test), update `.cursor/AGENTS.md` briefly.

### Phase 2.1 — Commits (order matters)

Follow `.cursor/rules/commits-small-incremental.mdc`:

1. **Implementation:** Commit all application code and test changes in one or more **small, conventional** commits (`feat`, `fix`, `test`, `chore`, etc.). Do **not** include `docs/FDRs/IMPLEMENTATION_PLAN.md`, FDR moves, or `.cursor/AGENTS.md` updates in these commits when you can avoid it.
2. **Docs / plan:** After implementation commits, create a **separate** `docs(...)` commit for updates to `docs/FDRs/IMPLEMENTATION_PLAN.md`, any FDR file move (ToDo → Done), and `.cursor/AGENTS.md` if touched. Example: `docs(ralph): mark task done and move FDR to Done`.

3. Keep working on the feature branch from Phase 0. Do not create a new branch for another task of the same feature. Do not commit directly on `main`.

4. If the feature is marked complete in this run: push the feature branch, then **create the PR using the GitHub MCP server** (use MCP tools to create the pull request targeting `main`). Do **not** use the `gh` CLI. If GitHub MCP is not available, push the branch and document in the output that the user should open the PR manually (with the branch name and repo URL). After the PR is created (or documented), checkout `main` and delete the local feature branch.

## Guardrails

- One task per run. Do not start the next task in the same conversation.
- Do not assume "not implemented" — always search/read the code first.
- Resolve test failures before committing. No placeholder or stub-only implementations.
- Keep `docs/FDRs/IMPLEMENTATION_PLAN.md` up to date so the next run knows what is left.
- If you find spec inconsistencies or bugs unrelated to this task, add them to the plan or document in the plan; do not expand scope beyond the one chosen task.
- Do not consider the Building run finished until Phase 2 is fully executed: FDR moved to Done (when applicable), commits done (implementation then docs), feature branch pushed, PR created (or clearly documented when MCP is unavailable), `main` checked out, and the local feature branch deleted.

## Output

When done, state: (1) which task you completed, (2) that tests and Pint passed, (3) whether you moved an FDR to Done, (4) the commit hash(es) or messages, and (5) if feature-complete, the PR URL (from GitHub MCP) and confirmation that local branch cleanup was done. If you could not create the PR via MCP, give the user the branch name and link to open the PR manually.
