import { fireEvent, screen, waitFor, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { mockApi } from '@/test/api-mock';
import { agent, makeSuggestion, makeTicket, page } from '@/test/fixtures';
import { renderApp } from '@/test/render';

import { splitMarkers, withoutMarkers } from './api';

const ticket = makeTicket();
const url = `/tickets/${ticket.id}`;

function setup(suggestion: unknown, overrides: Parameters<typeof mockApi>[0] = {}) {
  return mockApi({
    'GET /api/v1/auth/me': { data: agent },
    'GET /api/v1/tickets/:id': { data: ticket },
    'GET /api/v1/tickets/:id/messages': { data: [] },
    'GET /api/v1/tickets/:id/attachments': { data: [] },
    'GET /api/v1/tickets': page([ticket]),
    'GET /api/v1/team': page([]),
    'GET /api/v1/tickets/:id/suggestions/latest': { data: suggestion },
    ...overrides,
  });
}

async function findPanel() {
  return within(await screen.findByRole('complementary', { name: 'Sugestão da IA' }));
}

describe('AI suggestion panel', () => {
  it('asks for a draft and shows it while it is written', async () => {
    const pending = makeSuggestion({ status: 'pending', body: null, confidence: null, citations: [] });
    let current: unknown = null;
    const api = setup(null, {
      'GET /api/v1/tickets/:id/suggestions/latest': () => ({ data: current }),
      'POST /api/v1/tickets/:id/suggestions': () => {
        current = pending;

        return { status: 202, body: { data: pending } };
      },
    });
    const { user } = renderApp(url);

    await user.click(await screen.findByRole('button', { name: 'Gerar sugestão' }));

    expect(await screen.findByText('Escrevendo resposta…')).toBeInTheDocument();
    expect(api.find('POST', `/api/v1/tickets/${ticket.id}/suggestions`)).toHaveLength(1);
  });

  it('shows the cited draft and sends it without source markers', async () => {
    const approved = makeSuggestion({
      status: 'approved',
      reviewed_by: { id: agent.id, name: agent.name, role: 'agent', email: null },
      reviewed_at: '2026-10-12T14:10:00+00:00',
    });
    let current = makeSuggestion();
    const api = setup(null, {
      'GET /api/v1/tickets/:id/suggestions/latest': () => ({ data: current }),
      'POST /api/v1/suggestions/:id/approve': () => {
        current = approved;

        return { data: approved };
      },
    });
    const { user } = renderApp(url);
    const ai = await findPanel();

    expect(await ai.findByRole('meter', { name: 'Confiança' })).toHaveAttribute('aria-valuenow', '0.86');
    expect(ai.getByText('Alta ·', { exact: false })).toBeInTheDocument();

    // The source chip reveals the quoted passage and a link to the article.
    await user.click(ai.getByRole('button', { name: 'Fonte 1' }));
    expect(ai.getByText('“O prazo de resposta é de até 2 dias úteis.”')).toBeInTheDocument();
    expect(ai.getByRole('link', { name: /Abrir artigo/ })).toHaveAttribute('href', '/knowledge/kb-1');

    await user.click(ai.getByRole('button', { name: 'Aprovar e enviar' }));

    await waitFor(() =>
      expect(api.find('POST', '/api/v1/suggestions/sg-1/approve')[0]?.body).toEqual({
        body: 'Oi, Mariana! Abrimos uma investigação com a transportadora.',
      }),
    );
    expect(await ai.findByText('Enviada sem edições')).toBeInTheDocument();
    expect(ai.getByText(`por ${agent.name} às`, { exact: false })).toBeInTheDocument();
  });

  it('lets the agent edit the draft before sending', async () => {
    let current = makeSuggestion();
    const api = setup(null, {
      'GET /api/v1/tickets/:id/suggestions/latest': () => ({ data: current }),
      'POST /api/v1/suggestions/:id/approve': () => {
        current = makeSuggestion({ status: 'approved', edited: true });

        return { data: current };
      },
    });
    const { user } = renderApp(url);
    const ai = await findPanel();

    await user.click(await ai.findByRole('button', { name: 'Editar' }));
    const box = ai.getByLabelText('Editando o rascunho');
    expect(box).toHaveValue('Oi, Mariana! Abrimos uma investigação com a transportadora.');
    fireEvent.change(box, { target: { value: 'Oi! Já abrimos a investigação.' } });
    await user.click(ai.getByRole('button', { name: 'Aprovar e enviar' }));

    await waitFor(() =>
      expect(api.find('POST', '/api/v1/suggestions/sg-1/approve')[0]?.body).toEqual({ body: 'Oi! Já abrimos a investigação.' }),
    );
    expect(await ai.findByText('Enviada com edições')).toBeInTheDocument();
  });

  it('discards with a reason', async () => {
    const api = setup(makeSuggestion(), {
      'POST /api/v1/suggestions/:id/discard': { data: makeSuggestion({ status: 'discarded', discard_reason: 'tone' }) },
    });
    const { user } = renderApp(url);
    const ai = await findPanel();

    await user.click(await ai.findByRole('button', { name: 'Descartar' }));
    await user.click(ai.getByRole('button', { name: 'Tom inadequado' }));

    await waitFor(() => expect(api.find('POST', '/api/v1/suggestions/sg-1/discard')[0]?.body).toEqual({ reason: 'tone' }));
    expect(await ai.findByText('Descartada: Tom inadequado')).toBeInTheDocument();
  });

  it('says it does not know, lists the closest articles and reports gaps', async () => {
    const api = setup(makeSuggestion({ status: 'insufficient', body: null, confidence: 0.31, citations: [] }), {
      'POST /api/v1/suggestions/:id/gap': {
        data: makeSuggestion({
          status: 'insufficient',
          body: null,
          confidence: 0.31,
          citations: [],
          gap_reported_at: '2026-10-12T14:10:00+00:00',
        }),
      },
    });
    const { user } = renderApp(url);
    const ai = await findPanel();

    expect(await ai.findByText('Não encontrei base suficiente para responder')).toBeInTheDocument();
    expect(ai.getByText('Baixa ·', { exact: false })).toBeInTheDocument();
    expect(ai.getByRole('link', { name: /Política de entregas e prazos/ })).toBeInTheDocument();
    expect(ai.queryByRole('button', { name: 'Aprovar e enviar' })).toBeNull();

    await user.click(ai.getByRole('button', { name: 'Escrever resposta' }));
    expect(document.activeElement).toBe(document.querySelector('[data-composer] textarea'));

    await user.click(ai.getByRole('button', { name: 'Sinalizar lacuna na base' }));
    expect(await ai.findByRole('button', { name: 'Lacuna sinalizada' })).toBeDisabled();
    expect(api.find('POST', '/api/v1/suggestions/sg-1/gap')).toHaveLength(1);
  });

  it('reports provider failures and retries', async () => {
    let current = makeSuggestion({ status: 'failed', body: null, citations: [], error_code: 'LLM_TIMEOUT' });
    const api = setup(null, {
      'GET /api/v1/tickets/:id/suggestions/latest': () => ({ data: current }),
      'POST /api/v1/tickets/:id/suggestions': () => {
        current = makeSuggestion({ status: 'pending', body: null });

        return { status: 202, body: { data: current } };
      },
    });
    const { user } = renderApp(url);
    const ai = await findPanel();

    expect(await ai.findByRole('alert')).toHaveTextContent('Não consegui gerar a sugestão');
    expect(ai.getByText(/LLM_TIMEOUT/)).toBeInTheDocument();

    await user.click(ai.getByRole('button', { name: 'Tentar de novo' }));

    await waitFor(() => expect(api.find('POST', `/api/v1/tickets/${ticket.id}/suggestions`)).toHaveLength(1));
  });

  it('turns drafts into plain text and chips', () => {
    expect(withoutMarkers('Prazo de 2 dias [1][2].  Ok [3]')).toBe('Prazo de 2 dias. Ok');
    expect(splitMarkers('A [1] b')).toEqual([
      { kind: 'text', value: 'A ' },
      { kind: 'cite', n: 1 },
      { kind: 'text', value: ' b' },
    ]);
  });
});
