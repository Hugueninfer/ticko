# Ticko

[![CI](https://github.com/Hugueninfer/ticko/actions/workflows/ci.yml/badge.svg)](https://github.com/Hugueninfer/ticko/actions/workflows/ci.yml)
![Coverage](https://raw.githubusercontent.com/Hugueninfer/ticko/badges/coverage.svg)

AI-assisted, multi-tenant helpdesk. This repository is a monorepo: **phase 1** delivered the
API, its infrastructure and quality gates; **phase 2** adds the web app for agents and
admins and the typed API client. Mobile and the AI classifier are skeletons for now.

> 🇧🇷 Leia em português: [README.pt-BR.md](README.pt-BR.md)

## Quick start

Requirements: Docker with Compose v2 and `make`. Nothing else (PHP, Composer and Node are
not needed on the host).

```bash
make up
```

This creates `.env` with freshly generated secrets (only the first time), builds the images,
runs the migrations and starts everything. Then:

| URL | What |
| --- | --- |
| http://localhost:8080 | The web app (agents and admins) |
| http://localhost:8080/docs | Swagger UI for the API (OpenAPI 3) |
| http://localhost:8080/docs/openapi.json | The OpenAPI document |
| http://localhost:8080/api/v1 | The API |
| http://localhost:8080/horizon | Queue dashboard (admins only) |

Port 8080 taken? Set `WEB_PORT` (and `APP_URL`) in `.env`.

Create a demo tenant (`acme`, "Loja Aurora") with one user per role, categories and tickets.
Passwords are random and printed once:

```bash
make demo
```

With `TICKO_DEMO_LOGIN=true` (the default in `.env.example`; never honoured in production) the
sign-in page offers one-click **Agent** and **Admin** demo buttons.

Try it:

```bash
curl -s -X POST http://localhost:8080/api/v1/auth/tokens \
  -H 'X-Tenant: acme' -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"email":"agent@acme.test","password":"<printed password>","device_name":"curl"}'

curl -s http://localhost:8080/api/v1/tickets -H "Authorization: Bearer <token>" -H 'Accept: application/json'
```

Real tenants: `docker compose exec api php artisan ticko:tenant:create <slug> "<Name>" --admin-email=<email>`.

### Development commands

| Command | Does |
| --- | --- |
| `make test` | Run the Pest suite (against the `ticko_testing` database) |
| `make coverage` | Tests with coverage; fails under 80% |
| `make lint` / `make fix` | Pint check / fix |
| `make stan` | Larastan at level `max` |
| `make audit` | `composer audit` |
| `make gitleaks` | Secret scan of the git history |
| `make docs` | Regenerate `apps/api/resources/openapi/openapi.json` with Scribe |
| `make ci` | Everything CI runs for the API |
| `make web-dev` | Web app with hot reload on http://localhost:5173 (Node 22 + corepack) |
| `make web-test` | ESLint, Prettier, `tsc`, Vitest with coverage (web + API client) |
| `make e2e` | Playwright against the running stack (`make up && make demo` first) |
| `make down` / `make logs` | Stop / follow logs |

All PHP tooling runs inside the `dev` container (`docker compose --profile tools run --rm dev …`).
The web tooling needs Node 22 on the host (pnpm comes from corepack).

## Repository layout

```
apps/
  api/                Laravel 13 API (PHP 8.4)          ← phase 1
  web/                Web app: React + Vite              ← phase 2
  mobile/             Mobile app                         (skeleton)
services/
  classifier/         AI ticket classifier (pgvector)    (skeleton)
packages/
  api-client/         Typed client generated from the OpenAPI document (web + mobile)
infra/
  docker/php/         Multi-stage Dockerfile (dev, prod, web)
  docker/caddy/       Caddyfile
  postgres/init/      Creates the non-superuser application role
compose.yaml          Local stack
Makefile              One-command workflows
```

The JavaScript side is a pnpm workspace (`pnpm-workspace.yaml`). `pnpm install` also generates
the API types from `apps/api/resources/openapi/openapi.json` (`pnpm api-client:generate`).

### Web app (`apps/web`)

React 19 + TypeScript (strict) + Vite, TanStack Query, React Router, Tailwind CSS with
shadcn-style components, React Hook Form + Zod, react-i18next (**pt-BR by default**, English).
Light and dark themes follow the design tokens: teal for every human action, violet only for
what comes from the AI. Screens: sign-in, ticket queue (Mine / Unassigned / All / Overdue,
filters, search, pagination), ticket detail (customer, conversation with internal notes and
images, AI panel placeholder), team, categories and SLA, and the admin dashboard.

The browser only ever talks to **one origin**: in Docker, Caddy serves the built app at `/` and
proxies `/api`, `/sanctum`, `/docs`, `/horizon` and `/up` to Laravel; with `make web-dev`, Vite
proxies the same paths to the stack. Sign-in uses Sanctum's session cookie and CSRF token; no
token is ever stored in the browser and there is no CORS configuration with credentials.

## Architecture

```
            127.0.0.1:8080
                  │
          ┌───────▼───────┐   edge network
          │ web (Caddy)   │   web app (SPA), security headers, CSP
          └───────┬───────┘
                  │ FastCGI
          ┌───────▼───────┐         ┌──────────────┐
          │ api (PHP-FPM) │         │ queue        │  Horizon workers
          └───┬───────┬───┘         └───┬──────┬───┘
   backend    │       │                 │      │      (internal network,
   network ┌──▼───┐ ┌─▼────┐            │      │       no published ports)
           │ pg17 │ │redis │◄───────────┘      │
           │+pgvec│ └──────┘                   │
           └──▲───┘◄─────────────────────────────┘
              └── migrate (one-shot, runs before api/queue start)
```

### API layers (`apps/api`)

| Layer | Location | Responsibility |
| --- | --- | --- |
| Routes | `routes/api.php` | Versioned under `/api/v1`, grouped by middleware (auth, throttling, signatures) |
| Controllers | `app/Http/Controllers/Api/V1` | Thin: authorize, call one action, return a resource |
| Form requests | `app/Http/Requests` | Authorize (before validating) and validate input |
| Actions | `app/Actions` | One use case each (`CreateTicket`, `AssignTicket`, …); no HTTP knowledge |
| Services | `app/Services` | Reusable domain services (`ImageSanitizer`) |
| Policies | `app/Policies` | Per-role, per-tenant authorization rules |
| Resources | `app/Http/Resources` | JSON output; never expose internal columns |
| Models | `app/Models` | ULID keys, explicit `$fillable`, `BelongsToTenant` |
| Errors | `app/Exceptions/ProblemDetailsRenderer.php` | RFC 7807 `application/problem+json` |

Every PHP file declares `strict_types`.

### Multi-tenancy

Every tenant-owned table has a `tenant_id`. Isolation is enforced twice:

1. **Application:** the `BelongsToTenant` trait adds a global scope (and matches nothing
   when no tenant is set) and fills `tenant_id` on create from the current context.
2. **Database:** Postgres Row-Level Security policies, `FORCE`d so they apply to the table
   owner as well, only expose rows where `tenant_id = current_setting('app.tenant_id')`.
   The application connects with a role that is neither superuser nor `BYPASSRLS`.
   Composite foreign keys (`tenant_id`, `id`) stop rows from referencing another tenant.

The `ResolveTenant` middleware runs before authentication and sets the context (and the
Postgres setting) from, in order: the tenant bound to the bearer token, the tenant stored in
the session at login, the tenant inside a valid signed URL, or the `X-Tenant` header (slug,
used to log in). A stronger source always wins, so a client cannot switch tenants with the
header.

### Roles

| | customer | agent | admin |
| --- | --- | --- | --- |
| Tickets | own only; open, close/reopen, reply, upload | all; any status, assign, internal notes | as agent |
| Categories | list | list | manage |
| Team | — | list | list, create, change role, deactivate |
| Horizon | — | — | yes (allow-listed outside `local`) |

### Main endpoints (`/api/v1`)

| Method | Path | |
| --- | --- | --- |
| POST | `/auth/login`, `/auth/logout` | SPA session (Sanctum cookie) |
| GET / POST | `/auth/demo` | Demo sign-in (only with `TICKO_DEMO_LOGIN`, never in production) |
| POST / DELETE | `/auth/tokens`, `/auth/tokens/current` | Mobile bearer tokens |
| GET / PUT | `/auth/me`, `/auth/password` | Current user |
| GET / POST | `/tickets` | List (filters `status`, `priority`, `category_id`, `assignee_id`, `requester_id`, `unassigned`, `overdue`, `q`, `sort`, pagination) / open |
| GET | `/tickets/{id}` | Show |
| PATCH | `/tickets/{id}/status`, `/tickets/{id}/assignee` | Change status / assign |
| GET / POST | `/tickets/{id}/messages` | Conversation (internal notes for staff) |
| GET / POST | `/tickets/{id}/attachments` | Images |
| GET | `/attachments/{id}`, `/attachments/{id}/content` | Metadata + signed URL / signed download |
| CRUD | `/categories` | Categories, with per-priority SLA overrides |
| GET / POST / PATCH | `/team`, `/team/{id}` | Members (search `q`, open ticket counts) |
| GET | `/metrics` | Admin dashboard: volume, median first response, SLA compliance |

**SLA:** each ticket gets first-response and resolution deadlines when it is opened, from its
category's targets or the per-priority defaults in `config/ticko.php` (calendar time; business
hours are not modelled yet). The first public staff reply is recorded; `?overdue=1` lists
unresolved tickets with a missed deadline.

The complete, always-current reference is the Swagger UI at `/docs`.

## Configuration

`make env` writes `.env` from [`.env.example`](.env.example) and generates
`APP_KEY` and the database and Redis passwords. `.env.example` contains **no secrets**.
Ticko-specific settings live in `apps/api/config/ticko.php` (`TICKO_*` variables: token
lifetime, rate limits, upload size, signed-URL lifetime, docs toggle, Horizon allow-list).

## Testing and quality

- **Pest** unit, feature and architecture suites; coverage gate of **80%** (`make coverage`).
- **Tenant isolation:** every endpoint that targets a single resource is called by an admin of
  another tenant and must answer `404` without changing data; a meta-test fails if a new
  route is missing from that list. RLS is tested with raw SQL.
- **Authorization:** role matrices for tickets, messages, attachments, categories and team.
- **Architecture:** strict types, thin final controllers, readonly actions, explicit
  `$fillable`, and a test that fails if an API controller action never calls `authorize()`
  (public endpoints must be rate limited or signed).
- **Static analysis:** Larastan at level `max`. **Style:** Pint.
- **Web:** ESLint (strict type-checked, React hooks, jsx-a11y), Prettier, `tsc` strict,
  Vitest + Testing Library against the real route tree (coverage gate of 80%), and Playwright
  end-to-end tests against the Docker stack (sign-in, queue, reply and internal note, admin
  pages, categories, team), which also fail on any CSP violation.
- **CI** (GitHub Actions): lint, Larastan, tests + coverage, `composer audit`, gitleaks,
  Docker image builds, web lint/types/tests/build/`pnpm audit`, and the end-to-end suite.

## Security

See [SECURITY.md](SECURITY.md) to report a vulnerability. Highlights:

- **Isolation:** global scope + forced RLS + composite foreign keys + non-privileged DB role.
- **Auth:** Sanctum cookies for the SPA (CSRF-protected), expiring tenant-bound tokens
  (prefixed `ticko_` so leaked tokens are caught by secret scanners) for mobile; tokens are
  revoked on deactivation or role change, and the other tokens on password change.
- **Passwords:** Argon2id; minimum 12 characters; rejected if found in known breaches
  (HIBP k-anonymity API).
- **No user enumeration:** login answers with one generic error, whether the tenant, the
  e-mail or the password is wrong, and always costs one Argon2id verification.
- **Rate limits:** login per account and per IP, API per user, uploads per user; `429`
  responses carry `Retry-After`.
- **Authorization before validation:** form requests check the policy first, so validation
  errors never leak information (e.g. "e-mail already taken") to unauthorized callers.
  Foreign resources answer `404`, not `403`.
- **Uploads:** JPEG/PNG/WebP detected from content, max 5 MB and 40 MP, decoded and
  re-encoded (drops EXIF/GPS and appended payloads), random file names on a private disk,
  served only through 5-minute signed URLs with `nosniff` and a sandboxing CSP.
- **Errors:** RFC 7807 problem details; no stack traces or internal messages unless
  `APP_DEBUG=true`.
- **HTTP:** security headers at the edge; the web app is served under a strict CSP (only
  same-origin scripts, styles, fonts, images and requests; no inline code, no framing); the
  docs page uses a nonce-based CSP with SRI.
- **Web app:** session cookie only (httpOnly; nothing in `localStorage`/`sessionStorage` but
  the theme, language and last company identifier), user content always rendered as text
  (lint forbids `dangerouslySetInnerHTML` and HTML string writes), no secrets in `VITE_*`
  variables, role checks in the UI for navigation only — the API authorizes every request.
- **Containers:** non-root, read-only filesystems, all capabilities dropped,
  `no-new-privileges`; Postgres and Redis unreachable from the host; Redis password-protected.
- **Supply chain:** `composer audit`, gitleaks, Actions pinned by SHA, least-privilege tokens.

## License

Proprietary — all rights reserved.
