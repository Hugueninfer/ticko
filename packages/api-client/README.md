# @ticko/api-client

Typed TypeScript client for the Ticko API, shared by `apps/web` and `apps/mobile`.

**Status:** skeleton (phase 1). Only type generation is wired up.

```bash
pnpm install
pnpm api-client:generate   # writes src/schema.d.ts from the versioned OpenAPI file
```

The source of truth is `apps/api/resources/openapi/openapi.json`, regenerated with
`make docs` whenever the API changes.
