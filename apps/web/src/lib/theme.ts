export type Theme = 'light' | 'dark';

const STORAGE_KEY = 'ticko.theme';

export function preferredTheme(): Theme {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);

    if (saved === 'light' || saved === 'dark') {
      return saved;
    }
  } catch {
    // Ignore unavailable storage.
  }

  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

/** Applied from JS (no inline script), so the strict CSP stays intact. */
export function applyTheme(theme: Theme): void {
  document.documentElement.classList.toggle('dark', theme === 'dark');

  try {
    localStorage.setItem(STORAGE_KEY, theme);
  } catch {
    // Not persisted.
  }
}
