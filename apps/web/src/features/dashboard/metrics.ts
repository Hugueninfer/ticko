import { isoDate } from '@/lib/format';

export const RANGES = ['7', '30', '90'] as const;
export type Range = (typeof RANGES)[number];

export function periodFor(range: Range, today: Date = new Date()): { from: string; to: string } {
  const from = new Date(today);
  from.setDate(from.getDate() - (Number(range) - 1));

  return { from: isoDate(from), to: isoDate(today) };
}

/** Signed change between periods, and whether that change is good news. */
export function delta(value: number | null, previous: number | null, lowerIsBetter = false) {
  if (value === null || previous === null) {
    return null;
  }

  const diff = value - previous;

  return { diff, good: diff === 0 ? null : lowerIsBetter ? diff < 0 : diff > 0 };
}
