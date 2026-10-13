import type { Role } from '@ticko/api-client';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { Navigate, useLocation } from 'react-router';

import { ErrorState, FullPageSpinner } from '@/components/layout/States';

import { landingPath } from './landing';
import { isStaff, useCurrentUser } from './useAuth';

/**
 * Front-end route protection only improves navigation: the API authorizes
 * every request again, whatever the UI shows.
 */
export function RequireStaff({ children }: { children: ReactNode }) {
  const { t } = useTranslation();
  const location = useLocation();
  const { data: user, isPending, isError, refetch } = useCurrentUser();

  if (isPending) {
    return <FullPageSpinner />;
  }

  if (isError) {
    return <ErrorState onRetry={() => void refetch()} />;
  }

  if (user === null) {
    return <Navigate to="/login" replace state={{ from: location.pathname + location.search }} />;
  }

  if (!isStaff(user.role)) {
    return <ErrorState title={t('auth.staffOnly')} />;
  }

  return children;
}

export function RequireRole({ role, children }: { role: Role; children: ReactNode }) {
  const { t } = useTranslation();
  const { data: user } = useCurrentUser();

  if (user?.role !== role) {
    return <ErrorState title={t('errors.forbidden')} />;
  }

  return children;
}

export function RedirectIfSignedIn({ children }: { children: ReactNode }) {
  const location = useLocation();
  const { data: user, isPending } = useCurrentUser();

  if (isPending) {
    return <FullPageSpinner />;
  }

  if (user && isStaff(user.role)) {
    return <Navigate to={landingPath(user.role, (location.state as { from?: unknown } | null)?.from)} replace />;
  }

  return children;
}
