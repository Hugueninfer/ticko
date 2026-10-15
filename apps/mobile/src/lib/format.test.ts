import { daysAgo, formatDayLabel, formatListDate, initials, subjectFrom, ticketRef } from './format';

const now = new Date(2026, 9, 15, 15, 0);

describe('format', () => {
  it('builds the short ticket reference like the web app', () => {
    expect(ticketRef('01m503y8y87hra2tfv9vqthppt')).toBe('#QTHPPT');
  });

  it('counts calendar days', () => {
    expect(daysAgo(new Date(2026, 9, 15, 0, 5).toISOString(), now)).toBe(0);
    expect(daysAgo(new Date(2026, 9, 14, 23, 59).toISOString(), now)).toBe(1);
  });

  it('formats list dates as time, yesterday or day', () => {
    expect(formatListDate(new Date(2026, 9, 15, 14, 9).toISOString(), 'pt-BR', 'Ontem', now)).toBe('14:09');
    expect(formatListDate(new Date(2026, 9, 14, 9, 0).toISOString(), 'pt-BR', 'Ontem', now)).toBe('ontem');
    expect(formatListDate(new Date(2026, 9, 10, 9, 0).toISOString(), 'pt-BR', 'Ontem', now)).toMatch(/10/);
  });

  it('labels conversation days', () => {
    const labels = { today: 'Hoje', yesterday: 'Ontem' };

    expect(formatDayLabel(now.toISOString(), 'pt-BR', labels, now)).toBe('Hoje');
    expect(formatDayLabel(new Date(2026, 9, 14).toISOString(), 'pt-BR', labels, now)).toBe('Ontem');
    expect(formatDayLabel(new Date(2026, 9, 1).toISOString(), 'pt-BR', labels, now)).toMatch(/outubro/);
  });

  it('takes initials from names', () => {
    expect(initials('Mariana Costa')).toBe('MC');
    expect(initials('Ana')).toBe('A');
    expect(initials('  ')).toBe('');
  });

  it('derives a subject from the first line of the description', () => {
    expect(subjectFrom('Fone não funciona\nComprei dia 05/10')).toBe('Fone não funciona');
    expect(subjectFrom('Oi')).toBe('Oi.');
    const long = subjectFrom('palavra '.repeat(30));
    expect(long.length).toBeLessThanOrEqual(80);
    expect(long.endsWith('…')).toBe(true);
    expect(subjectFrom('x'.repeat(100))).toHaveLength(80);
  });
});
