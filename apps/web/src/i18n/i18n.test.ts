import { describe, expect, it } from 'vitest';

import { en } from './locales/en';
import { ptBR } from './locales/pt-BR';

import { detectLanguage, setLanguage } from '.';

function keys(value: object, prefix = ''): string[] {
  return Object.entries(value).flatMap(([key, child]) =>
    typeof child === 'object' && child !== null ? keys(child as object, `${prefix}${key}.`) : [`${prefix}${key}`],
  );
}

describe('i18n', () => {
  it('has the same keys in every language', () => {
    expect(keys(en).sort()).toEqual(keys(ptBR).sort());
  });

  it('remembers the chosen language and defaults to Portuguese', () => {
    localStorage.removeItem('ticko.language');
    Object.defineProperty(navigator, 'language', { value: 'fr-FR', configurable: true });
    expect(detectLanguage()).toBe('pt-BR');

    setLanguage('en');
    expect(detectLanguage()).toBe('en');
    expect(document.documentElement.lang).toBe('en');

    setLanguage('pt-BR');
  });
});
