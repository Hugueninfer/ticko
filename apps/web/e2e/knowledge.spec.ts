import { expect, signInAsDemo, test, unique } from './fixtures';

// The stack runs the offline "fake" AI provider: deterministic embeddings and
// extractive drafts. The demo tenant's articles are indexed by the queue.

test('the knowledge base is indexed and searchable', async ({ page }) => {
  await signInAsDemo(page, 'Atendente');
  await page.getByRole('link', { name: 'Base de conhecimento' }).click();
  await expect(page.getByRole('heading', { name: 'Base de conhecimento' })).toBeVisible();

  const row = page.getByRole('row', { name: /Política de entregas e prazos/ });
  await expect(row.getByText(/Indexado · \d+ trechos/)).toBeVisible({ timeout: 60_000 });

  await row.getByRole('link', { name: 'Política de entregas e prazos' }).click();
  await page.getByRole('tab', { name: 'Como a IA vê' }).click();
  await expect(page.getByText('Rastreio sem atualização').first()).toBeVisible();

  await page.getByRole('link', { name: 'Playground de busca' }).click();
  await page
    .getByLabel('Escreva como um cliente perguntaria…')
    .fill('o rastreamento não é atualizado há uma semana, o que faço?');
  await page.getByRole('button', { name: 'Buscar' }).click();
  await expect(page.locator('main, body').getByRole('link', { name: 'Política de entregas e prazos' }).first()).toBeVisible();
  await expect(page.getByText(/Latência: \d+ ms/)).toBeVisible();
});

test('an agent writes, publishes and indexes an article', async ({ page }) => {
  const title = unique('Garantia de eletrônicos');

  await signInAsDemo(page, 'Atendente');
  await page.goto('/knowledge');
  await page.getByRole('link', { name: 'Novo artigo' }).click();
  await page.getByLabel('Título').fill(title);
  await page
    .getByLabel('Texto (Markdown)')
    .fill('## Prazo\n\nEletrônicos têm 90 dias de garantia legal.\n\n<script>alert(1)</script>');
  await expect(page.getByRole('heading', { name: 'Prazo' })).toBeVisible();
  await page.getByRole('button', { name: 'Publicar', exact: true }).click();

  await expect(page.getByText('Publicado', { exact: true }).first()).toBeVisible();
  await expect(page.getByText(/Indexado · 1 trechos/)).toBeVisible({ timeout: 60_000 });
});

test('an agent reviews and sends an AI draft; nothing is sent without approval', async ({ page }) => {
  await signInAsDemo(page, 'Atendente');
  await page.getByRole('tab', { name: /Todos/ }).click();
  await page
    .getByRole('link', { name: /rastre(io|amento) par(ou|ado)/i })
    .first()
    .click();

  const panel = page.getByRole('complementary', { name: 'Sugestão da IA' });
  const regenerate = panel.getByRole('button', { name: /Gerar (sugestão|nova sugestão)/ }).first();
  await regenerate.click();

  await expect(panel.getByRole('button', { name: 'Aprovar e enviar' })).toBeVisible({ timeout: 30_000 });

  // The draft cites its source; drafting alone added nothing to the conversation.
  const conversation = page.getByRole('region', { name: 'Conversa' });
  const opening = (await panel.locator('.whitespace-pre-wrap').first().innerText()).slice(0, 40);
  expect(opening.length).toBeGreaterThan(5);
  const before = await conversation.getByText(opening, { exact: false }).count();

  await panel.getByRole('button', { name: 'Fonte 1' }).first().click();
  await expect(panel.getByRole('link', { name: /Abrir artigo/ })).toBeVisible();

  await panel.getByRole('button', { name: 'Aprovar e enviar' }).click();
  await expect(panel.getByText('Enviada sem edições')).toBeVisible();

  // Sent as the agent's reply, without the source markers.
  await expect(conversation.getByText(opening, { exact: false })).toHaveCount(before + 1);
  await expect(conversation.getByText(/\[1\]/)).toHaveCount(0);
  await expect(page.getByLabel('Status')).toHaveValue('pending');
});
