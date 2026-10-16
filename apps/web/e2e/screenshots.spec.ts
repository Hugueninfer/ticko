import { mkdirSync } from 'node:fs';
import { resolve } from 'node:path';

import { expect, test, type Page } from '@playwright/test';

import { signInAsDemo } from './fixtures';

/**
 * Screenshots for docs/DEMO.md, taken from the running demo stack:
 *   make up-obs && make demo && make screenshots
 * Grafana shots need GRAFANA_URL and GRAFANA_PASSWORD (from .env).
 */
const out = resolve(import.meta.dirname, '../../../docs/screenshots');
const grafana = process.env.GRAFANA_URL ?? '';
const password = process.env.GRAFANA_PASSWORD ?? '';

test.use({ viewport: { width: 1440, height: 900 }, colorScheme: 'light' });
test.beforeAll(() => mkdirSync(out, { recursive: true }));

async function shot(page: Page, name: string): Promise<void> {
  // Let fonts, charts and live updates settle.
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: `${out}/${name}.png` });
}

async function openTicket(page: Page, subject: RegExp): Promise<void> {
  await page.goto('/tickets?tab=all');
  await page.getByRole('link', { name: subject }).first().click();
  await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
}

test('web app', async ({ page }) => {
  test.setTimeout(120_000);

  await page.goto('/login');
  await expect(page.getByRole('button', { name: /^Atendente/ })).toBeVisible();
  await shot(page, '01-login');

  await signInAsDemo(page, 'Atendente');
  await page.goto('/tickets?tab=all');
  await expect(page.locator('tbody tr').first()).toBeVisible();
  await shot(page, '02-queue');

  // A draft ready for review, with its sources, and the AI classification.
  await openTicket(page, /Cobrança em duplicidade no cartão/);
  await expect(page.getByRole('button', { name: 'Aprovar e enviar' })).toBeVisible({ timeout: 30_000 });
  await expect(page.getByRole('region', { name: 'Classificação da IA' })).toBeVisible();
  await shot(page, '03-ticket-ai-draft');

  // "I don't know": no good source in the knowledge base.
  await openTicket(page, /loja física em Curitiba/);
  await shot(page, '04-ticket-dont-know');

  // A classification corrected by an agent (no AI badge on the corrected field).
  await openTicket(page, /zíper quebrado/);
  await shot(page, '05-ticket-corrected');

  await page.goto('/knowledge');
  await expect(page.getByText('Política de entregas e prazos')).toBeVisible();
  await shot(page, '06-knowledge-base');

  await page.goto('/knowledge/playground');
  await page.getByRole('textbox').first().fill('meu pedido está atrasado, o rastreio não muda');
  await page.keyboard.press('Enter');
  await expect(page.getByText(/Política de entregas/).first()).toBeVisible({ timeout: 30_000 });
  await shot(page, '07-search-playground');

  await page.getByRole('button', { name: 'Sair' }).click();
  await signInAsDemo(page, 'Admin');
  await page.goto('/dashboard');
  await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
  await shot(page, '08-admin-dashboard');
});

test('Grafana dashboards', async ({ page }) => {
  test.skip(!grafana || !password, 'GRAFANA_URL and GRAFANA_PASSWORD are not set');
  test.setTimeout(120_000);

  await page.goto(`${grafana}/login`);
  await page.getByLabel(/username/i).fill('admin');
  await page
    .getByLabel(/password/i)
    .first()
    .fill(password);
  await page.getByRole('button', { name: /log in/i }).click();
  await expect(page).not.toHaveURL(/\/login/);

  for (const [uid, name] of [
    ['ticko-api', '09-grafana-api'],
    ['ticko-ai', '10-grafana-ai'],
    ['ticko-sla', '11-grafana-sla'],
    ['ticko-classifier', '12-grafana-classifier'],
    ['ticko-queue', '13-grafana-queue'],
  ] as const) {
    await page.goto(`${grafana}/d/${uid}?orgId=1&from=now-1h&to=now&kiosk`);
    await page.waitForTimeout(4_000);
    await shot(page, name);
  }
});
