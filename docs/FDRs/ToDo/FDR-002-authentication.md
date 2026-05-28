# FDR-002: Authentication

**Feature:** 02  
**Status:** Approved  
**Reference:** [02 Authentication](../../05%20-%20Feature%20List.md#f02-authentication), [ADR-008](../../ADRs/ADR-008-authentication-internal-users.md)

---

## How it works

1. Use **Laravel Fortify** (existing starter kit) for login, password reset, optional 2FA.
2. Protect CRM routes with `auth` middleware; guest users redirect to login.
3. Internal-only policy: disable public registration in production (env flag or Fortify config).
4. User model stores minimal profile fields for header menu.

---

## How to test

- Unauthenticated access to `/dashboard` redirects to login.
- Valid login establishes session; logout clears session.
- Password reset flow works via mail driver in test env.
- Optional: 2FA enrollment and challenge (existing kit tests).

---

## Acceptance criteria

- [ ] All CRM pages require authentication.
- [ ] Login/logout/password reset functional.
- [ ] Registration disabled or restricted in production configuration.
- [ ] No multi-tenant scoping on queries (single org dataset).
- [ ] Feature tests cover auth gates on primary routes.

---

## Deployment notes

- Configure mail for password reset in production.
- Document initial admin user seeding process.
