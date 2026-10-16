import { fireEvent, screen, waitFor } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import { mockApi, problem } from '@/test/api-mock';
import { admin, agent, makeArticle, makeCategory } from '@/test/fixtures';
import { renderApp } from '@/test/render';

const CHUNKS = {
  data: [
    {
      id: 'ch-1',
      ordinal: 3,
      heading: 'Rastreio sem atualização',
      content: 'Abra uma investigação.',
      tokens: 286,
      embedding_model: 'fake',
      citations_count: 211,
    },
  ],
  meta: { embedding_model: 'gemini/gemini-embedding-001', target_tokens: 320, overlap_tokens: 40 },
};

function setup(overrides: Parameters<typeof mockApi>[0] = {}) {
  return mockApi({
    'GET /api/v1/auth/me': { data: agent },
    'GET /api/v1/categories': { data: [makeCategory()] },
    'GET /api/v1/knowledge/articles/:id': { data: makeArticle() },
    'GET /api/v1/knowledge/articles/:id/chunks': CHUNKS,
    ...overrides,
  });
}

describe('ArticleEditorPage', () => {
  it('writes a new article and publishes it', async () => {
    const created = makeArticle({ id: 'kb-new', status: 'draft' });
    const api = setup({
      'POST /api/v1/knowledge/articles': { status: 201, body: { data: created } },
      'POST /api/v1/knowledge/articles/:id/publish': { data: makeArticle({ id: 'kb-new' }) },
    });
    const { user, router } = renderApp('/knowledge/new');

    await user.type(await screen.findByLabelText('Título'), 'Trocas em 30 dias');
    fireEvent.change(screen.getByLabelText('Texto (Markdown)'), { target: { value: '## Prazo\n\nAté 30 dias.' } });
    fireEvent.change(screen.getByLabelText('Visibilidade'), { target: { value: 'internal' } });
    await user.type(screen.getByLabelText('Tags'), 'troca, prazo');
    await user.click(screen.getByRole('button', { name: 'Publicar' }));

    await waitFor(() => expect(api.find('POST', '/api/v1/knowledge/articles/kb-new/publish')).toHaveLength(1));
    expect(api.find('POST', '/api/v1/knowledge/articles')[0]?.body).toEqual({
      title: 'Trocas em 30 dias',
      markdown: '## Prazo\n\nAté 30 dias.',
      visibility: 'internal',
      tags: ['troca', 'prazo'],
      category_id: null,
    });
    await waitFor(() => expect(router.state.location.pathname).toBe('/knowledge/kb-new'));
  });

  it('shows validation errors next to the fields', async () => {
    setup({
      'PUT /api/v1/knowledge/articles/:id': problem(422, 'Invalid', {
        title: ['The title field must be at least 3 characters.'],
      }),
    });
    const { user } = renderApp('/knowledge/kb-1');

    await user.click(await screen.findByRole('button', { name: 'Salvar rascunho' }));

    expect(await screen.findByText('The title field must be at least 3 characters.')).toBeInTheDocument();
  });

  it('previews Markdown safely and shows how the AI sees the article', async () => {
    setup({
      'GET /api/v1/knowledge/articles/:id': {
        data: makeArticle({
          markdown: '## Título\n\n<script>alert(1)</script>\n\n[clique](javascript:alert(1)) ![x](https://evil.test/p.png)',
          has_unpublished_changes: true,
        }),
      },
    });
    const { user } = renderApp('/knowledge/kb-1');

    expect(await screen.findByRole('heading', { name: 'Título' })).toBeInTheDocument();
    expect(document.querySelector('script')).toBeNull();
    expect(document.querySelector('img')).toBeNull();
    expect(screen.getByText('clique').closest('a')?.getAttribute('href') ?? '').not.toContain('javascript');

    await user.click(screen.getByRole('tab', { name: 'Como a IA vê' }));

    expect(await screen.findByText(/~320 tokens cada, 40 de sobreposição/)).toBeInTheDocument();
    expect(screen.getByText('citado 211×')).toBeInTheDocument();
    expect(screen.getByText(/gemini-embedding-001/)).toBeInTheDocument();
    expect(screen.getByText(/Alterações não publicadas ainda não foram reindexadas/)).toBeInTheDocument();
  });

  it('lets admins delete and anyone unpublish', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    const api = setup({
      'GET /api/v1/auth/me': { data: admin },
      'POST /api/v1/knowledge/articles/:id/unpublish': { data: makeArticle({ status: 'archived' }) },
      'DELETE /api/v1/knowledge/articles/:id': null,
      'GET /api/v1/knowledge/articles': {
        data: [],
        links: {},
        meta: { current_page: 1, from: null, last_page: 1, links: [], path: '', per_page: 50, to: null, total: 0 },
      },
      'GET /api/v1/knowledge/stats': { data: { published: 0, chunks: 0, queued: 0, failed: 0, gaps: 0 } },
    });
    const { user, router } = renderApp('/knowledge/kb-1');

    await user.click(await screen.findByRole('button', { name: 'Despublicar' }));
    await waitFor(() => expect(api.find('POST', '/api/v1/knowledge/articles/kb-1/unpublish')).toHaveLength(1));

    await user.click(screen.getByRole('button', { name: 'Excluir' }));
    await waitFor(() => expect(router.state.location.pathname).toBe('/knowledge'));
  });

  it('says when the article does not exist', async () => {
    setup({ 'GET /api/v1/knowledge/articles/:id': problem(404, 'Not Found.') });
    renderApp('/knowledge/missing');

    expect(await screen.findByText('Artigo não encontrado')).toBeInTheDocument();
  });
});
