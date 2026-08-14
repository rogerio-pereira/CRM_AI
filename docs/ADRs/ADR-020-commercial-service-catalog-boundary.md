# ADR-020: Commercial service catalog boundary

## Status

Accepted (2026-08-14)

## Partially supersedes

The “service source of truth” wording in section 6 and Prompt assets of [ADR-017](ADR-017-wave-4-ai-qualification-schema.md).

ADR-017 remains authoritative for opportunity-scoped qualification, statuses, schema version 1, and qualification behavior. This ADR replaces only the interpretation that `docs/services/` is the complete catalog for every commercial workflow.

## Context

Qualification needs broad service categories such as “website design and development.” Commercial proposals need detailed sellable line items such as domain registration, DNS setup, cloud setup, and development hours, including default prices.

Those concerns have different data and change requirements:

- `docs/services/*.md` provides versioned category briefs and positioning context for prospecting/qualification.
- The CRM database provides editable commercial line items and prices for proposal construction.

## Decision

1. `docs/services/*.md` remains the source of truth for **general qualification categories**.
2. The database commercial service catalog is the source of truth for **sellable proposal line items**.
3. Commercial services store name, description, default price, active state, and a category slug aligned with a `docs/services/` category where applicable.
4. There is no automatic synchronization from Markdown files to the database in MVP.
5. Qualification agents continue reading `docs/services/`.
6. Proposal recommendation reads the database catalog and may use the category relationship for context.
7. Per-proposal price overrides never mutate catalog defaults.
8. Commercial proposal pricing is not billing, invoicing, or financial management.

## Consequences

- **Positive:**
  - Qualification remains stable and version-controlled.
  - Sales can maintain detailed priced services without editing prompt documentation.
- **Negative:**
  - Categories and sellable items require intentional manual alignment.
- **Neutral:**
  - Seed/admin workflows maintain catalog rows; no sync job is required.

## References

- [ADR-017 Qualification flow and schema](ADR-017-wave-4-ai-qualification-schema.md)
- [ADR-018 Proposal domain and artifacts](ADR-018-proposal-artifact-rendering-and-delivery.md)
- [FDR-018 Commercial service catalog](../FDRs/Done/FDR-018-commercial-service-catalog.md)
