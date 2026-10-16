import { defineConfig, devices } from '@playwright/test';

/**
 * End-to-end tests run against the full Docker stack (Caddy serving the web
 * app and the API on one origin), seeded with `php artisan ticko:demo` and
 * TICKO_DEMO_LOGIN=true.
 */
export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  workers: 1,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8080',
    locale: 'pt-BR',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      testIgnore: /observability\.spec\.ts/,
      use: { ...devices['Desktop Chrome'], ...(process.env.E2E_CHANNEL ? { channel: process.env.E2E_CHANNEL } : {}) },
    },
    // Grafana (`make up-obs`), after the app suite has produced traffic:
    // GRAFANA_URL=http://localhost:3001 GRAFANA_PASSWORD=... pnpm e2e --project observability
    {
      name: 'observability',
      testMatch: /observability\.spec\.ts/,
      use: { ...devices['Desktop Chrome'], ...(process.env.E2E_CHANNEL ? { channel: process.env.E2E_CHANNEL } : {}) },
    },
  ],
});
