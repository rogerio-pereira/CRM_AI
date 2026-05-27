---
name: feature-creation
description: Guides creation of a new feature by reading docs, interviewing the user, creating a branch, ADR, FDR (ToDo), running Ralph Planning, committing incrementally, opening a PR, and cleaning up. Does not implement the feature. Use when the user wants to add a new feature, create feature spec, or "create a new feature" workflow.
---

# New Feature Creation (Spec-Only)

Use this skill when the user wants to **define and document a new feature** without implementing it. The agent reads project docs, interviews the user, creates an ADR, an FDR in ToDo, runs Ralph Planning, commits by context, opens a PR, then returns to `main` and asks whether to create another feature. **No application code is written.**

## Rules (mandatory)

- **Do not assume.** If anything is unclear (scope, naming, dependencies, acceptance criteria), ask the user before writing the ADR or FDR.
- **Do not implement the feature.** This workflow produces only documentation and plan updates. Implementation is done later (e.g. Ralph Building or separate sessions).
- **One feature per run.** One branch, one ADR, one FDR, one planning run, one PR. To create another feature, run the skill again after the user confirms.

---

## Phase 1 — Orient

1. Read project documentation to understand the product and architecture:
   - `docs/01 - Product Requirement Document.md`
   - `docs/02 - High Level Design.md`
   - `docs/04 - Features.md`
   - `docs/03 - Branding Manual.md`
2. Read all ADRs in `docs/ADRs/` (naming, status, context/decision/consequences pattern).
3. Read existing FDRs in `docs/FDRs/ToDo/` and a few in `docs/FDRs/Done/` to learn the FDR format (How it works, How to test, Acceptance criteria, Deployment notes).
4. Skim `docs/FDRs/IMPLEMENTATION_PLAN.md` and `.cursor/ralph/PROMPT_plan.md` to understand the Ralph Planning flow.

Summarize briefly what the project is and what the docs say (stack, features, ADR/FDR conventions). Do not proceed to the interview until orientation is done.

---

## Phase 2 — Interview (new feature)

Conduct a short interview with the user. **Ask; do not assume.**

Suggested questions (adapt or skip if the user already gave the information):

- **What should the feature do?** (goal, user-facing behavior)
- **Why is it needed?** (problem or opportunity)
- **Which existing features or systems does it depend on or affect?** (e.g. form, payment, queue, email)
- **Are there non-obvious constraints?** (e.g. performance, privacy, third-party limits)
- **How do you want to name it?** (short name for ADR/FDR and branch; e.g. "persist report", "admin export")
- **Any specific acceptance criteria or “how to test” scenarios** you want in the FDR?

If the user’s answers are vague, ask one or two follow-ups. Do not invent scope or criteria.

---

## Phase 3 — Branch and docs

1. **Sync and create branch**
   - `git fetch origin`
   - `git checkout main`
   - `git pull origin main`
   - Create a new branch from `main` with a clear name (e.g. `docs/feat-<short-name>-spec`). Prefer a name that reflects “feature spec” if the branch only contains docs.
   - Checkout the new branch.

2. **Create ADR** (if the feature implies an architectural decision)
   - File: `docs/ADRs/ADR_XXX_<short_snake_case_title>.md`
   - Use the next sequential number (e.g. list existing ADR_*.md and pick next).
   - Structure: Status, Context, Decision, Consequences, References (see existing ADRs).
   - If the feature does not require a new architectural decision, ask the user: “No new ADR, or do you want one for consistency?” and only create if they confirm.

3. **Create FDR (ToDo)**
   - File: `docs/FDRs/ToDo/FDR_XXX_<short_snake_case_title>.md`
   - Pick the next free FDR number by scanning filenames in `docs/FDRs/ToDo/`, `docs/FDRs/Done/`, and `docs/FDRs/Closed/` to avoid collisions. **`Closed/`** holds **human-archived discarded** specs only; agents do **not** move work there and do **not** treat it as part of the ToDo→Done delivery path.
   - Structure: **Feature** (number), **Reference** (e.g. docs/04 - Features.md, ADR-XXX), **How it works**, **How to test**, **Acceptance criteria** (checkboxes), **Deployment notes** (if any).
   - Add the feature to `docs/04 - Features.md` in the right place (dependency order) with **Objective**, **Scope**, **Dependencies**, and **Related to** (if needed). Update the **Feature dependency summary** table at the bottom if applicable.

