# apps/web — Ticko web app

**Status:** skeleton (phase 1). Nothing to run yet.

Planned: single-page app for customers, agents and admins.

## How it will talk to the API

- Authentication with **Sanctum SPA cookies** (no tokens in browser storage):
  1. `GET /sanctum/csrf-cookie`
  2. `POST /api/v1/auth/login` with the `X-Tenant: <slug>` header
  3. later requests send the session cookie and `X-XSRF-TOKEN`; the session carries the tenant.
- **Same origin, no CORS.** The browser only talks to the API's origin
  (`http://localhost:8080` by default). In development the Vite dev server will
  proxy `/api` and `/sanctum` to that origin instead of calling it cross-origin,
  so the API never sends `Access-Control-Allow-Credentials`. Keep
  `SANCTUM_STATEFUL_DOMAINS` limited to that origin; do not add `localhost:5173`
  or publish a CORS config with credentials.
- Types come from `@ticko/api-client`.
