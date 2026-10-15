import { getLocales } from 'expo-localization';
import * as SecureStore from 'expo-secure-store';
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

export function isLanguage(value: unknown): value is Language {
  return value === 'pt-BR' || value === 'en';
}

/** The phone's language when it is English, otherwise Portuguese. */
export function deviceLanguage(): Language {
  const tag = getLocales().at(0)?.languageTag.toLowerCase() ?? '';

  return tag.startsWith('en') ? 'en' : 'pt-BR';
}

export async function savedLanguage(): Promise<Language | null> {
  try {
    const value = await SecureStore.getItemAsync(STORAGE_KEY);

    return isLanguage(value) ? value : null;
  } catch {
    return null;
  }
}

export async function setLanguage(language: Language): Promise<void> {
  await i18next.changeLanguage(language);

  try {
    await SecureStore.setItemAsync(STORAGE_KEY, language);
  } catch {
    // Not persisted; the change still applies until the app restarts.
  }
}

export function currentLanguage(): Language {
  return isLanguage(i18next.language) ? i18next.language : 'pt-BR';
}

export function initI18n(language: Language = deviceLanguage()) {
  if (!i18next.isInitialized) {
    void i18next.use(initReactI18next).init({
      lng: language,
      fallbackLng: 'pt-BR',
      supportedLngs: [...LANGUAGES],
      resources: { 'pt-BR': { translation: ptBR }, en: { translation: en } },
      // React Native renders text, never HTML.
      interpolation: { escapeValue: false },
      returnNull: false,
    });
  }

  return i18next;
}
