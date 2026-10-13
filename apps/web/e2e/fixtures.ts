import { expect, test as base, type Page } from '@playwright/test';

/** Fails the test on any CSP violation or uncaught error in the page. */
export const test = base.extend<{ page: Page }>({
  page: async ({ page }, use) => {
    const problems: string[] = [];

    page.on('pageerror', (error) => problems.push(`page error: ${error.message}`));
    page.on('console', (message) => {
      if (message.type() === 'error' && /Content Security Policy|Refused to/i.test(message.text())) {
        problems.push(`CSP: ${message.text()}`);
      }
    });

    await use(page);
    expect(problems, problems.join('\n')).toEqual([]);
  },
});

export { expect };

export async function signInAsDemo(page: Page, role: 'Atendente' | 'Admin'): Promise<void> {
  await page.goto('/login');
  await page.getByRole('button', { name: new RegExp(`^${role}`) }).click();
  await expect(page.getByRole('button', { name: 'Sair' })).toBeVisible();
}

export function unique(prefix: string): string {
  return `${prefix} ${Date.now().toString(36)}`;
}
