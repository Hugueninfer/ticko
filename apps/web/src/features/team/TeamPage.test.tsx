import { screen, waitFor, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { mockApi, problem } from '@/test/api-mock';
import { admin, makeMember, page } from '@/test/fixtures';
import { renderApp } from '@/test/render';

function setup(overrides: Parameters<typeof mockApi>[0] = {}) {
  return mockApi({
    'GET /api/v1/auth/me': { data: admin },
    'GET /api/v1/team': (request) =>
      page(
        request.query.get('q') === 'zzz'
          ? []
          : [{ ...makeMember(), id: admin.id, name: admin.name, email: admin.email, role: 'admin' as const }, makeMember()],
      ),
    ...overrides,
  });
}

describe('TeamPage', () => {
  it("lists members and protects the admin's own row", async () => {
    setup();
    renderApp('/team');

    const caio = (await screen.findByText('Caio Freitas')).closest('tr')!;
    expect(within(caio).getByText('caio@acme.test')).toBeInTheDocument();
    expect(within(caio).getByText('4')).toBeInTheDocument();
    expect(within(caio).getByText('Ativo')).toBeInTheDocument();

    const self = screen.getByText('(você)').closest('tr')!;
    expect(within(self).getByLabelText('Papel de Renata Nogueira')).toBeDisabled();
    expect(within(self).queryByRole('button', { name: 'Desativar' })).not.toBeInTheDocument();
  });

  it('changes roles, deactivates and searches', async () => {
    const api = setup({ 'PATCH /api/v1/team/:id': { data: makeMember() } });
    const { user } = renderApp('/team');

    await user.selectOptions(await screen.findByLabelText('Papel de Caio Freitas'), 'admin');
    await user.click(screen.getByRole('button', { name: 'Desativar' }));
    await waitFor(() => expect(api.find('PATCH', '/api/v1/team/user-caio')).toHaveLength(2));
    expect(api.find('PATCH', '/api/v1/team/user-caio').map((request) => request.body)).toEqual([
      { role: 'admin' },
      { is_active: false },
    ]);

    await user.type(screen.getByLabelText('Buscar'), 'zzz');
    expect(await screen.findByText('Ninguém encontrado.')).toBeInTheDocument();
  });

  it('adds people and shows API validation errors on the fields', async () => {
    let calls = 0;
    const api = setup({
      'POST /api/v1/team': () =>
        ++calls === 1
          ? problem(422, 'invalid', { email: ['The email has already been taken.'] })
          : { status: 201, body: { data: makeMember() } },
    });
    const { user } = renderApp('/team');

    await user.click(await screen.findByRole('button', { name: 'Adicionar pessoa' }));
    expect(await screen.findByText('Informe um e-mail válido.')).toBeInTheDocument();

    await user.type(screen.getByLabelText('Nome'), 'Bruna Lopes');
    await user.type(screen.getByLabelText('E-mail'), 'bruna@acme.test');
    await user.selectOptions(screen.getByLabelText('Papel'), 'admin');
    await user.type(screen.getByLabelText('Senha inicial'), 'uma-senha-bem-longa');
    await user.click(screen.getByRole('button', { name: 'Adicionar pessoa' }));
    expect(await screen.findByText('The email has already been taken.')).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: 'Adicionar pessoa' }));
    expect(await screen.findByText('Pessoa adicionada.')).toBeInTheDocument();
    expect(api.find('POST', '/api/v1/team')[1]?.body).toEqual({
      name: 'Bruna Lopes',
      email: 'bruna@acme.test',
      role: 'admin',
      password: 'uma-senha-bem-longa',
    });
  });
});
