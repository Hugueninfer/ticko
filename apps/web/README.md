# apps/web — Ticko web app

**Status:** skeleton (phase 1). Nothing to run yet.

Planned: single-page app for customers, agents and admins.

## How it will talk to the API

- Authentication with **Sanctum SPA cookies** (no tokens in browser storage):
  1. `GET /sanctum/csrf-cookie`
  2. `POST /api/v1/auth/login` with the `X-Tenant: <slug>` header
  3. later requests send the session cookie and `X-XSRF-TOKEN`; the session carries the tenant.
- The SPA origin must be listed in `SANCTUM_STATEFUL_DOMAINS` (e.g. `localhost:5173`).
- Types come from `@ticko/api-client`.
