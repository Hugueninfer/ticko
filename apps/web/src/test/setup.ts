import '@testing-library/jest-dom/vitest';

import { cleanup } from '@testing-library/react';
import { afterEach, beforeAll, vi } from 'vitest';

import { initI18n } from '@/i18n';

beforeAll(() => {
  initI18n('pt-BR');
});

afterEach(() => {
  cleanup();
  vi.unstubAllGlobals();
  document.cookie.split(';').forEach((cookie) => {
    document.cookie = `${cookie.split('=')[0]?.trim() ?? ''}=; expires=Thu, 01 Jan 1970 00:00:00 GMT`;
  });
});

// jsdom lacks matchMedia, used by the theme toggle.
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: (query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addEventListener: () => undefined,
    removeEventListener: () => undefined,
    addListener: () => undefined,
    removeListener: () => undefined,
    dispatchEvent: () => false,
  }),
});
