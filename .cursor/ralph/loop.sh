#!/usr/bin/env bash
#
# Ralph Loop — run Planning or Building prompts in a CLI loop.
#
# Usage (from repository root):
#   .cursor/ralph/loop.sh [cursor] [plan] [N]
#
# Examples:
#   .cursor/ralph/loop.sh              # Claude CLI, Building, until Ctrl+C
#   .cursor/ralph/loop.sh 20           # Claude CLI, Building, max 20 iterations
#   .cursor/ralph/loop.sh plan         # Claude CLI, Planning
#   .cursor/ralph/loop.sh plan 5       # Claude CLI, Planning, max 5 iterations
#   .cursor/ralph/loop.sh cursor         # Cursor CLI, Building
#   .cursor/ralph/loop.sh cursor 20    # Cursor CLI, Building, max 20 iterations
#   .cursor/ralph/loop.sh cursor plan  # Cursor CLI, Planning
#
# Requirements: git repository; claude or cursor CLI on PATH.
# Prompts: .cursor/ralph/PROMPT_build.md | PROMPT_plan.md
# Plan state (gitignored): docs/FDRs/ImplementationPlans/

set -euo pipefail

RALPH_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

usage() {
    cat <<'EOF'
Ralph Loop — automated Planning or Building iterations

Usage:
  .cursor/ralph/loop.sh [cursor] [plan] [N]

Arguments (any order):
  cursor    Use Cursor CLI (default: Claude CLI)
  plan      Planning mode (default: Building)
  N         Max iterations (default: unlimited until Ctrl+C)

Examples:
  .cursor/ralph/loop.sh
  .cursor/ralph/loop.sh 20
  .cursor/ralph/loop.sh plan
  .cursor/ralph/loop.sh plan 5
  .cursor/ralph/loop.sh cursor
  .cursor/ralph/loop.sh cursor 20
  .cursor/ralph/loop.sh cursor plan

See .cursor/ralph/README.md for workflow details.
EOF
}

if ! REPO_ROOT="$(git -C "$RALPH_DIR" rev-parse --show-toplevel 2>/dev/null)"; then
    echo "error: .cursor/ralph must live inside a git repository." >&2
    exit 1
fi

AGENT="claude"
MODE="build"
MAX_ITER=0

for arg in "$@"; do
    case "$arg" in
        -h|--help)
            usage
            exit 0
            ;;
        cursor)
            AGENT="cursor"
            ;;
        plan)
            MODE="plan"
            ;;
        [0-9]*)
            MAX_ITER="$arg"
            ;;
        *)
            echo "error: unknown argument: $arg" >&2
            usage >&2
            exit 1
            ;;
    esac
done

if [[ "$MODE" == "plan" ]]; then
    PROMPT_FILE="$RALPH_DIR/PROMPT_plan.md"
    MODE_LABEL="Planning"
else
    PROMPT_FILE="$RALPH_DIR/PROMPT_build.md"
    MODE_LABEL="Building"
fi

if [[ ! -f "$PROMPT_FILE" ]]; then
    echo "error: prompt file not found: $PROMPT_FILE" >&2
    exit 1
fi

if [[ "$AGENT" == "cursor" ]]; then
    if ! command -v cursor >/dev/null 2>&1; then
        echo "error: cursor CLI not found. Install: https://cursor.com/docs/cli/installation" >&2
        exit 1
    fi
else
    if ! command -v claude >/dev/null 2>&1; then
        echo "error: claude CLI not found. Install: https://claude.ai/download" >&2
        exit 1
    fi
fi

read_prompt() {
    cat "$PROMPT_FILE"
}

run_iteration() {
    local iteration="$1"
    local prompt

    prompt="$(read_prompt)"

    echo ""
    echo "=== Ralph ${MODE_LABEL} (${AGENT}) — iteration ${iteration} ==="
    echo "Repository: ${REPO_ROOT}"
    echo "Prompt:     ${PROMPT_FILE}"
    echo ""

    cd "$REPO_ROOT"

    if [[ "$AGENT" == "cursor" ]]; then
        # Non-interactive agent run for trusted local environments only.
        cursor agent "$prompt" -p --force --trust --approve-mcps --workspace "$REPO_ROOT"
    else
        claude -p "$prompt" --dangerously-skip-permissions
    fi
}

echo "Ralph Loop"
echo "  Agent:  ${AGENT}"
echo "  Mode:   ${MODE_LABEL}"
if [[ "$MAX_ITER" -gt 0 ]]; then
    echo "  Limit:  ${MAX_ITER} iteration(s)"
else
    echo "  Limit:  none (Ctrl+C to stop)"
fi
echo ""

iteration=0

while true; do
    if [[ "$MAX_ITER" -gt 0 ]] && [[ "$iteration" -ge "$MAX_ITER" ]]; then
        echo "Reached max iterations (${MAX_ITER})."
        exit 0
    fi

    iteration=$((iteration + 1))
    run_iteration "$iteration" || true
done
