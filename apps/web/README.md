# apps/web — Ticko web app

The app agents and admins use: ticket queue, ticket detail with the conversation, team,
categories and SLA, and the admin dashboard. Customers use the mobile app.

React 19 · TypeScript (strict) · Vite · TanStack Query · React Router · Tailwind CSS with
shadcn-style components · React Hook Form + Zod · react-i18next (pt-BR default, English).

## Run it

With the Docker stack (`make up`), the built app is already served at `http://localhost:8080`.
For hot reload (Node 22; pnpm comes from corepack):

```bash
make web-dev   # http://localhost:5173, proxies /api, /sanctum and /up to the stack
```

`make demo` seeds the demo tenant; with `TICKO_DEMO_LOGIN=true` the sign-in page offers
one-click Agent and Admin buttons.

## How it talks to the API

- **One origin.** The browser only calls the origin it was loaded from. Docker: Caddy serves
  the app and proxies the API paths to Laravel. Development: the Vite dev server proxies them.
  `localhost:5173` is listed in `SANCTUM_STATEFUL_DOMAINS` so Sanctum treats those proxied,
  same-origin requests as first-party; there is no CORS configuration with credentials.
- **Session cookie + CSRF.** `GET /sanctum/csrf-cookie`, then `POST /api/v1/auth/login` with the
  `X-Tenant: <company>` header; later requests send the session cookie and `X-XSRF-TOKEN`.
  No token is ever stored in the browser (`localStorage` only keeps the theme, the language and
  the last company identifier).
- **Typed client.** `@ticko/api-client` is generated from the versioned OpenAPI document, so a
  breaking API change fails `tsc`.

## Rules

- User content is rendered as text. `dangerouslySetInnerHTML`, `innerHTML`/`outerHTML`
  writes, `insertAdjacentHTML`, `document.write`, `eval` and `sessionStorage` are lint errors.
  If Markdown is ever rendered, it must go through DOMPurify first.
- No secrets in `VITE_*` variables: everything in the bundle is public.
- The production CSP allows no inline scripts or styles; avoid libraries that inject `<style>`
  tags at runtime.
- Role checks in the UI only shape navigation; the API authorizes every request.

## Tests

```bash
make web-test   # ESLint, Prettier, tsc, Vitest with coverage (thresholds: 80% lines)
make e2e        # Playwright against the running stack (make up && make demo first)
```

Component tests render the real route tree against a small fetch router
(`src/test/api-mock.ts`). End-to-end tests fail on any CSP violation or page error.
