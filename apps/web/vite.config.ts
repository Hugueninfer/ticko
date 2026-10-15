/// <reference types="vitest/config" />
import path from 'node:path';

import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

// The browser only ever talks to one origin. In development the Vite server
// proxies the API paths to the Docker stack (Caddy), so the session cookie is
// first-party and there is no CORS at all.
const apiTarget = process.env.TICKO_API_URL ?? 'http://localhost:8080';
const proxied = ['/api', '/sanctum', '/up'];

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: { '@': path.resolve(import.meta.dirname, './src') },
  },
  server: {
    port: 5173,
    strictPort: true,
    proxy: {
      ...Object.fromEntries(proxied.map((prefix) => [prefix, { target: apiTarget, changeOrigin: false }])),
      // Reverb WebSocket (realtime), served by Caddy on the same origin.
      '/app': { target: apiTarget, changeOrigin: false, ws: true },
    },
  },
  preview: {
    port: 4173,
    proxy: Object.fromEntries(proxied.map((prefix) => [prefix, { target: apiTarget, changeOrigin: false }])),
  },
  build: {
    sourcemap: false,
    // Keep every asset as a file: the production CSP allows no inline data.
    assetsInlineLimit: 0,
  },
  test: {
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.ts'],
    include: ['src/**/*.test.{ts,tsx}'],
    css: false,
    coverage: {
      provider: 'v8',
      include: ['src/**/*.{ts,tsx}'],
      exclude: ['src/**/*.test.{ts,tsx}', 'src/test/**', 'src/main.tsx', 'src/components/ui/**', 'src/vite-env.d.ts'],
      thresholds: { lines: 80, functions: 80, branches: 75, statements: 80 },
    },
  },
});
