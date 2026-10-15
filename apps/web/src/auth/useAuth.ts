import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ApiError, unwrap, type CurrentUser, type Role } from '@ticko/api-client';

import { api, ensureCsrfCookie } from '@/lib/api';
import { queryKeys, settleQueries } from '@/lib/query';
import { resumeRealtime, suspendRealtime } from '@/lib/realtime';

/** The signed-in user, or null for guests. Other errors propagate. */
export function useCurrentUser() {
  return useQuery({
    queryKey: queryKeys.me,
    queryFn: async (): Promise<CurrentUser | null> => {
      try {
        return unwrap(await api.GET('/api/v1/auth/me')).data;
      } catch (error) {
        if (error instanceof ApiError && error.status === 401) {
          return null;
        }

        throw error;
      }
    },
    staleTime: 60_000,
  });
}

export function isStaff(role: Role | undefined): boolean {
  return role === 'agent' || role === 'admin';
}

export interface LoginInput {
  tenant: string;
  email: string;
  password: string;
}

export function useLogin() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ tenant, email, password }: LoginInput) => {
      await ensureCsrfCookie();

      return unwrap(
        await api.POST('/api/v1/auth/login', {
          body: { email, password },
          headers: { 'X-Tenant': tenant },
        }),
      ).data;
    },
    onSuccess: (user) => {
      queryClient.setQueryData(queryKeys.me, user);
    },
  });
}

export function useDemoAvailability() {
  return useQuery({
    queryKey: queryKeys.demo,
    queryFn: async () => {
      const result = await api.GET('/api/v1/auth/demo');

      // 404 simply means "no demo here".
      return result.response.ok ? unwrap(result).data : null;
    },
    staleTime: Infinity,
    retry: false,
  });
}

export function useDemoLogin() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (role: 'agent' | 'admin') => {
      await ensureCsrfCookie();

      return unwrap(await api.POST('/api/v1/auth/demo', { body: { role } })).data;
    },
    onSuccess: (user) => {
      queryClient.setQueryData(queryKeys.me, user);
    },
  });
}

export function useLogout() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async () => {
      // A request still running with the current session would restore it
      // after the logout (it saves the session and resends its cookie), so
      // stop realtime first, then wait for every request in flight.
      await suspendRealtime();
      await settleQueries(queryClient);
      unwrap(await api.POST('/api/v1/auth/logout'));
    },
    onSettled: () => {
      resumeRealtime();
      // Drop every cached response of the previous user.
      queryClient.clear();
      queryClient.setQueryData(queryKeys.me, null);
    },
  });
}
