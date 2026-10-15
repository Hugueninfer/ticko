/**
 * Build-time settings (EXPO_PUBLIC_*), validated once at startup.
 *
 * Outside development the API must be served over HTTPS. In development,
 * plain HTTP is accepted only for loopback and private network addresses
 * (your computer on the Wi-Fi), never for a public host.
 */

export class ConfigError extends Error {
  override name = 'ConfigError';
}

const PRIVATE_HOST = [
  /^localhost$/,
  /^127\.\d+\.\d+\.\d+$/,
  /^10\.\d+\.\d+\.\d+$/,
  /^192\.168\.\d+\.\d+$/,
  /^172\.(1[6-9]|2\d|3[01])\.\d+\.\d+$/,
  /\.local$/,
];

const TENANT_SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

export interface AppConfig {
  apiUrl: string;
  tenant: string;
}

export function isPrivateHost(host: string): boolean {
  return PRIVATE_HOST.some((pattern) => pattern.test(host));
}

export function parseApiUrl(raw: string | undefined, isDev: boolean): string {
  if (!raw) {
    throw new ConfigError('EXPO_PUBLIC_API_URL is not set. Copy apps/mobile/.env.example to apps/mobile/.env.');
  }

  let url: URL;

  try {
    url = new URL(raw);
  } catch {
    throw new ConfigError(`EXPO_PUBLIC_API_URL is not a valid URL: ${raw}`);
  }

  if (url.protocol === 'https:') {
    return url.origin;
  }

  if (url.protocol === 'http:' && isDev && isPrivateHost(url.hostname)) {
    return url.origin;
  }

  throw new ConfigError(
    isDev
      ? 'EXPO_PUBLIC_API_URL must use HTTPS, or HTTP with a local network address (e.g. http://192.168.0.10:8080).'
      : 'EXPO_PUBLIC_API_URL must use HTTPS outside development.',
  );
}

export function parseTenant(raw: string | undefined): string {
  if (!raw || !TENANT_SLUG.test(raw)) {
    throw new ConfigError('EXPO_PUBLIC_TENANT must be the tenant slug, e.g. "acme".');
  }

  return raw;
}

export function loadConfig(env: Record<string, string | undefined>, isDev: boolean): AppConfig {
  return { apiUrl: parseApiUrl(env.apiUrl, isDev), tenant: parseTenant(env.tenant) };
}

let cached: AppConfig | ConfigError | undefined;

/** The app's configuration, or the error explaining what to fix. */
export function getConfig(): AppConfig | ConfigError {
  if (cached === undefined) {
    try {
      // Expo inlines EXPO_PUBLIC_* only when they are read by their full name.
      cached = loadConfig({ apiUrl: process.env.EXPO_PUBLIC_API_URL, tenant: process.env.EXPO_PUBLIC_TENANT }, __DEV__);
    } catch (error) {
      cached = error instanceof ConfigError ? error : new ConfigError(String(error));
    }
  }

  return cached;
}

export function requireConfig(): AppConfig {
  const config = getConfig();

  if (config instanceof ConfigError) {
    throw config;
  }

  return config;
}
