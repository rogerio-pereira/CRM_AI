# Ralph Loop in GoViral

This project uses the **Ralph Loop** in two ways:

1. **In the IDE (manual)** — you start each iteration in the agent chat; the agent runs one phase at a time and commits. For the next task, start a new conversation if needed.
2. **With Claude CLI (automated)** — a bash script restarts Claude after each task so you do not need to monitor or open new chats.

---

## Option A: Automated loop (Claude CLI) — hands-off

Use this if you want Ralph-style behavior: **leave it running** without interacting.

### Prerequisites

- **Cursor:** [Cursor CLI](https://cursor.com/docs/cli/installation) installed (`curl https://cursor.com/install -fsS | bash`). Run `./loop.sh cursor`.
- **Claude:** [Claude CLI](https://claude.ai/download) installed and authenticated. Run `./loop.sh` with no argument.
- From the repository root: `./loop.sh` or `./loop.sh cursor`.

### Usage

**With Cursor Agent CLI** (if you already use Cursor):

```bash
# Building: continuous loop (one task at a time until Ctrl+C)
./loop.sh cursor

# Building: at most 20 iterations
./loop.sh cursor 20

# Planning: generate/update the plan
./loop.sh cursor plan
```

**With Claude CLI:**

```bash
# Building: continuous loop
./loop.sh

# Building: at most 20 iterations
./loop.sh 20

# Planning: generate/update the plan (usually 1–2 iterations)
./loop.sh plan

# Planning: at most 5 iterations
./loop.sh plan 5
```

The script uses the same prompts under `.cursor/ralph/` (`PROMPT_build.md` and `PROMPT_plan.md`) and the same plan file `docs/FDRs/IMPLEMENTATION_PLAN.md`. Each time the agent finishes a task (commit and exit), the script starts another run. Stop with **Ctrl+C** when you want.

- **Cursor:** install the [Cursor CLI](https://cursor.com/docs/cli/installation) and use `./loop.sh cursor`. The agent runs with `-p --force --trust --approve-mcps` so it does not wait for confirmations.
- **Claude:** use `./loop.sh` with no argument. Claude CLI may run with `--dangerously-skip-permissions`. Use only in a trusted environment.

---

## Option B: In the IDE (one iteration per chat)

There is **no** built-in automatic loop in the IDE. The "loop" is: run the agent again when you want the next task.

---

## Files in this folder

| File | Purpose |
|------|---------|
| `PROMPT_plan.md` | **Planning** instructions. The agent reads docs/FDRs/ADRs/code and updates the plan only. |
| `PROMPT_build.md` | **Building** instructions. The agent picks **one** task, implements it, tests, updates the plan, and commits. |
| Plan (outside this folder) | `docs/FDRs/IMPLEMENTATION_PLAN.md` — prioritized task list. **Planning** creates/updates it; **Building** consumes it and marks work done. |
| Loop script (repo root) | `loop.sh` — runs Building (or Planning) in a loop without interaction. |

---

## General flow

1. **First time or stale plan**
   - Open an agent chat in your IDE.
   - Paste `.cursor/ralph/PROMPT_plan.md` or ask for **Ralph Planning**.
   - The agent reviews `docs/FDRs/ToDo/`, `docs/ADRs/`, and code, then fills/updates `docs/FDRs/IMPLEMENTATION_PLAN.md`. No implementation and no commits.

2. **Implement tasks (Building)**
   - Open an agent chat.
   - Paste `.cursor/ralph/PROMPT_build.md` or ask for **Ralph Building** / **one Ralph task**.
   - The agent reads the plan, picks **one** task, stays on the **feature branch** (creates it from updated `main` when the feature starts; **reuse** the same branch for all tasks of that feature—**not** a new branch per task), implements, runs tests and Pint, updates the plan, and commits per `.cursor/rules/commits-small-incremental.mdc`.
   - If a whole FDR is done (all acceptance criteria), the agent **moves** the FDR from `docs/FDRs/ToDo/` to `docs/FDRs/Done/`.
   - For the **next** task, start another chat and repeat.

---

## Important rules

- **One task per Building run.** Do not ask for multiple tasks in the same chat.
- **Do not assume something is missing.** Search the codebase before concluding.
- **Commands:** tests and lint via Sail (see `.cursor/rules/starting-environment.mdc`).
- **FDRs:** specs live in `docs/FDRs/ToDo/`. When a feature is complete, move the FDR to `docs/FDRs/Done/`. See `.cursor/rules/fdr-todo-done.mdc` for **`Closed/`** (archive only).

---

## Where specs and decisions live

- **Features (specs):** `docs/FDRs/ToDo/*.md` (one FDR per feature).
- **Architecture:** `docs/ADRs/*.md`.
- **Product and design:** `docs/01 - Product Requirement Document.md`, `docs/02 - High Level Design.md`, `docs/04 - Features.md`, `docs/03 - Branding Manual.md`.

The agent treats these as sources of truth; there is no separate top-level `specs/` folder.

---

## Rules and skills (this repository)

- **Rules:** `.cursor/rules/ralph-loop.mdc` — Ralph workflow and plan usage. `.cursor/rules/fdr-todo-done.mdc` — FDR ToDo/Done (always applied).
- **Skill:** `.cursor/skills/ralph-loop/SKILL.md` — when to use Planning vs Building and how to run the loop.

---

## Quick reference

| Goal | Action |
|------|--------|
| Generate or refresh the plan | Chat with `PROMPT_plan.md` or "Ralph Planning". |
| Do one task and commit | Chat with `PROMPT_build.md` or "Ralph Building" / "one Ralph task". |
| Next task | **Claude CLI:** `./loop.sh` starts the next run. **IDE:** new chat with Building. |
| See what is left | Open `docs/FDRs/IMPLEMENTATION_PLAN.md`. |

With Claude CLI you do not manually restart after each task; in the IDE, trigger the agent with the right prompt when you want the next Planning or Building iteration.
