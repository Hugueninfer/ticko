import { expect, signInAsDemo, test } from './fixtures';

test('serves the app with a strict CSP and no tokens in browser storage', async ({ page }) => {
  const response = await page.goto('/login');
  const csp = response?.headers()['content-security-policy'] ?? '';

  expect(csp).toContain("script-src 'self'");
  expect(csp).not.toContain('unsafe-inline');
  expect(csp).toContain("frame-ancestors 'none'");

  await signInAsDemo(page, 'Atendente');

  const storage = await page.evaluate(() => ({
    local: Object.keys(localStorage),
    session: Object.keys(sessionStorage),
  }));
  expect(storage.session).toEqual([]);
  expect(storage.local.filter((key) => /token|auth|session/i.test(key))).toEqual([]);

  const cookies = await page.context().cookies();
  const session = cookies.find((cookie) => cookie.name.endsWith('-session'));
  expect(session?.httpOnly).toBe(true);
});

test('rejects wrong credentials without saying which part was wrong', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Empresa').fill('acme');
  await page.getByLabel('E-mail').fill('agent@acme.test');
  await page.getByLabel('Senha').fill('definitely-not-the-password');
  await page.getByRole('button', { name: 'Entrar', exact: true }).click();

  await expect(page.getByRole('alert')).toHaveText('E-mail ou senha incorretos.');
});

test('signs out and protects pages again', async ({ page }) => {
  await signInAsDemo(page, 'Atendente');
  await page.getByRole('button', { name: 'Sair' }).click();

  await expect(page).toHaveURL(/\/login$/);
  await page.goto('/tickets');
  await expect(page).toHaveURL(/\/login$/);
});
