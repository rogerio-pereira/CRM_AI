---
name: ralph-loop
description: Run the Ralph Loop (Planning or Building) using docs/FDRs/ToDo and ephemeral plans in docs/FDRs/ImplementationPlans; one task per Building run; move completed FDRs to docs/FDRs/Done; mark waves Done in docs/05 - Feature List.md; delete local plans when done.
---

# Ralph Loop

Use this skill when the user wants to run **Planning** or **Building** for the Ralph workflow, or when they refer to "Ralph", "Ralph Loop", or "one task from the plan". For **hands-off continuous execution** (no monitoring between tasks), run `.cursor/ralph/loop.sh` (Claude or Cursor CLI) instead of interactive chat; see `.cursor/ralph/README.md`.

## What the Ralph Loop is here

- **Planning:** Read in full `docs/01 PRD.md`, `docs/02 HLD.md`, `docs/03 - Branding Manual.md`, `docs/04 - Design System.md`, and `docs/05 - Feature List.md`; then gap analysis vs in-scope FDRs, ADRs, and code. Write/update **local** plan files under `docs/FDRs/ImplementationPlans/` only. **Do not invent features** beyond committed product docs and FDRs—note gaps as blockers instead. No code changes, no commits (folder is gitignored).
- **Building:** One task from the active feature plan; implement, test, Pint; update local plan; move FDR to `Done` when complete; commit code and committed docs only. Delete plan files when feature/wave finishes; set wave `Done` in `docs/05 - Feature List.md`.
- **Branching:** One branch per feature (reuse across tasks), not one branch per task.
- **PR flow:** After a feature is complete, push and open PR to `main` (GitHub MCP).

## How to run

1. **Planning** — `.cursor/ralph/PROMPT_plan.md` or "Run Ralph Planning".
2. **Building** — `.cursor/ralph/PROMPT_build.md` or "Run Ralph Building" / "one Ralph task".
3. **Finalize feature** — PR via MCP; delete that feature's local plan file(s).
4. **Finalize wave** — all FDRs in `Done`; delete all wave plan files; set wave status `Done` in Feature List.

## Key paths

| What | Where |
|------|--------|
| Planning prompt | `.cursor/ralph/PROMPT_plan.md` |
| Building prompt | `.cursor/ralph/PROMPT_build.md` |
| Product roadmap (committed) | `docs/05 - Feature List.md` |
| Plan state (ephemeral, gitignored) | `docs/FDRs/ImplementationPlans/` |
| Feature specs | `docs/FDRs/ToDo/*.md` |
| Done features | `docs/FDRs/Done/*.md` |
| ADRs | `docs/ADRs/*.md` |

## Rules to respect

- **Planning:** mandatory product docs (PRD, HLD, Branding, Design System, Feature List) before planning; no scope invention.
- One task per Building run.
- Never commit `docs/FDRs/ImplementationPlans/`.
- `docs/05 - Feature List.md` is the source of truth for waves; implementation plans are throwaway state.
- When an FDR is fully done, move `ToDo` → `Done`. Do not use `Closed/` for delivery.
- Use Sail for tests and Pint (see `.cursor/rules/starting-environment.mdc`).
