import { describe, expect, it } from 'vitest';

import { formatFileSize, formatMinutes, isoDate, minutesUntil, ticketRef } from './format';
import { initials } from './utils';

describe('format helpers', () => {
  it('formats compact durations', () => {
    expect(formatMinutes(42, 'en')).toBe('42m');
    expect(formatMinutes(-90, 'en')).toBe('2h');
    expect(formatMinutes(60 * 72, 'en')).toBe('3d');
    expect(formatMinutes(0, 'en')).toBe('1m');
  });

  it('computes minutes until a deadline', () => {
    const now = new Date('2026-10-12T10:00:00Z');

    expect(minutesUntil('2026-10-12T10:30:00Z', now)).toBe(30);
    expect(minutesUntil('2026-10-12T09:00:00Z', now)).toBe(-60);
  });

  it('formats file sizes, references, ISO dates and initials', () => {
    expect(formatFileSize(214 * 1024, 'pt-BR')).toBe('214 KB');
    expect(formatFileSize(3.5 * 1024 * 1024, 'en')).toBe('3.5 MB');
    expect(ticketRef('01jabcdefghjkmnpqrstvwxyz4')).toBe('#VWXYZ4');
    expect(isoDate(new Date(2026, 0, 5))).toBe('2026-01-05');
    expect(initials('Mariana Costa')).toBe('MC');
    expect(initials('Ana')).toBe('AN');
    expect(initials('  ')).toBe('');
  });
});
