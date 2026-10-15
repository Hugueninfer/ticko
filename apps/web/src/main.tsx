import '@fontsource-variable/geist';
import '@fontsource-variable/geist-mono';
import './styles.css';

import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';

import { App } from './App';
import { initI18n } from './i18n';
import { createQueryClient } from './lib/query';
import { applyTheme, preferredTheme } from './lib/theme';
import { createRouter } from './router';

applyTheme(preferredTheme());
initI18n();

const root = document.getElementById('root');

if (root) {
  createRoot(root).render(
    <StrictMode>
      <App queryClient={createQueryClient()} router={createRouter()} />
    </StrictMode>,
  );
}
