import { fireEvent, screen, waitFor } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { mockApi, problem } from '@/test/api-mock';
import { agent, makeCategory } from '@/test/fixtures';
import { renderApp } from '@/test/render';

const HIT = {
  chunk_id: 'ch-3',
  article_id: 'kb-1',
  title: 'Política de entregas e prazos',
  ordinal: 3,
  heading: 'Rastreio sem atualização',
  content: 'Se o rastreamento não for atualizado por mais de 5 dias úteis, abra uma investigação.',
  text_score: 0.62,
  semantic_score: 0.89,
  score: 0.91,
  movement: 1,
};

function setup(overrides: Parameters<typeof mockApi>[0] = {}) {
  return mockApi({
    'GET /api/v1/auth/me': { data: agent },
    'GET /api/v1/categories': { data: [makeCategory()] },
    'POST /api/v1/knowledge/search': { data: [HIT], meta: { latency_ms: 212, embedding_model: 'fake' } },
    ...overrides,
  });
}

describe('PlaygroundPage', () => {
  it('searches with the chosen parameters and shows every score', async () => {
    const api = setup();
    const { user } = renderApp('/knowledge/playground');

    await user.type(await screen.findByLabelText('Escreva como um cliente perguntaria…'), 'o rastreio parou');
    fireEvent.change(screen.getByLabelText('Peso texto × semântica'), { target: { value: '0.7' } });
    fireEvent.change(screen.getByLabelText('Resultados (top-k)'), { target: { value: '3' } });
    await user.click(screen.getByLabelText('Reranking (fusão por ranking)'));
    await user.click(screen.getByRole('button', { name: 'Buscar' }));

    expect(await screen.findByRole('link', { name: 'Política de entregas e prazos' })).toHaveAttribute('href', '/knowledge/kb-1');
    expect(screen.getByText('0,91')).toBeInTheDocument();
    expect(screen.getByText('subiu 1')).toBeInTheDocument();
    expect(screen.getByText(/Latência: 212 ms/)).toBeInTheDocument();
    await waitFor(() =>
      expect(api.find('POST', '/api/v1/knowledge/search')[0]?.body).toEqual({
        query: 'o rastreio parou',
        text_weight: 0.7,
        top_k: 3,
        rerank: true,
        category_id: null,
        customer_visible_only: true,
      }),
    );
  });

  it('explains when search is unavailable or finds nothing', async () => {
    setup({ 'POST /api/v1/knowledge/search': problem(503, 'Service Unavailable') });
    const { user } = renderApp('/knowledge/playground');

    await user.type(await screen.findByLabelText('Escreva como um cliente perguntaria…'), 'prazo');
    await user.click(screen.getByRole('button', { name: 'Buscar' }));
    expect(await screen.findByRole('alert')).toHaveTextContent('A busca não está disponível agora');
  });

  it('shows the empty state', async () => {
    setup({ 'POST /api/v1/knowledge/search': { data: [], meta: { latency_ms: 5, embedding_model: 'fake' } } });
    const { user } = renderApp('/knowledge/playground');

    await user.type(await screen.findByLabelText('Escreva como um cliente perguntaria…'), 'xyz');
    await user.click(screen.getByRole('button', { name: 'Buscar' }));
    expect(await screen.findByText('Nenhum trecho encontrado')).toBeInTheDocument();
  });
});
