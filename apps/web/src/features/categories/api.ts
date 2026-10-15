import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { unwrap, type CategoryInput } from '@ticko/api-client';

import { api } from '@/lib/api';
import { queryKeys } from '@/lib/query';

export function useCategories() {
  return useQuery({
    queryKey: queryKeys.categories,
    queryFn: async () => unwrap(await api.GET('/api/v1/categories')).data,
    staleTime: 5 * 60_000,
  });
}

export function useSaveCategory() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, input }: { id: string | null; input: CategoryInput }) =>
      id === null
        ? unwrap(await api.POST('/api/v1/categories', { body: input })).data
        : unwrap(await api.PUT('/api/v1/categories/{id}', { params: { path: { id } }, body: input })).data,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKeys.categories }),
  });
}

export function useDeleteCategory() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: string) => {
      unwrap(await api.DELETE('/api/v1/categories/{id}', { params: { path: { id } } }));
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKeys.categories }),
  });
}
