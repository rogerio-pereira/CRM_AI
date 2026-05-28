# ADR-008: Authentication for internal users

## Status

Accepted

## Context

The platform serves **multiple internal users** with standard Laravel authentication. The HLD explicitly does **not** define ACL/RBAC systems or multi-tenancy for MVP.

## Decision

- Use **standard Laravel authentication** (Fortify is present in the starter kit: login, registration policy TBD for internal-only, password reset, optional 2FA per existing kit).
- **No multi-tenancy** in MVP—all users share one company dataset.
- **No RBAC/ACL** in MVP; authorization is binary (authenticated vs guest) unless a future ADR adds roles.
- Protect all CRM routes behind authentication middleware.

References:

- [HLD §11 Authentication](../02%20HLD.md#11-authentication)
- [Design System — internal users only](../04%20-%20Design%20System.md#1-product-context)

## Consequences

- **Positive:**
  - Fast delivery; matches internal CRM threat model.
- **Negative:**
  - Cannot segregate data by team/role without future work.
- **Neutral:**
  - Registration may be disabled in production; only invited internal accounts.
