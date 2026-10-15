# @ticko/api-client

Typed TypeScript client for the Ticko API, shared by `apps/web` and (later) `apps/mobile`.

- Types are generated from `apps/api/resources/openapi/openapi.json` by `openapi-typescript`
  (`pnpm api-client:generate`, also run by `pnpm install`). Regenerate the document with
  `make docs` whenever the API changes.
- `createTickoClient()` wraps `openapi-fetch`: same-origin cookies, the Sanctum CSRF header on
  state-changing requests, or a bearer token through `getAccessToken` for mobile.
- `unwrap()` returns the data or throws `ApiError` with RFC 9457 problem details
  (`error.fieldErrors()` for forms).
- Query strings follow Laravel's validation: booleans as `1`/`0`, arrays as `key[]=`.
- Entity types (`Ticket`, `TicketMessage`, `Category`, `TeamMember`, `Metrics`, …) are derived
  from the generated paths.

```ts
import { createTickoClient, unwrap } from '@ticko/api-client';

const api = createTickoClient();
const tickets = unwrap(await api.GET('/api/v1/tickets', { params: { query: { overdue: true } } }));
```
