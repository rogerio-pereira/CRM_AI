# FDR-018: Commercial service catalog

**Feature:** 18  
**Status:** Approved  
**Reference:** [18 Commercial service catalog](../../05%20-%20Feature%20List.md#f18-commercial-service-catalog), [ADR-020](../../ADRs/ADR-020-commercial-service-catalog-boundary.md), [ADR-018](../../ADRs/ADR-018-proposal-artifact-rendering-and-delivery.md), [Design System §17A](../../04%20-%20Design%20System.md)

---

## How it works

1. Persist a global **commercial services** catalog in the database: name, description, default unit price, active flag, and **category slug** aligned with files under `docs/services/` (general service categories used by qualification).
2. `docs/services/` remains the qualification category briefs (for example “website design and development”). Catalog rows are **detailed sellable items** under those categories (domain, DNS, cloud setup, development hours, etc.).
3. Provide authenticated CRUD UI (sidebar **Services**) and seed initial rows; **no automatic sync** job from markdown files.
4. Proposal line items (FDR-013) reference catalog services and may override unit price on the proposal only.

---

## How to test

- Create/update/deactivate a service; list filters and validation for price/name.
- Category slug accepts known `docs/services/` identifiers.
- Changing a catalog default price does not rewrite existing proposal line overrides.
- Browser smoke for Services navigation and CRUD; feature tests for model/validation.

---

## Acceptance criteria

- [ ] Catalog table + model/factory/seed with category slug, name, description, default price, active.
- [ ] Livewire/Flux CRUD reachable from sidebar with stable `data-test` selectors.
- [ ] Documented relationship to `docs/services/` categories (no auto-sync).
- [ ] Prices are for proposals only (not billing/invoicing).
- [ ] Feature and browser tests cover happy path and validation.

---

## Deployment notes

- Seed baseline catalog for local/demo environments.
- No new external integrations.
