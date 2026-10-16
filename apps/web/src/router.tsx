import { lazy, Suspense, type ComponentType } from 'react';
import { createBrowserRouter, Navigate, type RouteObject } from 'react-router';

import { RedirectIfSignedIn, RequireRole, RequireStaff } from '@/auth/guards';
import { AppShell } from '@/components/layout/AppShell';
import { NotFoundPage } from '@/components/layout/NotFoundPage';
import { FullPageSpinner } from '@/components/layout/States';
import { LoginPage } from '@/features/auth/LoginPage';
import { QueuePage } from '@/features/tickets/QueuePage';
import { TicketDetailPage } from '@/features/tickets/TicketDetailPage';

// Admin screens are loaded on demand, outside the agents' main bundle.
const DashboardPage = lazy(() => import('@/features/dashboard/DashboardPage').then((m) => ({ default: m.DashboardPage })));
const TeamPage = lazy(() => import('@/features/team/TeamPage').then((m) => ({ default: m.TeamPage })));
const KnowledgePage = lazy(() => import('@/features/knowledge/KnowledgePage').then((m) => ({ default: m.KnowledgePage })));
const ArticleEditorPage = lazy(() =>
  import('@/features/knowledge/ArticleEditorPage').then((m) => ({ default: m.ArticleEditorPage })),
);
const PlaygroundPage = lazy(() => import('@/features/knowledge/PlaygroundPage').then((m) => ({ default: m.PlaygroundPage })));
const CategoriesPage = lazy(() => import('@/features/categories/CategoriesPage').then((m) => ({ default: m.CategoriesPage })));

function lazyPage(Page: ComponentType) {
  return (
    <Suspense fallback={<FullPageSpinner />}>
      <Page />
    </Suspense>
  );
}

function adminPage(Page: ComponentType) {
  return (
    <RequireRole role="admin">
      <Suspense fallback={<FullPageSpinner />}>
        <Page />
      </Suspense>
    </RequireRole>
  );
}

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
      { path: 'knowledge', element: lazyPage(KnowledgePage) },
      { path: 'knowledge/playground', element: lazyPage(PlaygroundPage) },
      { path: 'knowledge/:id', element: lazyPage(ArticleEditorPage) },
      { path: 'dashboard', element: adminPage(DashboardPage) },
      { path: 'team', element: adminPage(TeamPage) },
      { path: 'categories', element: adminPage(CategoriesPage) },
      { path: '*', element: <NotFoundPage /> },
    ],
  },
];

export function createRouter() {
  return createBrowserRouter(routes);
}
