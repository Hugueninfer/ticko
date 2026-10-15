import type { Role } from '@ticko/api-client';

/** Where a signed-in user lands: the page they came from, or their home page. */
export function landingPath(role: Role, from: unknown): string {
  if (typeof from === 'string' && from.startsWith('/') && !from.startsWith('//') && from !== '/login') {
    return from;
  }

  return role === 'admin' ? '/dashboard' : '/tickets';
}
