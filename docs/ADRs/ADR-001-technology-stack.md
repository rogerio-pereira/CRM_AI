# ADR-001: Technology stack

## Status

Accepted

## Context

The PRD and HLD define an internal AI-assisted CRM that must remain lightweight, maintainable, and deployable on Laravel Cloud. The HLD specifies Laravel 13 for the backend, Laravel Livewire for the frontend UI, PostgreSQL for persistence, Redis for queues, Laravel Horizon for queue monitoring, Docker/Laravel Sail for local development, and Laravel Cloud for production.

The existing codebase uses the Laravel Livewire starter kit (Fortify, Flux, Livewire 4).

## Decision

Adopt the following stack for MVP:

| Layer | Choice |
| ----- | ------ |
| Backend | Laravel 13 |
| Frontend | Laravel Livewire (+ Flux UI components per starter kit) |
| Database | PostgreSQL |
| Queue | Redis with Laravel queue workers |
| Queue monitoring | Laravel Horizon |
| AI integration | Laravel AI SDK (see [ADR-002](ADR-002-ai-provider-abstraction.md)) |
| Local dev | Docker via Laravel Sail |
| Production | Laravel Cloud |

References:

- [HLD §2 Technology Stack](../02%20HLD.md#2-technology-stack)
- [Design System §1 Product Context](../04%20-%20Design%20System.md#1-product-context)

## Consequences

- **Positive:**
  - Single-language full-stack (PHP/Livewire) reduces context switching for internal CRM work.
  - Aligns with Laravel Cloud deployment model and Horizon for operational queue visibility.
  - Matches the official starter kit already in the repository.
- **Negative:**
  - Livewire differs from SPA frameworks; complex highly interactive UIs may need careful component design.
  - Laravel AI SDK must be added when implementing AI features (not yet in `composer.json`).
- **Neutral:**
  - Tailwind CSS is used for design tokens per Design System; no separate CSS framework.
