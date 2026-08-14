# Ralph — Building Mode

You are running **one iteration** of the Ralph Loop in **BUILDING** mode. Do **exactly one** task from the implementation plan, then validate, update local plan state, and commit according to `.cursor/rules/commits-small-incremental.mdc`.

## Paths and sources of truth

| What | Where | Committed? |
| ---- | ----- | ---------- |
| Product feature list (waves, dependencies) | `docs/05 - Feature List.md` | Yes — **source of truth** |
| Feature specs (FDRs) | `docs/FDRs/ToDo/` → `docs/FDRs/Done/` when complete | Yes |
| ADRs | `docs/ADRs/` | Yes |
| **Implementation plans (ephemeral state)** | `docs/FDRs/ImplementationPlans/` | **No** — gitignored |

Implementation plans are **not** long-lived documentation. They exist only during the current implementation cycle as **local state/progress files** for the agent (task checklists, branch names, discoveries). **Never commit** them.

Wave index example: `docs/FDRs/ImplementationPlans/IMPLEMENTATION_PLAN_wave_1.md`  
Feature plan example: `docs/FDRs/ImplementationPlans/IMPLEMENTATION_PLAN_wave_1_FDR-001-platform-foundation.md`

## Phase 0 — Orient

1. Study the project context from `.cursor/AGENTS.md` (stack, standards, commands).
2. Study `docs/05 - Feature List.md` (wave scope and status), `docs/FDRs/ToDo/` (active FDR), and `docs/ADRs/`. Use `.cursor/rules/starting-environment.mdc` for environment and test commands.
3. Open the **feature-specific** plan in `docs/FDRs/ImplementationPlans/`. If missing, run Planning first for the current wave/feature. Use the wave index only to confirm build order. Choose the **single most important** open task in the feature plan.
4. Before implementing: search the codebase to confirm the current state. Do **not** assume something is not implemented — verify first.
5. Before implementing: sync the default branch first (e.g. `git checkout main && git fetch && git pull`).
6. Before implementing: use **one branch per feature** (not per task). If the feature branch is registered in that feature's plan file, switch to it. If not, create it from updated `main` (e.g. `feat/<feature-name>`), switch to it, and register it in the **local** plan before coding.

## Phase 1 — Implement

1. Implement **only** the chosen task. Do not do extra tasks in this run.
2. Follow `.cursor/rules/style-guide.md` strictly (English code, PSR, **no ternary operators** in PHP, explicit sequential data flow, one chained method per line with the documented continuation indent). A task is **not done** until every changed file complies.
3. Follow `.cursor/rules/kiss.mdc`: simplest solution that meets the requirement; no overengineering. Do not skip style guide, security, validation, or tests.
4. In fluent method chains, keep one method call per line and keep indentation consistent.
5. For assigned fluent chains, use a deeper continuation indent for `->` lines; for standalone chains, use one continuation indent level.
6. In tests, chained expectations are acceptable when each method call stays on its own line.
7. Use Sail for all commands (see `.cursor/rules/starting-environment.mdc`).
8. When the change is done, run the relevant tests (e.g. `./vendor/bin/sail artisan test --parallel` or targeted tests). If tests fail, fix the code until they pass.
9. Run the linter: `./vendor/bin/sail exec laravel.test vendor/bin/pint --parallel`.

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

### Phase 1.4 — Style guide and KISS review (mandatory)

Read `.cursor/rules/style-guide.md` and `.cursor/rules/kiss.mdc`, then re-read **every** changed file.

A feature, fix, refactor, or any code change is **not done** until all changed files comply. Tests and Pint passing is not a substitute.

Check at least:

- Explicit named intermediate values (no nested meaningful method calls)
- One logical operation per statement
- One chained method per line, with continuation indent per the guide
- No PHP ternary operators
- Arrays and multiline arguments indented per the guide
- Simplest solution that meets the requirement; no extra layers, helpers, or abstractions unless needed now

