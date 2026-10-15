/**
 * Same identity as the web app (apps/web/src/styles.css): zinc neutrals,
 * teal for actions, violet only for AI, Geist. Light and dark.
 */
export const palette = {
  light: {
    background: '#ffffff',
    foreground: '#09090b',
    muted: '#f4f4f5',
    mutedForeground: '#52525b',
    border: '#e4e4e7',
    card: '#ffffff',
    primary: '#0f766e',
    primaryForeground: '#ffffff',
    primarySoft: '#f0fdfa',
    ai: '#7c3aed',
    aiSoft: '#f5f3ff',
    success: '#16a34a',
    successSoft: '#f0fdf4',
    warning: '#b45309',
    warningSoft: '#fffbeb',
    danger: '#dc2626',
    dangerSoft: '#fef2f2',
    bubbleOther: '#f4f4f5',
  },
  dark: {
    background: '#09090b',
    foreground: '#fafafa',
    muted: '#18181b',
    mutedForeground: '#a1a1aa',
    border: '#27272a',
    card: '#0c0c0e',
    primary: '#2dd4bf',
    primaryForeground: '#042f2e',
    primarySoft: '#0b2a28',
    ai: '#a78bfa',
    aiSoft: '#1a1530',
    success: '#4ade80',
    successSoft: '#0d2a17',
    warning: '#fbbf24',
    warningSoft: '#2a2008',
    danger: '#f87171',
    dangerSoft: '#2a1010',
    bubbleOther: '#1c1c20',
  },
} as const;

export type Colors = { [K in keyof (typeof palette)['light']]: string };

export const fonts = {
  regular: 'Geist_400Regular',
  medium: 'Geist_500Medium',
  semibold: 'Geist_600SemiBold',
  bold: 'Geist_700Bold',
} as const;

/** Base-4 spacing scale. */
export const space = { xs: 4, sm: 8, md: 12, lg: 16, xl: 24, xxl: 32 } as const;

export const radius = { badge: 6, base: 8, mobile: 12, pill: 999 } as const;

export const text = {
  title: { fontFamily: fonts.bold, fontSize: 28, lineHeight: 34 },
  heading: { fontFamily: fonts.semibold, fontSize: 20, lineHeight: 26 },
  body: { fontFamily: fonts.regular, fontSize: 16, lineHeight: 22 },
  label: { fontFamily: fonts.medium, fontSize: 14, lineHeight: 20 },
  small: { fontFamily: fonts.regular, fontSize: 13, lineHeight: 18 },
} as const;
