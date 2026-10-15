/** Short, human ticket reference from a ULID (same as the web): the last 6 characters. */
export function ticketRef(id: string): string {
  return `#${id.slice(-6).toUpperCase()}`;
}

function startOfDay(date: Date): number {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
}

/** Days between the calendar day of `iso` and today (0 = today, 1 = yesterday). */
export function daysAgo(iso: string, now: Date = new Date()): number {
  return Math.round((startOfDay(now) - startOfDay(new Date(iso))) / 86_400_000);
}

export function formatTime(iso: string, locale: string): string {
  return new Intl.DateTimeFormat(locale, { hour: '2-digit', minute: '2-digit' }).format(new Date(iso));
}

/** "14:09" today, the given word for yesterday, "10 out." otherwise. */
export function formatListDate(iso: string, locale: string, yesterday: string, now: Date = new Date()): string {
  const days = daysAgo(iso, now);

  if (days <= 0) {
    return formatTime(iso, locale);
  }

  if (days === 1) {
    return yesterday.toLowerCase();
  }

  return new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'short' }).format(new Date(iso));
}

/** Label of the day separators in a conversation. */
export function formatDayLabel(
  iso: string,
  locale: string,
  labels: { today: string; yesterday: string },
  now: Date = new Date(),
): string {
  const days = daysAgo(iso, now);

  if (days <= 0) {
    return labels.today;
  }

  if (days === 1) {
    return labels.yesterday;
  }

  return new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'long' }).format(new Date(iso));
}

export function initials(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  const first = parts[0]?.[0] ?? '';
  const last = parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? '') : '';

  return (first + last).toUpperCase();
}

/**
 * Subject for a ticket written as free text: its first line, shortened.
 * The API needs 3 to 200 characters.
 */
export function subjectFrom(description: string, max = 80): string {
  const firstLine = description.trim().split('\n')[0]?.replace(/\s+/g, ' ').trim() ?? '';

  if (firstLine.length <= max) {
    return firstLine.padEnd(3, '.');
  }

  const cut = firstLine.slice(0, max - 1);
  const lastSpace = cut.lastIndexOf(' ');

  return `${(lastSpace > max / 2 ? cut.slice(0, lastSpace) : cut).trimEnd()}…`;
}
