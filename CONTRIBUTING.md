# Contributing

Thanks for helping! Ticko is a monorepo; everything runs in Docker (`make help` lists the targets).

## Setup

```bash
make up && make demo      # the stack and the demo store
make web-install          # Node 22 + pnpm (corepack) for the web and mobile tooling
```

## Before opening a pull request

```bash
make ci                   # Pint, Larastan, Pest + coverage, audits, gitleaks, Semgrep,
                          # web, mobile and classifier lint/types/tests
make e2e                  # Playwright against the running stack
```

- **Tests:** new behaviour comes with tests (Pest, Vitest, Jest or pytest); coverage gates are
  80% (API, web, mobile) and 85% (classifier). Tenant isolation: every new route that targets one
  resource must be added to `TenantIsolationTest` (a meta-test fails otherwise).
- **API changes:** `make docs` regenerates the OpenAPI document; `pnpm install` regenerates the
  typed client.
- **Dependencies:** exact versions; Python only through `requirements*.in` + `make classifier-lock`
  (hashes); Docker images and GitHub Actions pinned by digest/SHA.
- **Secrets:** never in code, tests, fixtures or commits — only in your local `.env`. Report
  vulnerabilities privately (see [SECURITY.md](SECURITY.md)).
- **Data:** demo and test data are fictional; never use real customers' data.
- **Commits:** small, in English, imperative mood (`feat(api): …`, `fix(web): …`, `docs: …`).
- **Pull requests:** describe the change and how it was tested; CI must be green.
