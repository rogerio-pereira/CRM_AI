# Ralph — Planning Mode

You are running **one iteration** of the Ralph Loop in **PLANNING** mode. Your job is to analyze the gap between specifications and code, then create or update the implementation plan only. Do **not** implement any code.

## Phase 0 — Orient

1. Study the project documentation (paths may vary—see `.cursor/AGENTS.md`), typically:
   - Product: `docs/01 PRD.md` or `docs/01 - PRD.md`
   - Architecture: `docs/02 HLD.md`
   - Feature list: `docs/05 - Feature List.md`
   - Branding: `docs/03 - Branding Manual.md`
   - Design System: `docs/04 - Design System.md`
2. Study all ADRs in `docs/ADRs/` to understand architectural decisions.
3. Study all FDRs (feature specs) in `docs/FDRs/ToDo/` — each file is one feature to implement.
4. Study `docs/FDRs/IMPLEMENTATION_PLAN.md` (if it exists) to see the current plan.
5. Study the application source code (`app/`, `resources/`, `routes/`, relevant config) to understand what is already implemented.

## Phase 1 — Gap analysis and plan

1. Compare each FDR in `docs/FDRs/ToDo/` against the current codebase. Do **not** assume something is missing — search and read the code to confirm.
2. For each FDR, identify concrete tasks (examples: "Add Lead model and migration", "Create Livewire index for opportunities", "Configure Horizon queue dashboard"). Consider dependencies between FDRs (see the project's feature list).
3. Create or update `docs/FDRs/IMPLEMENTATION_PLAN.md` with a **prioritized bullet list** of tasks. Order by dependency and value (foundation first, then features in sequence).
4. Mark or remove items that are already done, if the plan already exists.
5. Keep the plan concise: one line per task, sorted by priority. Remove completed items when appropriate to avoid clutter.

## Rules

- **Plan only.** Do not implement, do not edit application code, do not commit.
- Do not assume functionality is missing — confirm with code search first.
- Respect ADRs for stack choices (queues, UI stack, database, etc.).
- If an FDR is fully implemented (all acceptance criteria met), note in the plan that the FDR can be moved to `docs/FDRs/Done/` as a separate task.
- `docs/FDRs/Closed/` is **not** part of the agent workflow; only humans archive discarded specs there. Agents use **ToDo → Done** only.
- Single source of truth: FDRs in `docs/FDRs/ToDo/` are the specs; ADRs in `docs/ADRs/` are the decisions.
- If human ask to plan a wave that contains multiple features, each feature should have it's own implementation plan. DO NOT merge everything in one implementation phase. This is important because when running in build mode each feature should have it's own branch
- If human ask to plan a wave, include `_wave_x` in file name, eg: `docs/FDRs/IMPLEMENTATION_PLAN_wave_3.md`

## Output

Update only `docs/FDRs/IMPLEMENTATION_PLAN.md`. When done, state briefly what you added or changed in the plan.
