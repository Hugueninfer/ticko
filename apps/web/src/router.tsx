import { createBrowserRouter, Navigate, type RouteObject } from 'react-router';

import { RedirectIfSignedIn, RequireRole, RequireStaff } from '@/auth/guards';
import { AppShell } from '@/components/layout/AppShell';
import { NotFoundPage } from '@/components/layout/NotFoundPage';
import { LoginPage } from '@/features/auth/LoginPage';
import { CategoriesPage } from '@/features/categories/CategoriesPage';
import { DashboardPage } from '@/features/dashboard/DashboardPage';
import { TeamPage } from '@/features/team/TeamPage';
import { QueuePage } from '@/features/tickets/QueuePage';
import { TicketDetailPage } from '@/features/tickets/TicketDetailPage';

export const routes: RouteObject[] = [
  {
    path: '/login',
    element: (
      <RedirectIfSignedIn>
        <LoginPage />
      </RedirectIfSignedIn>
    ),
  },
  {
    element: (
      <RequireStaff>
        <AppShell />
      </RequireStaff>
    ),
    children: [
      { index: true, element: <Navigate to="/tickets" replace /> },
      { path: 'tickets', element: <QueuePage /> },
      { path: 'tickets/:id', element: <TicketDetailPage /> },
      {
        path: 'dashboard',
        element: (
          <RequireRole role="admin">
            <DashboardPage />
          </RequireRole>
        ),
      },
      {
        path: 'team',
        element: (
          <RequireRole role="admin">
            <TeamPage />
          </RequireRole>
        ),
      },
      {
        path: 'categories',
        element: (
          <RequireRole role="admin">
            <CategoriesPage />
          </RequireRole>
        ),
      },
      { path: '*', element: <NotFoundPage /> },
    ],
  },
];

export function createRouter() {
  return createBrowserRouter(routes);
}
