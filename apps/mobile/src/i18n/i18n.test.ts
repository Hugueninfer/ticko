import { getLocales } from 'expo-localization';
import i18next from 'i18next';

import { currentLanguage, deviceLanguage, isLanguage, savedLanguage, setLanguage } from '.';
import { en } from './locales/en';
import { ptBR } from './locales/pt-BR';

function keys(value: object, prefix = ''): string[] {
  return Object.entries(value).flatMap(([key, child]) =>
    typeof child === 'object' && child !== null ? keys(child as object, `${prefix}${key}.`) : [`${prefix}${key}`],
  );
}

describe('i18n', () => {
  afterEach(() => i18next.changeLanguage('pt-BR'));

  it('has the same keys in both languages', () => {
    expect(keys(en).sort()).toEqual(keys(ptBR).sort());
  });

  it('follows the phone language, defaulting to Portuguese', () => {
    jest.mocked(getLocales).mockReturnValueOnce([{ languageTag: 'en-US' }] as never);
    expect(deviceLanguage()).toBe('en');

    jest.mocked(getLocales).mockReturnValueOnce([{ languageTag: 'es-ES' }] as never);
    expect(deviceLanguage()).toBe('pt-BR');
  });

  it('saves the chosen language', async () => {
    await expect(savedLanguage()).resolves.toBeNull();

    await setLanguage('en');

    expect(currentLanguage()).toBe('en');
    expect(i18next.t('auth.submit')).toBe('Sign in');
    await expect(savedLanguage()).resolves.toBe('en');
    expect(isLanguage('fr')).toBe(false);
  });
});
