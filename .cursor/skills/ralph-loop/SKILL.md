---
name: ralph-loop
description: Run the Ralph Loop (Planning or Building) using docs/FDRs/ToDo and IMPLEMENTATION_PLAN; one task per Building run; move completed FDRs to docs/FDRs/Done; then push the feature branch and open a PR to main when a feature is complete.
---

# Ralph Loop

Use this skill when the user wants to run **Planning** or **Building** for the Ralph workflow, or when they refer to "Ralph", "Ralph Loop", or "one task from the plan". For **hands-off continuous execution** (no monitoring between tasks), use the **Claude CLI** with the project's `loop.sh` script instead of interactive chat; see `.cursor/ralph/README.md`.

## What the Ralph Loop is here

- **Planning:** Analyze the gap between FDRs in `docs/FDRs/ToDo/` and the codebase; output is an updated `docs/FDRs/IMPLEMENTATION_PLAN.md` only (no code changes, no commits).
- **Building:** Pick the single most important task from `docs/FDRs/IMPLEMENTATION_PLAN.md`, implement it, run tests and Pint, update the plan, move the FDR to `docs/FDRs/Done/` if the whole feature is done, then commit per `.cursor/rules/commits-small-incremental.mdc`. One task per agent run.
- **Branching:** Use **one branch per feature** (reuse across tasks of that feature), not one branch per task.
- **PR flow:** After a feature is marked complete, push the feature branch and open a pull request to `main` after local checks pass (GitHub MCP when available).

## How to run

1. **Planning**
   - Open or paste the contents of `.cursor/ralph/PROMPT_plan.md` into the agent chat, or say "Run Ralph Planning".
   - Follow the prompt: read docs, ADRs, FDRs, and code; then write/update only `docs/FDRs/IMPLEMENTATION_PLAN.md`.
   - Do not implement code or commit.

2. **Building**
   - Open or paste the contents of `.cursor/ralph/PROMPT_build.md` into the agent chat, or say "Run Ralph Building" or "Do one Ralph task".
   - Follow the prompt: choose one task from the plan, implement it, test, lint, update plan, move FDR to Done if applicable, commit (implementation commits first, then a separate `docs(...)` commit for plan/FDR moves when applicable).
   - Before creating/switching the feature branch, sync `main` (`checkout`, `fetch`, `pull`).
   - Keep all tasks of the same feature on the same branch; create a branch only when the feature starts.
   - Do not do a second task in the same run.

3. **Finalize feature (when complete)**
   - Ensure the branch is pushed to the remote.
   - Open a PR targeting `main` using the **GitHub MCP server** (MCP tools), not the `gh` CLI. Use a concise summary and test plan.
   - After push + PR, checkout `main` and delete the local feature branch.
   - Confirm lint and test checks passed before merge.

## Key paths

| What | Where |
|------|--------|
| Planning prompt | `.cursor/ralph/PROMPT_plan.md` |
| Building prompt | `.cursor/ralph/PROMPT_build.md` |
| Plan (state) | `docs/FDRs/IMPLEMENTATION_PLAN.md` |
| Feature specs | `docs/FDRs/ToDo/*.md` |
| Done features | `docs/FDRs/Done/*.md` |
| ADRs | `docs/ADRs/*.md` |
| Project context | `.cursor/AGENTS.md`, `.cursor/rules/starting-environment.mdc` |

## Rules to respect

- One task per Building run.
- One branch per feature; do not create a new branch for each task.
- Do not assume something is not implemented — search the code first.
- Use Sail for all commands (tests, Pint); see `.cursor/rules/starting-environment.mdc`.
- When an FDR is fully done, move its file from `docs/FDRs/ToDo/` to `docs/FDRs/Done/`. Do not use `docs/FDRs/Closed/` as part of delivery workflow.
- When a feature is marked complete, push the branch and open a PR (MCP or documented manual steps).
