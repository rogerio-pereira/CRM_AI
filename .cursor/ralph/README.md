# Ralph Loop

This workflow supports **spec-driven, one-task-at-a-time** implementation. FDRs live under `docs/FDRs/`; **ephemeral** plan state lives in `docs/FDRs/ImplementationPlans/` (gitignored). Wave and product status: `docs/05 - Feature List.md`.

Two ways to run it:

1. **In the IDE (manual)** — you start each iteration in the agent chat; the agent runs one phase at a time and commits. For the next task, start a new conversation if needed.
2. **With Claude CLI or Cursor CLI (automated)** — run `.cursor/ralph/loop.sh` from the repository root; it restarts the agent after each task so you do not need to monitor or open new chats.

---

## Option A: Automated loop (CLI) — hands-off

Use this if you want Ralph-style behavior: **leave it running** without interacting.

### Prerequisites

- **Cursor:** [Cursor CLI](https://cursor.com/docs/cli/installation) installed (`curl https://cursor.com/install -fsS | bash`).
- **Claude:** [Claude CLI](https://claude.ai/download) installed and authenticated.
- Run all commands from the **repository root** (where `.cursor/ralph/` lives).

### Usage

**With Cursor Agent CLI:**

```bash
# Building: continuous loop (one task at a time until Ctrl+C)
.cursor/ralph/loop.sh cursor

# Building: at most 20 iterations
.cursor/ralph/loop.sh cursor 20

# Planning: generate/update the plan
.cursor/ralph/loop.sh cursor plan
```

**With Claude CLI:**

```bash
# Building: continuous loop
.cursor/ralph/loop.sh

# Building: at most 20 iterations
.cursor/ralph/loop.sh 20

# Planning: generate/update the plan (usually 1–2 iterations)
.cursor/ralph/loop.sh plan

# Planning: at most 5 iterations
.cursor/ralph/loop.sh plan 5
```

Arguments `cursor`, `plan`, and `N` (max iterations) may appear in any order.

The script reads prompts from this folder (`PROMPT_build.md`, `PROMPT_plan.md`). Plan state is local under `docs/FDRs/ImplementationPlans/` (not committed). Each time the agent finishes a task (commit and exit), the script starts another run. Stop with **Ctrl+C** when you want.

- **Cursor:** uses `cursor agent` with `-p --force --trust --approve-mcps` for non-interactive runs—**trusted environments only**.
- **Claude:** uses `claude -p` with `--dangerously-skip-permissions`—**trusted environments only**.

Help: `.cursor/ralph/loop.sh --help`

---

## Option B: In the IDE (one iteration per chat)

There is **no** built-in automatic loop in the IDE. The "loop" is: run the agent again when you want the next task.

---

## Files in this folder

| File | Purpose |
|------|---------|
| `PROMPT_plan.md` | **Planning** instructions. The agent reads PRD, HLD, Branding, Design System, Feature List, FDRs, ADRs, and code; updates the plan only (no invented features). |
| `PROMPT_build.md` | **Building** instructions. The agent picks **one** task, implements it, tests, updates the plan, and commits. |
| `loop.sh` | Runs Planning or Building in a CLI loop (Claude or Cursor). |
| Plan state (gitignored) | `docs/FDRs/ImplementationPlans/*.md` — local task progress only. **Planning** creates/updates; **Building** consumes; **delete** when feature/wave is done. |

---

## General flow

1. **First time or stale plan**
   - Open an agent chat in your IDE.
   - Paste `.cursor/ralph/PROMPT_plan.md` or ask for **Ralph Planning**.
   - The agent reads `docs/01 PRD.md`, `docs/02 HLD.md`, `docs/03 - Branding Manual.md`, `docs/04 - Design System.md`, and `docs/05 - Feature List.md`, then reviews in-scope FDRs, ADRs, and code, and fills/updates local plans in `docs/FDRs/ImplementationPlans/`. Planning must not invent features beyond those sources. No implementation and no commits.

2. **Implement tasks (Building)**
   - Open an agent chat.
   - Paste `.cursor/ralph/PROMPT_build.md` or ask for **Ralph Building** / **one Ralph task**.
   - The agent reads the local feature plan, picks **one** task, stays on the **feature branch** (creates it from updated `main` when the feature starts; **reuse** the same branch for all tasks of that feature—**not** a new branch per task), implements, runs tests and linter, updates the local plan, and commits per `.cursor/rules/commits-small-incremental.mdc` (never plan files).
   - If a whole FDR is done, **move** it from `docs/FDRs/ToDo/` to `docs/FDRs/Done/` and **delete** that feature's plan file(s).
   - When a whole **wave** is done, delete all wave plan files and set the wave to `Done` in `docs/05 - Feature List.md`.
   - For the **next** task, start another chat and repeat—or run `.cursor/ralph/loop.sh` for automated iterations.

---

## Important rules

- **One task per Building run.** Do not ask for multiple tasks in the same chat.
- **Do not assume something is missing.** Search the codebase before concluding.
- **Commands:** use the project's documented runner (often Sail) — see `.cursor/rules/starting-environment.mdc`.
- **FDRs:** specs live in `docs/FDRs/ToDo/`. When a feature is complete, move the FDR to `docs/FDRs/Done/`. See `.cursor/rules/fdr-todo-done.mdc` for **`Closed/`** (archive only).

---

## Where specs and decisions live

- **Features (specs):** `docs/FDRs/ToDo/*.md` (one FDR per feature).
- **Architecture:** `docs/ADRs/*.md`.
- **Product and design:** under `docs/` (PRD, HLD, Feature List, Branding, Design System — see `.cursor/AGENTS.md`).

The agent treats these as sources of truth; there is no separate top-level `specs/` folder required.

---

## Rules and skills (in `.cursor/`)

- **Rules:** `.cursor/rules/ralph-loop.mdc` — Ralph workflow and plan usage. `.cursor/rules/fdr-todo-done.mdc` — FDR ToDo/Done (always applied).
- **Skill:** `.cursor/skills/ralph-loop/SKILL.md` — when to use Planning vs Building and how to run the loop.

---

## Quick reference

| Goal | Action |
|------|--------|
| Generate or refresh the plan | Chat with `PROMPT_plan.md` or "Ralph Planning"; or `.cursor/ralph/loop.sh plan` |
| Do one task and commit | Chat with `PROMPT_build.md` or "Ralph Building" / "one Ralph task" |
| Next task (automated) | `.cursor/ralph/loop.sh` or `.cursor/ralph/loop.sh cursor` |
| See what is left | Open the active plan in `docs/FDRs/ImplementationPlans/` (or run Planning) |

With a CLI loop you do not manually restart after each task; in the IDE, trigger the agent with the right prompt when you want the next Planning or Building iteration.
