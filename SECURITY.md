# Security Policy

## Reporting a vulnerability

Please **do not open a public issue** for security problems.

Report them privately through GitHub's
[private vulnerability reporting](https://github.com/Hugueninfer/ticko/security/advisories/new)
with:

- a description of the issue and its impact;
- steps to reproduce (requests, payloads, affected endpoint or file);
- the commit or version you tested.

You will get an acknowledgement within 5 business days. Please give us a reasonable
time to fix the issue before disclosing it.

## Supported versions

Ticko is pre-1.0. Only the latest commit on `main` receives security fixes.

## Security model (summary)

The full description is in the README's [Security](README.md#security) section.

| Area | Control |
| --- | --- |
| Tenant isolation | `BelongsToTenant` global scope **and** Postgres Row-Level Security (FORCEd) keyed on `app.tenant_id`; composite foreign keys keep relations inside a tenant; the app's DB role is neither superuser nor `BYPASSRLS`. |
| Authentication | Sanctum: session cookie for the first-party SPA, expiring bearer tokens for mobile. Argon2id hashing, breached-password check, generic errors and constant-cost checks (no user enumeration), per-account and per-IP rate limits. |
| Authorization | Policies on every resource; form requests authorize before validating; an architecture test fails if an API controller action does not call `Gate::authorize()`. Horizon is admin-only (and allow-listed outside `local`). |
| Mass assignment | Explicit `$fillable` on every model; `tenant_id`, ownership and status fields are never fillable. |
| Uploads | Type detected from content, dimension and size limits, re-encoded (drops EXIF/GPS and appended payloads), random names on a private disk, served through short-lived signed URLs with `nosniff` and a sandboxing CSP. |
| Errors | RFC 7807 problem details; internal messages are never exposed outside debug mode. |
| Web app | Served on the API's own origin under a strict CSP (same-origin scripts, styles, fonts, images and requests only; no inline code; no framing). Sanctum session cookie (httpOnly) with CSRF; no tokens in `localStorage`/`sessionStorage`; no CORS with credentials. User content is rendered as text only and lint forbids `dangerouslySetInnerHTML` and HTML string writes. No secrets in `VITE_*` variables. UI role checks are navigation only; the API authorizes every request. |
| Demo sign-in | `/api/v1/auth/demo` answers 404 unless `TICKO_DEMO_LOGIN=true` and never in production; no demo password reaches the browser. |
| Mobile and realtime | Mobile bearer tokens only in the platform keystore (`expo-secure-store`), revoked on sign-out (which also unregisters push); HTTPS required outside development. Realtime events on private channels authorized by tenant and ticket, carrying ids only; push notifications carry only the ticket reference. |
| AI (RAG) | Suggestions use only published, customer-visible articles of the caller's tenant (explicit tenant filter plus RLS); ticket text and passages are untrusted data inside per-request nonce delimiters; the model has no tools, must return JSON citing provided passages, and low confidence becomes "I don't know". **Nothing reaches a customer without an agent's approval** (enforced by an architecture test). Only minimal ticket data is sent to the provider. |
| Classifier | Internal Docker network reachable only by the queue (no published port, no egress) and a service token checked in constant time; request size and length limits; PII (CPF, CNPJ, card, e-mail, phone) masked by the API before sending and again by the service; ticket text never logged; output accepted only from closed enums and rendered as text; prompt-injection eval in CI; the AI never overwrites a person's choice; automatic classification and agent corrections recorded in append-only `audit_events`. Base model pinned by revision and SHA-256, safetensors only; no pickle. |
| Infrastructure | Postgres and Redis on an internal Docker network with no published ports; non-root, read-only containers with all capabilities dropped; only the web server binds a port, on `127.0.0.1` by default. |
| Supply chain | `composer audit`, `pnpm audit`, `pip-audit` (hash-locked Python dependencies installed with `--require-hashes`) and gitleaks in CI; Docker base images pinned by digest; exact versions in `package.json` with a frozen lockfile; GitHub Actions pinned by commit SHA with least-privilege permissions. |

## Secrets

- Never commit `.env` files, keys or tokens. `make env` generates local secrets.
- AI provider keys (`GEMINI_API_KEY`, `GROQ_API_KEY`, optional `EXPO_ACCESS_TOKEN`) belong only in
  your local `.env` or a secret manager; they are sent in request headers and never logged.
  The default `fake` provider needs no key.
- If a secret is committed by mistake, **rotate it first**, then remove it from history.
