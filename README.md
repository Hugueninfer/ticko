# Ticko

[![CI](https://github.com/Hugueninfer/ticko/actions/workflows/ci.yml/badge.svg)](https://github.com/Hugueninfer/ticko/actions/workflows/ci.yml)
![Coverage](https://raw.githubusercontent.com/Hugueninfer/ticko/badges/coverage.svg)

AI-assisted, multi-tenant helpdesk. This repository is a monorepo; **phase 1** delivers the
API, its infrastructure and its quality gates. Web, mobile and the AI classifier are
skeletons for now.

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
| http://localhost:8080/docs | Swagger UI for the API (OpenAPI 3) |
| http://localhost:8080/docs/openapi.json | The OpenAPI document |
| http://localhost:8080/api/v1 | The API |
| http://localhost:8080/horizon | Queue dashboard (admins only) |

Port 8080 taken? Set `WEB_PORT` (and `APP_URL`) in `.env`.

Create a demo tenant (`acme`) with one user per role. Passwords are random and printed once:

```bash
make demo
```

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
| `make ci` | Everything CI runs |
| `make down` / `make logs` | Stop / follow logs |

All PHP tooling runs inside the `dev` container (`docker compose --profile tools run --rm dev …`).

## Repository layout

```
apps/
  api/                Laravel 13 API (PHP 8.4)          ← phase 1
  web/                Web SPA                            (skeleton)
  mobile/             Mobile app                         (skeleton)
services/
  classifier/         AI ticket classifier (pgvector)    (skeleton)
packages/
  api-client/         TypeScript types generated from the OpenAPI document
infra/
  docker/php/         Multi-stage Dockerfile (dev, prod, web)
  docker/caddy/       Caddyfile
  postgres/init/      Creates the non-superuser application role
compose.yaml          Local stack
Makefile              One-command workflows
```

The JavaScript side is a pnpm workspace (`pnpm-workspace.yaml`); run `pnpm install` and
`pnpm --filter @ticko/api-client generate` to produce API types.

## Architecture

```
            127.0.0.1:8080
                  │
          ┌───────▼───────┐   edge network
          │ web (Caddy)   │   static files, security headers
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
| POST / DELETE | `/auth/tokens`, `/auth/tokens/current` | Mobile bearer tokens |
| GET / PUT | `/auth/me`, `/auth/password` | Current user |
| GET / POST | `/tickets` | List (filters `status`, `priority`, `category_id`, `assignee_id`, `requester_id`, `unassigned`, `q`, `sort`, pagination) / open |
| GET | `/tickets/{id}` | Show |
| PATCH | `/tickets/{id}/status`, `/tickets/{id}/assignee` | Change status / assign |
| GET / POST | `/tickets/{id}/messages` | Conversation (internal notes for staff) |
| GET / POST | `/tickets/{id}/attachments` | Images |
| GET | `/attachments/{id}`, `/attachments/{id}/content` | Metadata + signed URL / signed download |
| CRUD | `/categories` | Categories |
| GET / POST / PATCH | `/team`, `/team/{id}` | Members |

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
- **CI** (GitHub Actions): lint, Larastan, tests + coverage, `composer audit`, gitleaks,
  Docker image builds.

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
- **HTTP:** security headers at the edge; docs page under a nonce-based CSP with SRI.
- **Containers:** non-root, read-only filesystems, all capabilities dropped,
  `no-new-privileges`; Postgres and Redis unreachable from the host; Redis password-protected.
- **Supply chain:** `composer audit`, gitleaks, Actions pinned by SHA, least-privilege tokens.

## License

Proprietary — all rights reserved.
