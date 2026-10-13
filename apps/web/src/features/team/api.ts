import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { unwrap, type Role, type TeamMemberInput } from '@ticko/api-client';

import { api } from '@/lib/api';
import { queryKeys } from '@/lib/query';

export function useTeam(filters: { q?: string; role?: Role; page?: number } = {}) {
  return useQuery({
    queryKey: queryKeys.team(filters),
    queryFn: async () => unwrap(await api.GET('/api/v1/team', { params: { query: filters } })),
    placeholderData: keepPreviousData,
  });
}

/** Agents and admins, e.g. to pick an assignee. */
export function useStaff() {
  return useQuery({
    queryKey: queryKeys.team({ staff: true }),
    queryFn: async () => {
      const [agents, admins] = await Promise.all([
        api.GET('/api/v1/team', { params: { query: { role: 'agent' } } }),
        api.GET('/api/v1/team', { params: { query: { role: 'admin' } } }),
      ]);

      return [...unwrap(agents).data, ...unwrap(admins).data]
        .filter((member) => member.is_active)
        .sort((a, b) => a.name.localeCompare(b.name));
    },
    staleTime: 5 * 60_000,
  });
}

export function useCreateMember() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: TeamMemberInput) => unwrap(await api.POST('/api/v1/team', { body: input })).data,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['team'] }),
  });
}

export function useUpdateMember() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, changes }: { id: string; changes: { role?: Role; is_active?: boolean; name?: string } }) =>
      unwrap(await api.PATCH('/api/v1/team/{member_id}', { params: { path: { member_id: id } }, body: changes })).data,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['team'] }),
  });
}
