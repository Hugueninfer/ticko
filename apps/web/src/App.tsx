import { QueryClientProvider, type QueryClient } from '@tanstack/react-query';
import { RouterProvider, type createBrowserRouter } from 'react-router';

import { ToastProvider } from '@/components/layout/Toaster';

export function App({ queryClient, router }: { queryClient: QueryClient; router: ReturnType<typeof createBrowserRouter> }) {
  return (
    <QueryClientProvider client={queryClient}>
      <ToastProvider>
        <RouterProvider router={router} />
      </ToastProvider>
    </QueryClientProvider>
  );
}
