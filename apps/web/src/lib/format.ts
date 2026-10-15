/** Formatting helpers bound to the active UI language. */

export function formatDateTime(iso: string, locale: string): string {
  return new Intl.DateTimeFormat(locale, { dateStyle: 'short', timeStyle: 'short' }).format(new Date(iso));
}

export function formatDate(iso: string, locale: string, options: Intl.DateTimeFormatOptions = { dateStyle: 'medium' }): string {
  return new Intl.DateTimeFormat(locale, options).format(new Date(iso));
}

export function formatTime(iso: string, locale: string): string {
  return new Intl.DateTimeFormat(locale, { hour: '2-digit', minute: '2-digit' }).format(new Date(iso));
}

export function formatNumber(value: number, locale: string, options?: Intl.NumberFormatOptions): string {
  return new Intl.NumberFormat(locale, options).format(value);
}

/** "42 min", "3 h", "2 d" — compact durations for SLA chips. */
export function formatMinutes(minutes: number, locale: string): string {
  const abs = Math.abs(minutes);
  const unit = abs < 60 ? 'minute' : abs < 60 * 48 ? 'hour' : 'day';
  const value = unit === 'minute' ? abs : unit === 'hour' ? abs / 60 : abs / (60 * 24);

  return new Intl.NumberFormat(locale, { style: 'unit', unit, unitDisplay: 'narrow', maximumFractionDigits: 0 }).format(
    Math.max(1, Math.round(value)),
  );
}

export function minutesUntil(iso: string, now: Date = new Date()): number {
  return Math.round((new Date(iso).getTime() - now.getTime()) / 60_000);
}

export function formatFileSize(bytes: number, locale: string): string {
  const kb = bytes / 1024;

  return kb < 1024
    ? `${formatNumber(Math.max(1, Math.round(kb)), locale)} KB`
    : `${formatNumber(kb / 1024, locale, { maximumFractionDigits: 1 })} MB`;
}

/** Short, human ticket reference from a ULID: the last 6 characters. */
export function ticketRef(id: string): string {
  return `#${id.slice(-6).toUpperCase()}`;
}

export function isoDate(date: Date): string {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');

  return `${y}-${m}-${d}`;
}
