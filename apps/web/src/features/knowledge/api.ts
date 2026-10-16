import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { unwrap, type KbArticleFilters, type KbArticleInput, type KnowledgeSearchInput } from '@ticko/api-client';

import { api } from '@/lib/api';
import { queryKeys } from '@/lib/query';

export function useArticles(filters: KbArticleFilters) {
  return useQuery({
    queryKey: queryKeys.articles(filters),
    queryFn: async () => unwrap(await api.GET('/api/v1/knowledge/articles', { params: { query: filters } })),
    placeholderData: (previous) => previous,
  });
}

export function useKnowledgeStats() {
  return useQuery({
    queryKey: queryKeys.knowledgeStats,
    queryFn: async () => unwrap(await api.GET('/api/v1/knowledge/stats')).data,
  });
}

export function useArticle(id: string | null) {
  return useQuery({
    queryKey: queryKeys.article(id ?? 'new'),
    queryFn: async () => unwrap(await api.GET('/api/v1/knowledge/articles/{id}', { params: { path: { id: id ?? '' } } })).data,
    enabled: id !== null,
  });
}

export function useChunks(id: string | null, enabled: boolean) {
  return useQuery({
    queryKey: queryKeys.chunks(id ?? 'new'),
    queryFn: async () =>
      unwrap(await api.GET('/api/v1/knowledge/articles/{article_id}/chunks', { params: { path: { article_id: id ?? '' } } })),
    enabled: enabled && id !== null,
  });
}

function useRefreshKnowledge() {
  const queryClient = useQueryClient();

  return () => queryClient.invalidateQueries({ queryKey: queryKeys.knowledge });
}

export function useSaveArticle() {
  const refresh = useRefreshKnowledge();

  return useMutation({
    mutationFn: async ({ id, input }: { id: string | null; input: KbArticleInput }) =>
      id === null
        ? unwrap(await api.POST('/api/v1/knowledge/articles', { body: input })).data
        : unwrap(await api.PUT('/api/v1/knowledge/articles/{id}', { params: { path: { id } }, body: input })).data,
    onSuccess: refresh,
  });
}

type ArticleAction = 'publish' | 'unpublish' | 'reindex';

export function useArticleAction() {
  const refresh = useRefreshKnowledge();

  return useMutation({
    mutationFn: async ({ id, action }: { id: string; action: ArticleAction }) => {
      const params = { params: { path: { article_id: id } } };

      switch (action) {
        case 'publish':
          return unwrap(await api.POST('/api/v1/knowledge/articles/{article_id}/publish', params)).data;
        case 'unpublish':
          return unwrap(await api.POST('/api/v1/knowledge/articles/{article_id}/unpublish', params)).data;
        case 'reindex':
          return unwrap(await api.POST('/api/v1/knowledge/articles/{article_id}/reindex', params)).data;
      }
    },
    onSuccess: refresh,
  });
}

export function useDeleteArticle() {
  const refresh = useRefreshKnowledge();

  return useMutation({
    mutationFn: async (id: string) => {
      unwrap(await api.DELETE('/api/v1/knowledge/articles/{id}', { params: { path: { id } } }));
    },
    onSuccess: refresh,
  });
}

export function useImportArticle() {
  const refresh = useRefreshKnowledge();

  return useMutation({
    mutationFn: async (file: File) => {
      const form = new FormData();
      form.append('file', file);

      return unwrap(
        await api.POST('/api/v1/knowledge/articles/import', {
          // The typed body is the multipart form itself.
          body: form as unknown as { file: string },
          bodySerializer: (body) => body as unknown as FormData,
        }),
      ).data;
    },
    onSuccess: refresh,
  });
}

export function useKnowledgeSearch() {
  return useMutation({
    mutationFn: async (input: KnowledgeSearchInput) => unwrap(await api.POST('/api/v1/knowledge/search', { body: input })),
  });
}
