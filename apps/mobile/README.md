# apps/mobile — Ticko mobile app

**Status:** skeleton (phase 1). Nothing to run yet.

Planned: mobile app for customers (open and follow tickets, attach photos) and agents.

## How it will talk to the API

- `POST /api/v1/auth/tokens` with `X-Tenant: <slug>`, `email`, `password`, `device_name`
  returns an expiring bearer token bound to the tenant. Store it in the platform's secure
  storage (Keychain / Android Keystore), never in plain preferences.
- Send `Authorization: Bearer <token>`; revoke with `DELETE /api/v1/auth/tokens/current`.
- Photos are uploaded to `POST /api/v1/tickets/{id}/attachments`; the server strips EXIF
  (including GPS) by re-encoding, but the app should also avoid sending location metadata.
- Types come from `@ticko/api-client`.
