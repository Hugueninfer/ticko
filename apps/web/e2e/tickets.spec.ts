import { expect, signInAsDemo, test, unique } from './fixtures';

test('agent works the queue: filters, opens a ticket, replies and adds a note', async ({ page }) => {
  await signInAsDemo(page, 'Atendente');
  await expect(page.getByRole('heading', { name: 'Fila de tickets' })).toBeVisible();

  await page.getByRole('tab', { name: /Todos/ }).click();
  await expect(page).toHaveURL(/tab=all/);
  const rows = page.locator('tbody tr');
  await expect(rows.first()).toBeVisible();

  await page.getByLabel('Prioridade').selectOption('urgent');
  await expect(page).toHaveURL(/priority=urgent/);
  await page.getByRole('button', { name: 'Limpar filtros' }).click();
  await expect(page).not.toHaveURL(/priority=/);

  await expect(rows.first().getByRole('link')).toBeVisible();
  await rows.first().getByRole('link').click();
  await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
  await expect(page.getByText('Sugestões de resposta em breve')).toBeVisible();

  const reply = unique('Resposta E2E');
  await page.getByRole('textbox', { name: 'Responder' }).fill(reply);
  await page.getByRole('button', { name: 'Enviar' }).click();
  await expect(page.getByText(reply)).toBeVisible();

  const note = unique('Nota E2E');
  await page.getByRole('tab', { name: 'Nota interna' }).click();
  await page.getByRole('textbox', { name: 'Nota interna' }).fill(note);
  await page.keyboard.press('Control+Enter');
  await expect(page.getByText(note)).toBeVisible();

  const assignee = page.getByLabel('Responsável');
  await assignee.selectOption('');
  await expect(assignee).toHaveValue('');
  await page.getByRole('button', { name: 'Atribuir a mim' }).click();
  await expect(assignee).not.toHaveValue('');
  await expect(page.getByRole('button', { name: 'Atribuir a mim' })).toHaveCount(0);

  // A staff reply moves the ticket to "waiting on customer".
  await expect(page.getByLabel('Status')).toHaveValue('pending');
});

test('agents cannot open admin pages', async ({ page }) => {
  await signInAsDemo(page, 'Atendente');
  await expect(page.getByRole('link', { name: 'Equipe' })).toHaveCount(0);

  await page.goto('/team');
  await expect(page.getByText('Você não tem acesso a esta página.')).toBeVisible();
});
