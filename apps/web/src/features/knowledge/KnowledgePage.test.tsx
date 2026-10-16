import { fireEvent, screen, waitFor } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { mockApi } from '@/test/api-mock';
import { admin, agent, makeArticle, makeCategory, page } from '@/test/fixtures';
import { renderApp } from '@/test/render';

const STATS = { data: { published: 42, chunks: 386, queued: 2, failed: 1, gaps: 3 } };

function setup(overrides: Parameters<typeof mockApi>[0] = {}) {
  return mockApi({
    'GET /api/v1/auth/me': { data: agent },
    'GET /api/v1/categories': { data: [makeCategory()] },
    'GET /api/v1/knowledge/stats': STATS,
    'GET /api/v1/knowledge/articles': page([
      makeArticle(),
      makeArticle({
        id: 'kb-2',
        title: 'Recuperar acesso à conta',
        index: { status: 'failed', progress: 0, error: 'LLM_TIMEOUT', indexed_at: null, chunks_count: 0 },
      }),
      makeArticle({
        id: 'kb-3',
        title: 'Trocas',
        index: { status: 'indexing', progress: 62, error: null, indexed_at: null, chunks_count: 0 },
      }),
      makeArticle({
        id: 'kb-4',
        title: 'Cupons vigentes',
        status: 'draft',
        index: { status: 'none', progress: 0, error: null, indexed_at: null, chunks_count: 0 },
      }),
    ]),
    ...overrides,
  });
}

describe('KnowledgePage', () => {
  it('shows the figures and every article with its publication and indexing state', async () => {
    setup();
    renderApp('/knowledge');

    expect(await screen.findByRole('heading', { name: 'Base de conhecimento' })).toBeInTheDocument();
    expect(await screen.findByText('386')).toBeInTheDocument();
    expect(await screen.findByRole('link', { name: 'Política de entregas e prazos' })).toHaveAttribute('href', '/knowledge/kb-1');
    expect(screen.getByText('Indexado · 14 trechos')).toBeInTheDocument();
    expect(screen.getByText('Erro ao indexar', { selector: 'span' })).toBeInTheDocument();
    expect(screen.getByText('Gerando embeddings · 62%')).toBeInTheDocument();
    expect(screen.getByText('Rascunho', { selector: 'span' })).toBeInTheDocument();
    expect(screen.getAllByText('312×')).toHaveLength(3);
  });

  it('filters and retries failed indexing', async () => {
    const api = setup({ 'POST /api/v1/knowledge/articles/:id/reindex': { data: makeArticle({ id: 'kb-2' }) } });
    const { user } = renderApp('/knowledge');

    await screen.findByText('Erro ao indexar', { selector: 'span' });
    fireEvent.change(screen.getByRole('combobox', { name: 'Publicação' }), { target: { value: 'draft' } });
    await waitFor(() => expect(api.find('GET', '/api/v1/knowledge/articles').at(-1)?.query.get('status')).toBe('draft'));

    await user.click(screen.getByRole('button', { name: 'Tentar de novo' }));
    await waitFor(() => expect(api.find('POST', '/api/v1/knowledge/articles/kb-2/reindex')).toHaveLength(1));
  });

  it('imports a Markdown file and opens it', async () => {
    const api = setup({
      'POST /api/v1/knowledge/articles/import': { status: 201, body: { data: makeArticle({ id: 'kb-9', status: 'draft' }) } },
      'GET /api/v1/knowledge/articles/:id': { data: makeArticle({ id: 'kb-9', status: 'draft' }) },
    });
    const { router } = renderApp('/knowledge');

    const input = await screen.findByLabelText('Importar Markdown', { selector: 'input' });
    fireEvent.change(input, { target: { files: [new File(['# Título\n\ntexto'], 'artigo.md', { type: 'text/markdown' })] } });

    await waitFor(() => expect(router.state.location.pathname).toBe('/knowledge/kb-9'));
    expect(api.find('POST', '/api/v1/knowledge/articles/import')).toHaveLength(1);
  });

  it('shows the empty state', async () => {
    setup({ 'GET /api/v1/auth/me': { data: admin }, 'GET /api/v1/knowledge/articles': page([]) });
    renderApp('/knowledge');

    expect(await screen.findByText('Nenhum artigo ainda')).toBeInTheDocument();
  });
});
