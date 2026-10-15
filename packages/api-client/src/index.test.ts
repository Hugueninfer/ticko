import { describe, expect, it, vi } from 'vitest';

import { ApiError, createTickoClient, ensureCsrfCookie, readDocumentCookie, serializeQuery, toProblem, unwrap } from './index';

function jsonResponse(body: unknown, status = 200, contentType = 'application/json'): Response {
  return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': contentType } });
}

function recordingFetch(response: () => Response) {
  const requests: Request[] = [];
  const fetch = vi.fn((input: Request | string | URL, init?: RequestInit) => {
    requests.push(input instanceof Request ? input : new Request(input, init));
    return Promise.resolve(response());
  });

  return { fetch: fetch as unknown as typeof globalThis.fetch, requests };
}

describe('createTickoClient', () => {
  it('sends same-origin requests with JSON headers and no CSRF header on safe methods', async () => {
    const { fetch, requests } = recordingFetch(() => jsonResponse({ data: { id: '1' } }));
    const client = createTickoClient({ baseUrl: 'http://app.test', fetch, readCookie: () => 'token' });

    await client.GET('/api/v1/auth/me');

    const request = requests[0];
    expect(request?.url).toBe('http://app.test/api/v1/auth/me');
    expect(request?.credentials).toBe('same-origin');
    expect(request?.headers.get('Accept')).toBe('application/json');
    expect(request?.headers.get('X-Requested-With')).toBe('XMLHttpRequest');
    expect(request?.headers.get('X-XSRF-TOKEN')).toBeNull();
  });

  it('echoes the XSRF-TOKEN cookie on state-changing requests', async () => {
    const { fetch, requests } = recordingFetch(() => new Response(null, { status: 204 }));
    const client = createTickoClient({
      baseUrl: 'http://app.test',
      fetch,
      readCookie: (name) => (name === 'XSRF-TOKEN' ? 'abc=' : null),
    });

    await client.POST('/api/v1/auth/logout');

    expect(requests[0]?.headers.get('X-XSRF-TOKEN')).toBe('abc=');
    expect(requests[0]?.headers.get('Authorization')).toBeNull();
  });

  it('uses a bearer token instead of the cookie when one is provided (mobile)', async () => {
    const { fetch, requests } = recordingFetch(() => new Response(null, { status: 204 }));
    const client = createTickoClient({
      baseUrl: 'http://app.test',
      fetch,
      getAccessToken: () => Promise.resolve('ticko_123'),
      readCookie: () => 'abc',
    });

    await client.POST('/api/v1/auth/logout');

    expect(requests[0]?.headers.get('Authorization')).toBe('Bearer ticko_123');
    expect(requests[0]?.headers.get('X-XSRF-TOKEN')).toBeNull();
  });
});

describe('unwrap', () => {
  it('returns the data of successful responses', () => {
    expect(unwrap({ data: { ok: true }, response: new Response(null, { status: 200 }) })).toEqual({ ok: true });
  });

  it('throws ApiError with problem details and field errors', () => {
    const problem = {
      type: 'https://ticko.dev/problems/validation-error',
      title: 'The given data was invalid.',
      status: 422,
      detail: 'One or more fields failed validation.',
      errors: { email: ['The email field is required.', 'second'], name: [] },
    };

    try {
      unwrap({ error: problem, response: new Response(null, { status: 422 }) });
      expect.unreachable();
    } catch (error) {
      expect(error).toBeInstanceOf(ApiError);
      const apiError = error as ApiError;
      expect(apiError.status).toBe(422);
      expect(apiError.message).toBe('One or more fields failed validation.');
      expect(apiError.fieldErrors()).toEqual({ email: 'The email field is required.' });
    }
  });

  it('treats non-ok responses without a body as errors too', () => {
    expect(() => unwrap({ data: undefined, response: new Response(null, { status: 500, statusText: 'Server Error' }) })).toThrow(
      'Server Error',
    );
  });
});

describe('toProblem', () => {
  it('falls back to the status line for non-problem bodies', () => {
    expect(toProblem('<html>', new Response(null, { status: 502, statusText: 'Bad Gateway' }))).toEqual({
      type: 'about:blank',
      title: 'Bad Gateway',
      status: 502,
      detail: 'Bad Gateway',
    });
    expect(toProblem(null, new Response(null, { status: 500 })).detail).toBe('Request failed.');
  });

  it('fills a missing detail with the title', () => {
    expect(toProblem({ title: 'Forbidden.', status: 403, type: 'x' }, new Response(null, { status: 403 })).detail).toBe(
      'Forbidden.',
    );
  });
});

describe('readDocumentCookie', () => {
  it('reads and decodes one cookie', () => {
    expect(readDocumentCookie('XSRF-TOKEN', 'a=1; XSRF-TOKEN=ab%3D%3D; b=2')).toBe('ab==');
    expect(readDocumentCookie('missing', 'a=1')).toBeNull();
    expect(readDocumentCookie('a')).toBeNull();
  });
});

describe('ensureCsrfCookie', () => {
  it('calls the Sanctum CSRF endpoint on the same origin', async () => {
    const { fetch, requests } = recordingFetch(() => new Response(null, { status: 204 }));

    await ensureCsrfCookie({ baseUrl: 'http://app.test', fetch });

    expect(requests[0]?.url).toBe('http://app.test/sanctum/csrf-cookie');
    expect(requests[0]?.credentials).toBe('same-origin');
  });

  it('throws when the endpoint fails', async () => {
    const { fetch } = recordingFetch(() => new Response(null, { status: 503, statusText: 'Unavailable' }));

    await expect(ensureCsrfCookie({ baseUrl: 'http://app.test', fetch })).rejects.toBeInstanceOf(ApiError);
  });
});

describe('serializeQuery', () => {
  it('sends booleans as 1/0, arrays with brackets and skips empty values', () => {
    expect(
      serializeQuery({ overdue: true, unassigned: false, q: '', page: 2, status: undefined, ids: ['a', 'b'], x: null }),
    ).toBe('overdue=1&unassigned=0&page=2&ids%5B%5D=a&ids%5B%5D=b');
  });

  it('is used for client requests', async () => {
    const { fetch, requests } = recordingFetch(() => jsonResponse({ data: [], links: {}, meta: {} }));
    const client = createTickoClient({ baseUrl: 'http://app.test', fetch });

    await client.GET('/api/v1/tickets', { params: { query: { overdue: true, q: 'printer' } } });

    expect(requests[0]?.url).toBe('http://app.test/api/v1/tickets?overdue=1&q=printer');
  });
});
