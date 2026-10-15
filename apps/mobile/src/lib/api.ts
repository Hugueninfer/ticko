import { createTickoClient, ApiError, toProblem, type Attachment, type TickoClient } from '@ticko/api-client';

import { getConfig, ConfigError } from './config';
import { currentToken } from './session';

let unauthorizedHandler: (() => void) | null = null;

/** Called when the API rejects our token (expired or revoked elsewhere). */
export function setUnauthorizedHandler(handler: (() => void) | null): void {
  unauthorizedHandler = handler;
}

function sentToken(input: RequestInfo | URL, init?: RequestInit): boolean {
  if (input instanceof Request) {
    return input.headers.has('Authorization');
  }

  return new Headers(init?.headers).has('Authorization');
}

const guardedFetch: typeof fetch = async (input, init) => {
  const response = await fetch(input, init);

  if (response.status === 401 && sentToken(input, init)) {
    unauthorizedHandler?.();
  }

  return response;
};

function baseUrl(): string {
  const config = getConfig();

  return config instanceof ConfigError ? '' : config.apiUrl;
}

/** Typed API client; authenticates with the bearer token from secure storage. */
export const api: TickoClient = createTickoClient({
  baseUrl: baseUrl(),
  fetch: guardedFetch,
  readCookie: () => null,
  getAccessToken: () => currentToken(),
});

export interface PhotoUpload {
  uri: string;
  name: string;
  mimeType: string;
}

/** Multipart upload; the API re-encodes the image and strips its metadata again. */
export async function uploadAttachment(ticketId: string, photo: PhotoUpload): Promise<Attachment> {
  const form = new FormData();
  // React Native's FormData takes a file descriptor instead of a Blob.
  form.append('file', { uri: photo.uri, name: photo.name, type: photo.mimeType } as unknown as Blob);

  const token = currentToken();
  const response = await guardedFetch(`${baseUrl()}/api/v1/tickets/${encodeURIComponent(ticketId)}/attachments`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: form,
  });
  const body: unknown = await response.json().catch(() => null);

  if (!response.ok) {
    throw new ApiError(toProblem(body, response));
  }

  return (body as { data: Attachment }).data;
}
