import { ConfigError, isPrivateHost, loadConfig, parseApiUrl, parseTenant } from './config';

describe('config', () => {
  it('accepts HTTPS anywhere and normalizes to the origin', () => {
    expect(parseApiUrl('https://help.example.com/some/path', false)).toBe('https://help.example.com');
    expect(parseApiUrl('https://abc.trycloudflare.com', true)).toBe('https://abc.trycloudflare.com');
  });

  it('accepts plain HTTP only in development and only on private networks', () => {
    expect(parseApiUrl('http://192.168.0.165:8090', true)).toBe('http://192.168.0.165:8090');
    expect(parseApiUrl('http://10.0.2.2:8080', true)).toBe('http://10.0.2.2:8080');
    expect(() => parseApiUrl('http://192.168.0.165:8090', false)).toThrow(ConfigError);
    expect(() => parseApiUrl('http://example.com', true)).toThrow(/HTTPS/);
  });

  it('rejects missing, malformed or non-web URLs', () => {
    expect(() => parseApiUrl(undefined, true)).toThrow(/EXPO_PUBLIC_API_URL is not set/);
    expect(() => parseApiUrl('not a url', true)).toThrow(/not a valid URL/);
    expect(() => parseApiUrl('ftp://192.168.0.1', true)).toThrow(ConfigError);
  });

  it('knows private hosts', () => {
    for (const host of ['localhost', '127.0.0.1', '10.1.2.3', '192.168.1.1', '172.16.0.1', '172.31.255.1', 'mac.local']) {
      expect(isPrivateHost(host)).toBe(true);
    }

    for (const host of ['172.32.0.1', '8.8.8.8', 'example.com', '192.168.evil.com']) {
      expect(isPrivateHost(host)).toBe(false);
    }
  });

  it('validates the tenant slug', () => {
    expect(parseTenant('acme')).toBe('acme');
    expect(parseTenant('loja-aurora')).toBe('loja-aurora');
    expect(() => parseTenant('')).toThrow(ConfigError);
    expect(() => parseTenant('Acme Inc')).toThrow(ConfigError);
  });

  it('loads both settings together', () => {
    expect(loadConfig({ apiUrl: 'http://192.168.0.10:8080', tenant: 'acme' }, true)).toEqual({
      apiUrl: 'http://192.168.0.10:8080',
      tenant: 'acme',
    });
  });
});