If any file fails, fix it before Phase 2.

**IMPORTANT:** Do not move forward before Phase 1.2, 1.3, and 1.4 are passing.

## Phase 2 — Update state, docs, and repo

### 2.1 Local plan state (not committed)

1. Update the **feature plan** file: mark the task done (e.g. `- [x]`), keep feature→branch mapping updated, add follow-up tasks if needed.
2. **When a feature is fully complete** (FDR acceptance criteria met, FDR moved to `Done`, PR merged or ready):
   - **Delete** that feature's plan file(s) under `docs/FDRs/ImplementationPlans/` (e.g. `IMPLEMENTATION_PLAN_wave_1_FDR-001-platform-foundation.md`).
3. **When an entire wave is fully complete** (every feature in the wave done, all FDRs in `Done`, all PRs merged):
   - **Delete** all remaining plan files for that wave (wave index + any feature plans).
   - **Update** `docs/05 - Feature List.md`: in **Development waves**, set that wave's **Status** to `Done` (and date if the table includes it).
4. Do **not** archive implementation plans elsewhere. Progress history lives in git commits, PRs, and `docs/FDRs/Done/`.

### 2.2 Committed documentation

1. If an entire FDR is now complete, **move** its file from `docs/FDRs/ToDo/` to `docs/FDRs/Done/`. Do **not** use `docs/FDRs/Closed/` for delivered work.
2. Operational documentation (run, build, test, deploy) belongs in **`README.md`** at the project root only. Update README when the task changes runtime operations.
3. Wave completion status is recorded only in **`docs/05 - Feature List.md`**, not in plan files.

### 2.3 Commits (order matters)

Follow `.cursor/rules/commits-small-incremental.mdc`:

1. **Implementation:** Commit application code and tests in **small, conventional** commits (`feat`, `fix`, `test`, `chore`, etc.). **Never** stage or commit anything under `docs/FDRs/ImplementationPlans/`.
2. **Docs:** After implementation commits, a **separate** `docs(...)` commit may include: FDR move (ToDo → Done), `docs/05 - Feature List.md` wave status when the wave completes, and `README.md` if touched. Example: `docs(ralph): move FDR-001 to Done and mark wave 1 complete`.
3. Keep working on the feature branch from Phase 0. Do not create a new branch per task. Do not commit directly on `main`.
4. When a **feature** is complete in this run: push the feature branch, **create the PR via GitHub MCP** targeting `main`. If MCP is unavailable, inform human and STOP. If PR is created, then checkout `main` and delete the local feature branch.
5. **Wave cleanup** (delete local plans + update Feature List) happens in the Building run where the **last** feature of the wave is finished—typically after that feature's PR is merged, or in the same run if the user confirms the wave is closed.

## Guardrails

- One task per run. Do not start the next task in the same conversation.
- Do not assume "not implemented" — always search/read the code first.
- Resolve test failures before committing. No placeholder or stub-only implementations.
- Keep the **local** feature plan current between runs so the next agent knows what is left.
- Do not commit, copy, or reference implementation plans in PR descriptions as permanent docs.
- Do not consider the run finished until: tests and Pint pass; **every changed file complies with `.cursor/rules/style-guide.md` and `.cursor/rules/kiss.mdc`**; local plan updated; FDR moved when applicable; commits exclude `ImplementationPlans/`; feature PR handled when feature-complete; wave plans deleted and Feature List updated when wave-complete.

## Output

When done, state: (1) which task you completed, (2) that tests and Pint passed, (3) that all changed files were re-read against `.cursor/rules/style-guide.md` and `.cursor/rules/kiss.mdc` and comply, (4) whether you moved an FDR to Done, (5) commit hash(es) or messages, (6) if feature-complete: PR URL and branch cleanup, (7) if wave-complete: confirmation that wave plan files were deleted and `docs/05 - Feature List.md` was updated.
