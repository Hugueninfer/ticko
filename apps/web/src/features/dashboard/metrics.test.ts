import { describe, expect, it } from 'vitest';

import { landingPath } from '@/auth/landing';

import { delta, periodFor } from './metrics';

describe('dashboard helpers', () => {
  it('builds inclusive periods ending today', () => {
    expect(periodFor('7', new Date(2026, 9, 12))).toEqual({ from: '2026-10-06', to: '2026-10-12' });
    expect(periodFor('30', new Date(2026, 9, 12))).toEqual({ from: '2026-09-13', to: '2026-10-12' });
  });

  it('tells whether a change is good news', () => {
    expect(delta(14, 20, true)).toEqual({ diff: -6, good: true });
    expect(delta(93.4, 91.3)?.good).toBe(true);
    expect(delta(5, 5)).toEqual({ diff: 0, good: null });
    expect(delta(null, 5)).toBeNull();
  });
});

describe('landingPath', () => {
  it('returns to the requested page or the role home', () => {
    expect(landingPath('agent', '/tickets/1')).toBe('/tickets/1');
    expect(landingPath('admin', undefined)).toBe('/dashboard');
    expect(landingPath('agent', '//evil.test')).toBe('/tickets');
    expect(landingPath('agent', '/login')).toBe('/tickets');
  });
});
