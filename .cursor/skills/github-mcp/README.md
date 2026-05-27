# GitHub MCP Setup (Generic)

This README explains how to install and configure the GitHub MCP server in a generic way, reusable across repositories.

## Prerequisites

- Docker installed and running
- An MCP-capable editor or agent host (any tool that loads `.cursor/mcp.json` or an equivalent project-local MCP definition)
- A GitHub Personal Access Token (PAT)
- Access to the target repository (personal or organization)

## 1) Create a GitHub PAT

Recommended: Fine-grained token.

1. Go to GitHub → Settings → Developer settings → Personal access tokens → Fine-grained tokens.
2. Generate a new token.
3. Set:
   - Resource owner: account or organization that owns the target repo(s)
   - Repository access: selected repositories (recommended) or broader scope as needed
   - Expiration: 30–90 days
4. Minimum permissions for PR workflows:
   - Contents: Read and write
   - Pull requests: Read and write
   - Commit statuses: Read
   - Actions: Read (optional, useful for checks visibility)

If your organization requires SSO/policy approval, authorize the token.

## 2) Configure MCP server (project-local recommended)

Create or update `.cursor/mcp.json`:

```json
{
  "mcpServers": {
    "github-official": {
      "command": "docker",
      "args": [
        "run",
        "-i",
        "--rm",
        "--env-file",
        ".cursor/.env.mcp",
        "ghcr.io/github/github-mcp-server"
      ]
    }
  }
}
```

## 3) Store token in local env file

Create `.cursor/.env.mcp`:

```bash
GITHUB_PERSONAL_ACCESS_TOKEN=your_token_here
```

Restrict permissions:

```bash
chmod 600 .cursor/.env.mcp
```

Ignore it in git:

```gitignore
/.cursor/.env.mcp
```

## 4) Reload and validate

1. **Reload the IDE window** or restart the MCP connection so the server reloads configuration.
2. Verify `github-official` appears connected in MCP settings.
3. Validate using your client’s GitHub tools (e.g. identity check, list pull requests for `<owner>/<repo>`).

## 5) Troubleshooting

- `401 Bad credentials`:
  - token is invalid/expired
  - wrong token value in env file
- `403 Forbidden`:
  - missing PAT scope
  - repository not included in token access
  - org SSO/policy not approved
- Server not listed/connected:
  - invalid `.cursor/mcp.json`
  - Docker not running
  - IDE/MCP reload pending

## 6) Safe operational flow

1. Push feature branch.
2. Open PR to base branch (usually `main`).
3. Wait for required checks.
4. Merge only when checks pass.
