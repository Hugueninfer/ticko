import { screen, waitFor } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { mockApi, problem } from '@/test/api-mock';
import { admin, makeMetrics } from '@/test/fixtures';
import { renderApp } from '@/test/render';

describe('DashboardPage', () => {
  it('shows KPIs with the comparison, the daily volume and categories', async () => {
    const api = mockApi({ 'GET /api/v1/auth/me': { data: admin }, 'GET /api/v1/metrics': { data: makeMetrics() } });
    const { user } = renderApp('/dashboard');

    expect(await screen.findByText('1.412')).toBeInTheDocument();
    expect(screen.getByText('+8% vs. período anterior')).toBeInTheDocument();
    expect(screen.getByText('14 min')).toBeInTheDocument();
    expect(screen.getByText('−6 min vs. período anterior')).toHaveClass('text-success');
    expect(screen.getByText('93,4%')).toBeInTheDocument();
    expect(screen.getByText('+2,1 p.p. vs. período anterior')).toBeInTheDocument();
    expect(screen.getByRole('listitem', { name: /12 de out.*42 tickets/ })).toBeInTheDocument();
    expect(screen.getByText('Sem categoria')).toBeInTheDocument();
    expect(screen.getByText('486')).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: '7 dias' }));
    await waitFor(() => expect(api.find('GET', '/api/v1/metrics')).toHaveLength(2));
  });

  it('handles empty periods and errors', async () => {
    let fail = false;
    mockApi({
      'GET /api/v1/auth/me': { data: admin },
      'GET /api/v1/metrics': () =>
        fail
          ? problem(500, 'x')
          : {
              data: makeMetrics({
                tickets_received: { value: 0, previous: 0 },
                first_response_median_minutes: { value: null, previous: null },
                sla_first_response_percent: { value: null, previous: null },
                by_category: [],
              }),
            },
    });
    const { user } = renderApp('/dashboard');

    expect(await screen.findAllByText('Sem dados do período anterior')).toHaveLength(3);
    expect(screen.getByText('Sem dados')).toBeInTheDocument();

    fail = true;
    await user.click(screen.getByRole('button', { name: '90 dias' }));
    expect(await screen.findByRole('button', { name: 'Tentar de novo' })).toBeInTheDocument();
  });
});