If anything is ambiguous, ask the user before writing. Do not assume technical details (e.g. table names, endpoints) unless they are already fixed in docs or ADRs.

---

## Phase 4 — Ralph Planning

1. Run **Ralph in Planning mode** as defined in `.cursor/ralph/PROMPT_plan.md`:
   - Phase 0: Orient (docs, ADRs, FDRs ToDo, IMPLEMENTATION_PLAN, code).
   - Phase 1: Gap analysis; update `docs/FDRs/IMPLEMENTATION_PLAN.md` with a prioritized task list for the new FDR (and any existing ToDo FDRs).
2. Do **not** implement code or run Building. Output is only an updated `IMPLEMENTATION_PLAN.md`.

---

## Phase 5 — Commits (incremental by context)

Make **separate commits** by logical context, for example:

1. **First commit:** New ADR (if created). Message e.g. `docs(adr): add ADR-XXX <short title>`
2. **Second commit:** New FDR + update `docs/04 - Features.md`. Message e.g. `docs(fdr): add FDR-XXX <short title> and feature entry in Features.md`
3. **Third commit:** Updated `docs/FDRs/IMPLEMENTATION_PLAN.md` after Ralph Planning. Message e.g. `docs(ralph): update implementation plan for FDR-XXX`

If there is no ADR, only two commits (FDR + plan). Keep commit messages concise and in English.

---

## Phase 6 — PR and cleanup

1. **Push** the feature branch to the remote.
2. **Open a Pull Request** to `main` using the **GitHub MCP server** (create_pull_request), not the `gh` CLI.
   - Title: short, e.g. `docs: add feature spec for <short name> (ADR-XXX, FDR-XXX)`
   - Body: brief summary of what was added (ADR if any, FDR, plan update); note that this is spec-only and no implementation is included. Add “How to test: N/A (docs only); CI may run on push.”
3. **Checkout `main`** and **pull** latest.
4. **Delete the local feature branch** (e.g. `git branch -d <branch-name>`).
5. Tell the user the PR link and that they can merge after review. Remind that implementation will be done later (e.g. Ralph Building).

---

## Phase 7 — Loop

Ask the user: **“Do you want to create another feature? (yes/no)”**

- If **yes**, start again from Phase 2 (Interview) on a new feature (create a new branch from updated `main` after they merge or you pull).
- If **no**, end the workflow and confirm that the new feature is documented and ready for implementation when they choose.

---

## Key paths

| What              | Where |
|-------------------|--------|
| Product/features  | `docs/01 - Product Requirement Document.md`, `docs/04 - Features.md` |
| Architecture      | `docs/02 - High Level Design.md`, `docs/ADRs/` |
| Feature specs     | `docs/FDRs/ToDo/*.md`, `docs/FDRs/Done/*.md` (`Closed/` = archived only) |
| Plan              | `docs/FDRs/IMPLEMENTATION_PLAN.md` |
| Ralph Planning    | `.cursor/ralph/PROMPT_plan.md` |
| PR                | GitHub MCP `create_pull_request` (target `main`) |

---

## Summary

1. Read all docs → understand project.  
2. Interview user → clarify goal, scope, constraints, naming.  
3. Create branch → ADR (if needed) → FDR (ToDo) → update Features.md.  
4. Run Ralph Planning → update IMPLEMENTATION_PLAN only.  
5. Commit incrementally (ADR, FDR+Features, plan).  
6. Push → open PR → checkout main → delete local branch.  
7. Ask if they want to create another feature.

**Do not assume; do not implement.**
