import { expect, signInAsDemo, test, unique } from './fixtures';

test('admin sees the dashboard and creates a category with SLA targets', async ({ page }) => {
  await signInAsDemo(page, 'Admin');
  await expect(page.getByRole('heading', { name: 'Visão geral' })).toBeVisible();
  await expect(page.getByText('Tickets recebidos')).toBeVisible();

  await page.getByRole('link', { name: 'Categorias e SLA' }).click();
  await page.getByRole('button', { name: 'Nova categoria' }).click();

  const name = unique('Garantia');
  await page.getByLabel('Nome').fill(name);
  await page.getByLabel('Descrição').fill('Defeitos cobertos pela garantia');
  await page.getByLabel('1ª resposta · Urgente', { exact: true }).fill('10');
  await page.getByLabel('Resolução · Urgente', { exact: true }).fill('2');
  await page.getByRole('button', { name: 'Salvar categoria' }).click();

  await expect(page.getByText('Categoria criada.')).toBeVisible();
  await expect(page.getByRole('button', { name: new RegExp(name) })).toBeVisible();
  await expect(page.getByLabel('1ª resposta · Urgente', { exact: true })).toHaveValue('10');
});

test('admin adds a team member who can then sign in', async ({ page }) => {
  await signInAsDemo(page, 'Admin');
  await page.getByRole('link', { name: 'Equipe' }).click();

  const suffix = Date.now().toString(36);
  const email = `e2e-${suffix}@acme.test`;
  const password = `e2e-passphrase-${suffix}-long`;

  await page.getByLabel('Nome').fill(unique('Bruna Lopes'));
  await page.getByLabel('E-mail').fill(email);
  await page.getByLabel('Papel', { exact: true }).selectOption('agent');
  await page.getByLabel('Senha inicial').fill(password);
  await page.getByRole('button', { name: 'Adicionar pessoa' }).click();
  await expect(page.getByText('Pessoa adicionada.')).toBeVisible();
  await expect(page.getByText(email)).toBeVisible();

  await page.getByRole('button', { name: 'Sair' }).click();
  await page.getByLabel('Empresa').fill('acme');
  await page.getByLabel('E-mail').fill(email);
  await page.getByLabel('Senha').fill(password);
  await page.getByRole('button', { name: 'Entrar', exact: true }).click();

  await expect(page.getByRole('heading', { name: 'Fila de tickets' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Equipe' })).toHaveCount(0);
});
