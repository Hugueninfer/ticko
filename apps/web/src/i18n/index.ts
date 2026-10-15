import i18next from 'i18next';
import { initReactI18next } from 'react-i18next';

import { en } from './locales/en';
import { ptBR, type Messages } from './locales/pt-BR';

export const LANGUAGES = ['pt-BR', 'en'] as const;
export type Language = (typeof LANGUAGES)[number];

const STORAGE_KEY = 'ticko.language';

declare module 'i18next' {
  interface CustomTypeOptions {
    defaultNS: 'translation';
    resources: { translation: Messages };
  }
}

/** Saved choice, then the browser language, then Portuguese. */
export function detectLanguage(): Language {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);

    if (saved === 'pt-BR' || saved === 'en') {
      return saved;
    }
  } catch {
    // Storage may be unavailable (private mode); fall through.
  }

  return navigator.language.toLowerCase().startsWith('en') ? 'en' : 'pt-BR';
}

export function setLanguage(language: Language): void {
  try {
    localStorage.setItem(STORAGE_KEY, language);
  } catch {
    // Not persisted; the change still applies to this tab.
  }

  void i18next.changeLanguage(language);
}

export function initI18n(language: Language = detectLanguage()) {
  if (!i18next.isInitialized) {
    void i18next.use(initReactI18next).init({
      lng: language,
      fallbackLng: 'pt-BR',
      supportedLngs: [...LANGUAGES],
      resources: { 'pt-BR': { translation: ptBR }, en: { translation: en } },
      // React already escapes text; nothing here renders raw HTML.
      interpolation: { escapeValue: false },
      returnNull: false,
    });

    i18next.on('languageChanged', (lng) => {
      document.documentElement.lang = lng;
    });
    document.documentElement.lang = language;
  }

  return i18next;
}
