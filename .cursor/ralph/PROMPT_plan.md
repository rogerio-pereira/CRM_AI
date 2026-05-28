# Ralph — Planning Mode

You are running **one iteration** of the Ralph Loop in **PLANNING** mode. Your job is to analyze the gap between specifications and code, then create or update **local** implementation plan files only. Do **not** implement any code or commit.

## Paths and sources of truth

| What | Where | Committed? |
| ---- | ----- | ---------- |
| Product feature list (waves, dependencies) | `docs/05 - Feature List.md` | Yes — **source of truth** |
| Feature specs (FDRs) | `docs/FDRs/ToDo/` | Yes |
| ADRs | `docs/ADRs/` | Yes |
| **Implementation plans (ephemeral state)** | `docs/FDRs/ImplementationPlans/` | **No** — gitignored |

Implementation plans are **temporary state files** for the current build cycle (task lists, branches, gaps). They are **not** long-lived documentation. Building deletes them when a feature or wave is complete. **Never commit** plan files.

## Phase 0 — Orient

1. Study the project documentation (see `.cursor/AGENTS.md`), typically:
   - `docs/05 - Feature List.md` — waves and dependencies
   - PRD, HLD, Branding, Design System under `docs/`
2. Study all ADRs in `docs/ADRs/`.
3. Study FDRs in `docs/FDRs/ToDo/` for the scope you are planning (wave or single feature).
4. Study existing **local** plans in `docs/FDRs/ImplementationPlans/` if present.
5. Study the codebase (`app/`, `resources/`, `routes/`, config) to see what is already implemented.

## Phase 1 — Gap analysis and plan

1. Compare each in-scope FDR against the codebase. Do **not** assume something is missing — search and read the code first.
2. Identify concrete, prioritized tasks per FDR. Respect dependencies in `docs/05 - Feature List.md`.
3. Write or update plan files under `docs/FDRs/ImplementationPlans/` only.
4. Mark done items in existing local plans when re-planning.
5. Keep each plan concise: one line per open task, ordered by priority.

## Rules

- **Plan only.** No application code changes. No git commits (plans are gitignored).
- `docs/FDRs/Closed/` is not part of delivery workflow; agents use **ToDo → Done** only.
- FDRs and ADRs are specs/decisions; **`docs/05 - Feature List.md`** is the product roadmap (including wave completion status).
- **Wave planning:** one plan file per feature + optional wave index. Do not merge all features into one task list (Building uses one branch per feature).
- **File names** (under `docs/FDRs/ImplementationPlans/`):
  - Wave index: `IMPLEMENTATION_PLAN_wave_<N>.md`
  - Per feature: `IMPLEMENTATION_PLAN_wave_<N>_FDR-<NNN>-<slug>.md`
  - Single feature (no wave): `IMPLEMENTATION_PLAN_FDR-<NNN>-<slug>.md`
- When re-planning a wave, set that wave's **Status** to `In progress` in `docs/05 - Feature List.md` if it was `Pending` (committed doc change is optional in Planning; Building marks `Done` when the wave finishes).

## Output

Update only local files in `docs/FDRs/ImplementationPlans/`. Do not commit. State briefly which plan file(s) you created or changed.
