import { screen, waitFor } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { mockApi, problem } from '@/test/api-mock';
import { admin, agent, makeUser, page } from '@/test/fixtures';
import { renderApp } from '@/test/render';

const guest = { 'GET /api/v1/auth/me': problem(401, 'Unauthenticated.') };

describe('LoginPage', () => {
  it('redirects guests to the login page', async () => {
    mockApi({ ...guest, 'GET /api/v1/auth/demo': problem(404, 'x') });
    const { router } = renderApp('/tickets');

    expect(await screen.findByRole('heading', { name: 'Entrar' })).toBeInTheDocument();
    expect(router.state.location.pathname).toBe('/login');
    expect(screen.queryByText(/Entrar como demo/)).not.toBeInTheDocument();
  });

  it('validates the form before calling the API', async () => {
    const api = mockApi({ ...guest, 'GET /api/v1/auth/demo': problem(404, 'x') });
    const { user } = renderApp('/login');

    await user.click(await screen.findByRole('button', { name: 'Entrar' }));

    expect(await screen.findByText('Informe um e-mail válido.')).toBeInTheDocument();
    expect(screen.getAllByText('Obrigatório.')).toHaveLength(2);
    expect(api.find('POST', '/api/v1/auth/login')).toHaveLength(0);
  });

  it('signs in with the CSRF cookie and the tenant header, then opens the queue', async () => {
    let me: object = { status: 401, body: { type: 'x', title: 'Unauthenticated.', status: 401, detail: 'x' } };
    const api = mockApi({
      'GET /api/v1/auth/me': () => me,
      'GET /api/v1/auth/demo': problem(404, 'x'),
      'POST /api/v1/auth/login': () => {
        me = { data: agent };
        return { data: agent };
      },
      'GET /api/v1/tickets': page([]),
      'GET /api/v1/categories': { data: [] },
    });
    const { user, router } = renderApp('/login');

    await user.type(await screen.findByLabelText('Empresa'), 'acme');
    await user.type(screen.getByLabelText('E-mail'), 'ana@acme.test');
    await user.type(screen.getByLabelText('Senha'), 'secret-password');
    await user.click(screen.getByRole('button', { name: 'Entrar' }));

    await waitFor(() => expect(router.state.location.pathname).toBe('/tickets'));
    const [login] = api.find('POST', '/api/v1/auth/login');
    expect(login?.headers.get('X-Tenant')).toBe('acme');
    expect(login?.headers.get('X-XSRF-TOKEN')).toBe('test-xsrf');
    expect(login?.body).toEqual({ email: 'ana@acme.test', password: 'secret-password' });
    expect(api.find('GET', '/sanctum/csrf-cookie')).toHaveLength(1);
    expect(localStorage.getItem('ticko.tenant')).toBe('acme');
    expect(sessionStorage.length).toBe(0);
    expect(Object.keys(localStorage).some((key) => /token/i.test(key))).toBe(false);
  });

  it('shows a generic error for wrong credentials and throttling', async () => {
    let attempts = 0;
    mockApi({
      ...guest,
      'GET /api/v1/auth/demo': problem(404, 'x'),
      'POST /api/v1/auth/login': () =>
        ++attempts === 1 ? problem(422, 'invalid', { email: ['These credentials do not match.'] }) : problem(429, 'slow down'),
    });
    const { user } = renderApp('/login');

    await user.type(await screen.findByLabelText('Empresa'), 'acme');
    await user.type(screen.getByLabelText('E-mail'), 'ana@acme.test');
    await user.type(screen.getByLabelText('Senha'), 'wrong');
    await user.click(screen.getByRole('button', { name: 'Entrar' }));
    expect(await screen.findByText('E-mail ou senha incorretos.')).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: 'Entrar' }));
    expect(await screen.findByText('Muitas tentativas. Aguarde um minuto e tente de novo.')).toBeInTheDocument();
  });

  it('refuses customers and signs them out', async () => {
    const api = mockApi({
      ...guest,
      'GET /api/v1/auth/demo': problem(404, 'x'),
      'POST /api/v1/auth/login': { data: makeUser({ role: 'customer' }) },
      'POST /api/v1/auth/logout': null,
    });
    const { user } = renderApp('/login');

    await user.type(await screen.findByLabelText('Empresa'), 'acme');
    await user.type(screen.getByLabelText('E-mail'), 'c@acme.test');
    await user.type(screen.getByLabelText('Senha'), 'secret');
    await user.click(screen.getByRole('button', { name: 'Entrar' }));

    expect(await screen.findByText(/Clientes usam o app Ticko no celular/)).toBeInTheDocument();
    await waitFor(() => expect(api.find('POST', '/api/v1/auth/logout')).toHaveLength(1));
  });

  it('offers demo shortcuts when the API enables them', async () => {
    let me: object = { status: 401, body: { type: 'x', title: 'x', status: 401, detail: 'x' } };
    const api = mockApi({
      'GET /api/v1/auth/me': () => me,
      'GET /api/v1/auth/demo': { data: { tenant: 'acme', roles: ['agent', 'admin'] } },
      'POST /api/v1/auth/demo': () => {
        me = { data: admin };
        return { data: admin };
      },
      'GET /api/v1/metrics': {
        data: {
          period: { from: '', to: '' },
          tickets_received: { value: 0, previous: 0 },
          first_response_median_minutes: { value: null, previous: null },
          sla_first_response_percent: { value: null, previous: null },
          volume_by_day: [],
          by_category: [],
        },
      },
    });
    const { user, router } = renderApp('/login');

    expect(await screen.findByText('Entrar como demo · acme')).toBeInTheDocument();
    await user.click(screen.getByRole('button', { name: /Admin/ }));

    await waitFor(() => expect(router.state.location.pathname).toBe('/dashboard'));
    expect(api.find('POST', '/api/v1/auth/demo')[0]?.body).toEqual({ role: 'admin' });
  });
});
