# ADR-002: AI provider abstraction

## Status

Accepted

## Context

The PRD requires an AI provider abstraction layer configurable for OpenAI and Gemini. The HLD mandates Laravel AI SDK as the official abstraction. MVP does not include automatic failover between providers.

## Decision

- Use **Laravel AI SDK** as the only supported integration path to AI providers.
- Support **OpenAI** and **Gemini**; active provider selected via environment/configuration.
- **No automatic provider failover** in MVP.
- Provider comparison and evaluation deferred to post-MVP evolution.

References:

- [PRD Integrations](../01%20PRD.md#integrations)
- [HLD §6 AI Architecture](../02%20HLD.md#6-ai-architecture)

## Consequences

- **Positive:**
  - Swappable providers without rewriting agent logic.
  - Clear boundary for testing (mock SDK / fake responses).
- **Negative:**
  - Outage of the configured provider blocks AI workflows until manual switch or recovery.
- **Neutral:**
  - Additional providers (future) extend configuration, not core orchestration.
