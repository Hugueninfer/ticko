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
| Authorization | Policies on every resource; form requests authorize before validating; an architecture test fails if an API controller action does not call `authorize()`. Horizon is admin-only (and allow-listed outside `local`). |
| Mass assignment | Explicit `$fillable` on every model; `tenant_id`, ownership and status fields are never fillable. |
| Uploads | Type detected from content, dimension and size limits, re-encoded (drops EXIF/GPS and appended payloads), random names on a private disk, served through short-lived signed URLs with `nosniff` and a sandboxing CSP. |
| Errors | RFC 7807 problem details; internal messages are never exposed outside debug mode. |
| Infrastructure | Postgres and Redis on an internal Docker network with no published ports; non-root, read-only containers with all capabilities dropped; only the web server binds a port, on `127.0.0.1` by default. |
| Supply chain | `composer audit` and gitleaks in CI; GitHub Actions pinned by commit SHA with least-privilege permissions. |

## Secrets

- Never commit `.env` files, keys or tokens. `make env` generates local secrets.
- AI provider keys (later phases) belong only in your local `.env` or a secret manager.
- If a secret is committed by mistake, **rotate it first**, then remove it from history.
