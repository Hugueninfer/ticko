import { screen, waitFor } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { mockApi, problem } from '@/test/api-mock';
import { admin, agent, makeUser, page } from '@/test/fixtures';
import { renderApp } from '@/test/render';

const queue = { 'GET /api/v1/tickets': page([]), 'GET /api/v1/categories': { data: [] } };

describe('route protection', () => {
  it('keeps admin pages and links away from agents', async () => {
    mockApi({ 'GET /api/v1/auth/me': { data: agent }, ...queue });
    renderApp('/team');

    expect(await screen.findByText('Você não tem acesso a esta página.')).toBeInTheDocument();
    expect(screen.queryByRole('link', { name: 'Equipe' })).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Fila' })).toBeInTheDocument();
  });

  it('shows admin navigation to admins', async () => {
    mockApi({ 'GET /api/v1/auth/me': { data: admin }, ...queue });
    renderApp('/tickets');

    expect(await screen.findByRole('link', { name: 'Equipe' })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Categorias e SLA' })).toBeInTheDocument();
    expect(screen.getByText('Loja Aurora')).toBeInTheDocument();
  });

  it('tells customers the web app is for staff', async () => {
    mockApi({ 'GET /api/v1/auth/me': { data: makeUser({ role: 'customer' }) } });
    renderApp('/tickets');

    expect(await screen.findByText(/O app web é para a equipe/)).toBeInTheDocument();
  });

  it('offers a retry when the session check fails', async () => {
    mockApi({ 'GET /api/v1/auth/me': problem(500, 'boom') });
    renderApp('/tickets');

    expect(await screen.findByRole('button', { name: 'Tentar de novo' })).toBeInTheDocument();
  });

  it('sends signed-in staff away from the login page and signs out', async () => {
    let signedIn = true;
    const api = mockApi({
      'GET /api/v1/auth/me': () => (signedIn ? { data: agent } : problem(401, 'x')),
      'POST /api/v1/auth/logout': () => {
        signedIn = false;
        return null;
      },
      'GET /api/v1/auth/demo': problem(404, 'x'),
      ...queue,
    });
    const { router, user } = renderApp('/login');

    await waitFor(() => expect(router.state.location.pathname).toBe('/tickets'));
    await user.click(await screen.findByRole('button', { name: 'Sair' }));

    await waitFor(() => expect(router.state.location.pathname).toBe('/login'));
    expect(api.find('POST', '/api/v1/auth/logout')[0]?.headers.get('X-XSRF-TOKEN')).toBeNull();
  });

  it('switches theme and language from the sidebar', async () => {
    mockApi({ 'GET /api/v1/auth/me': { data: agent }, ...queue });
    const { user } = renderApp('/nowhere');

    expect(await screen.findByText('Página não encontrada.')).toBeInTheDocument();
    await user.click(screen.getByRole('button', { name: 'Tema escuro' }));
    expect(document.documentElement.classList.contains('dark')).toBe(true);
    await user.click(screen.getByRole('button', { name: 'Tema claro' }));

    await user.selectOptions(screen.getByLabelText('Idioma'), 'en');
    expect(await screen.findByText('Page not found.')).toBeInTheDocument();
    await user.selectOptions(screen.getByLabelText('Language'), 'pt-BR');
  });
});
