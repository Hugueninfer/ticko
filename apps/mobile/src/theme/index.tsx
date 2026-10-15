import { useColorScheme } from 'react-native';

import { palette, type Colors } from './tokens';

export * from './tokens';

export interface Theme {
  dark: boolean;
  colors: Colors;
}

/** Follows the phone's light/dark setting. */
export function useTheme(): Theme {
  const dark = useColorScheme() === 'dark';

  return { dark, colors: dark ? palette.dark : palette.light };
}
