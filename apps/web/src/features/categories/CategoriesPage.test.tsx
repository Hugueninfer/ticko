import { screen, waitFor } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { mockApi, problem } from '@/test/api-mock';
import { admin, makeCategory } from '@/test/fixtures';
import { renderApp } from '@/test/render';

function setup(overrides: Parameters<typeof mockApi>[0] = {}) {
  return mockApi({
    'GET /api/v1/auth/me': { data: admin },
    'GET /api/v1/categories': { data: [makeCategory(), makeCategory({ id: 'cat-pay', name: 'Pagamento', tickets_count: 1 })] },
    ...overrides,
  });
}

describe('CategoriesPage', () => {
  it('shows each category with its effective SLA targets', async () => {
    setup();
    renderApp('/categories');

    expect(await screen.findByLabelText('Nome')).toHaveValue('Entrega');
    expect(screen.getByLabelText('1ª resposta · Urgente')).toHaveValue('15');
    expect(screen.getByLabelText('Resolução · Urgente')).toHaveValue('4');
    expect(screen.getByLabelText('1ª resposta · Alta')).toHaveValue('1');
    expect(screen.getAllByText('personalizado', { selector: '[data-slot=badge]' })).toHaveLength(1);
    expect(screen.getByText('3 tickets')).toBeInTheDocument();
  });

  it('saves only changed or custom targets, in minutes', async () => {
    const api = setup({ 'PUT /api/v1/categories/:id': { data: makeCategory() } });
    const { user } = renderApp('/categories');

    const urgent = await screen.findByLabelText('1ª resposta · Urgente');
    await user.clear(urgent);
    await user.type(urgent, '10');
    await user.click(screen.getByRole('button', { name: 'Salvar categoria' }));

    await waitFor(() => expect(api.find('PUT', '/api/v1/categories/cat-delivery')).toHaveLength(1));
    expect(api.find('PUT', '/api/v1/categories/cat-delivery')[0]?.body).toEqual({
      name: 'Entrega',
      description: 'Atrasos e rastreio',
      sla_targets: {
        urgent: { first_response_minutes: 10, resolution_minutes: 240 },
        high: { first_response_minutes: 60, resolution_minutes: 480 },
      },
    });
    expect(await screen.findByText('Categoria salva.')).toBeInTheDocument();
  });

  it('validates targets and can reset them to the defaults', async () => {
    const api = setup({ 'PUT /api/v1/categories/:id': { data: makeCategory() } });
    const { user } = renderApp('/categories');

    const resolution = await screen.findByLabelText('Resolução · Alta');
    await user.selectOptions(screen.getByLabelText('Resolução · Alta (min/h)'), 'min');
    await user.clear(resolution);
    await user.type(resolution, '30');
    await user.click(screen.getByRole('button', { name: 'Salvar categoria' }));
    expect(await screen.findByText('A resolução não pode ser antes da 1ª resposta.')).toBeInTheDocument();
    expect(api.find('PUT', '/api/v1/categories/cat-delivery')).toHaveLength(0);

    await user.clear(resolution);
    await user.type(resolution, '600');
    await user.click(screen.getByRole('button', { name: 'Usar metas padrão' }));
    await waitFor(() => expect(api.find('PUT', '/api/v1/categories/cat-delivery')[0]?.body).toMatchObject({ sla_targets: null }));
  });

  it('creates and deletes categories', async () => {
    const api = setup({
      'POST /api/v1/categories': { status: 201, body: { data: makeCategory({ id: 'cat-new', name: 'Conta e acesso' }) } },
      'DELETE /api/v1/categories/:id': null,
    });
    const { user } = renderApp('/categories');

    await user.click(await screen.findByRole('button', { name: /Nova categoria/ }));
    await user.type(screen.getByLabelText('Nome'), 'Conta e acesso');
    await user.click(screen.getByRole('button', { name: 'Salvar categoria' }));
    await waitFor(() => expect(api.find('POST', '/api/v1/categories')).toHaveLength(1));
    expect(api.find('POST', '/api/v1/categories')[0]?.body).toEqual({ name: 'Conta e acesso', description: null });

    await user.click(screen.getByRole('button', { name: 'Pagamento 1 tickets' }));
    await user.click(screen.getByRole('button', { name: 'Excluir' }));
    const dialog = await screen.findByRole('dialog');
    expect(dialog).toHaveTextContent('Excluir a categoria Pagamento?');
    await user.click(screen.getAllByRole('button', { name: 'Excluir' }).at(-1)!);
    await waitFor(() => expect(api.find('DELETE', '/api/v1/categories/cat-pay')).toHaveLength(1));
  });

  it('shows name errors returned by the API', async () => {
    setup({ 'PUT /api/v1/categories/:id': problem(422, 'invalid', { name: ['A category with this name already exists.'] }) });
    const { user } = renderApp('/categories');

    await user.click(await screen.findByRole('button', { name: 'Salvar categoria' }));
    expect(await screen.findByText('A category with this name already exists.')).toBeInTheDocument();
  });
});
