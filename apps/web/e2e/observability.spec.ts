import { expect, test, type APIRequestContext } from '@playwright/test';

/**
 * Grafana (`make up-obs`): login is required, and every provisioned
 * dashboard panel returns data. Needs GRAFANA_URL and GRAFANA_PASSWORD
 * (from .env, never stored in the repository) and traffic from the app
 * suite and `make demo` first.
 */
const grafana = process.env.GRAFANA_URL ?? '';
const password = process.env.GRAFANA_PASSWORD ?? '';

test.skip(!grafana || !password, 'GRAFANA_URL and GRAFANA_PASSWORD are not set');

const DASHBOARDS = ['ticko-api', 'ticko-queue', 'ticko-sla', 'ticko-ai', 'ticko-classifier'];

interface Panel {
  type: string;
  title: string;
  datasource?: { type: string; uid: string };
  targets?: { expr: string; instant?: boolean }[];
}

/** Rows returned by a panel's queries over the last hour. */
async function rows(api: APIRequestContext, panel: Panel): Promise<number> {
  let total = 0;
  const now = Date.now();

  for (const target of panel.targets ?? []) {
    const instant = Boolean(target.instant) || panel.type === 'stat';
    const response = await api.post(`${grafana}/api/ds/query`, {
      data: {
        from: String(now - 3_600_000),
        to: String(now),
        queries: [
          {
            refId: 'A',
            datasource: panel.datasource,
            expr: target.expr.replaceAll('$tenant', '.*').replaceAll('[$__range]', '[1h]'),
            instant,
            range: !instant,
            intervalMs: 15_000,
            maxDataPoints: 100,
            ...(panel.datasource?.type === 'loki' ? { queryType: 'range' } : {}),
          },
        ],
      },
    });
    expect(response.ok(), `${panel.title}: ${response.status()}`).toBe(true);
    const body = (await response.json()) as { results: { A: { frames?: { data: { values?: unknown[][] } }[] } } };

    for (const frame of body.results.A.frames ?? []) {
      total += frame.data.values?.[0]?.length ?? 0;
    }
  }

  return total;
}

test('Grafana refuses anonymous access, basic auth and sign-up', async ({ page, request }) => {
  expect((await request.get(`${grafana}/api/search`)).status()).toBe(401);
  expect((await request.get(`${grafana}/api/dashboards/uid/ticko-api`)).status()).toBe(401);
  const basic = await request.get(`${grafana}/api/search`, {
    headers: { Authorization: `Basic ${Buffer.from(`admin:${password}`).toString('base64')}` },
  });
  expect(basic.status()).toBe(401);
  expect((await request.get(`${grafana}/api/frontend/settings`)).status()).toBe(401);

  await page.goto(`${grafana}/d/ticko-api`);
  await expect(page).toHaveURL(/\/login/);
  await expect(page.getByRole('link', { name: /sign up/i })).toHaveCount(0);

  // The default password is not accepted.
  await page.getByLabel(/username/i).fill('admin');
  await page
    .getByLabel(/password/i)
    .first()
    .fill('admin');
  await page.getByRole('button', { name: /log in/i }).click();
  await expect(page.getByText(/invalid username or password/i)).toBeVisible();
});

test('every provisioned dashboard panel has data', async ({ page }) => {
  test.setTimeout(240_000);

  await page.goto(`${grafana}/login`);
  await page.getByLabel(/username/i).fill('admin');
  await page
    .getByLabel(/password/i)
    .first()
    .fill(password);
  await page.getByRole('button', { name: /log in/i }).click();
  await expect(page).not.toHaveURL(/\/login/);

  const api = page.request;
  const search = (await (await api.get(`${grafana}/api/search?tag=ticko`)).json()) as { uid: string }[];
  expect(search.map((d) => d.uid).sort()).toEqual([...DASHBOARDS].sort());

  const rules = (await (await api.get(`${grafana}/api/v1/provisioning/alert-rules`)).json()) as { title: string }[];
  expect(rules.length).toBeGreaterThanOrEqual(3);

  for (const uid of DASHBOARDS) {
    const { dashboard } = (await (await api.get(`${grafana}/api/dashboards/uid/${uid}`)).json()) as {
      dashboard: { title: string; panels: Panel[] };
    };

    for (const panel of dashboard.panels.filter((p) => p.type !== 'row')) {
      // Gauges are sampled every minute and metrics flushed every 15 s: poll a little.
      await expect
        .poll(() => rows(api, panel), { message: `${dashboard.title} › ${panel.title}`, timeout: 120_000, intervals: [5_000] })
        .toBeGreaterThan(0);
    }

    await page.goto(`${grafana}/d/${uid}`);
    await expect(page.getByText(dashboard.title).first()).toBeVisible();
  }
});
