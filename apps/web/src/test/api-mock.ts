import { vi } from 'vitest';

export interface MockRequest {
  method: string;
  path: string;
  query: URLSearchParams;
  headers: Headers;
  body: unknown;
}

type Reply = Response | { status: number; body?: unknown } | object | null;
type HandlerFn = (request: MockRequest, params: Record<string, string>) => Reply | Promise<Reply>;
type Handler = Reply | HandlerFn;

function toResponse(reply: Reply): Response {
  if (reply instanceof Response) {
    return reply;
  }

  if (reply === null) {
    return new Response(null, { status: 204 });
  }

  if (
    'status' in reply &&
    typeof reply.status === 'number' &&
    Object.keys(reply).every((key) => key === 'status' || key === 'body')
  ) {
    const { status, body } = reply;

    return body === undefined
      ? new Response(null, { status })
      : new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });
  }

  return new Response(JSON.stringify(reply), { status: 200, headers: { 'Content-Type': 'application/json' } });
}

function match(pattern: string, path: string): Record<string, string> | null {
  const expected = pattern.split('/');
  const actual = path.split('/');

  if (expected.length !== actual.length) {
    return null;
  }

  const params: Record<string, string> = {};

  for (const [index, part] of expected.entries()) {
    const value = actual[index] ?? '';

    if (part.startsWith(':')) {
      params[part.slice(1)] = decodeURIComponent(value);
    } else if (part !== value) {
      return null;
    }
  }

  return params;
}

/**
 * Replaces fetch with a tiny router: keys are "METHOD /path/:param".
 * Unknown routes answer 404 problem details. Returns the recorded requests.
 */
export function mockApi(routes: Record<string, Handler>) {
  const requests: MockRequest[] = [];

  const fetchMock = vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
    const request = input instanceof Request ? input : new Request(input, init);
    const url = new URL(request.url);
    // Multipart bodies (jsdom File objects) are not read: undici cannot stream them.
    const multipart = request.headers.get('Content-Type')?.startsWith('multipart/') ?? false;
    const text = request.method === 'GET' || request.method === 'HEAD' || multipart ? '' : await request.clone().text();
    let body: unknown = text;

    try {
      body = text ? JSON.parse(text) : undefined;
    } catch {
      body = text;
    }

    const recorded: MockRequest = {
      method: request.method,
      path: url.pathname,
      query: url.searchParams,
      headers: request.headers,
      body,
    };
    requests.push(recorded);

    if (url.pathname === '/sanctum/csrf-cookie') {
      document.cookie = 'XSRF-TOKEN=test-xsrf';

      return new Response(null, { status: 204 });
    }

    for (const [key, handler] of Object.entries(routes)) {
      const [method, pattern = ''] = key.split(' ');
      const params = method === request.method ? match(pattern, url.pathname) : null;

      if (params) {
        return toResponse(typeof handler === 'function' ? await (handler as HandlerFn)(recorded, params) : handler);
      }
    }

    return toResponse({
      status: 404,
      body: { type: 'x', title: 'Not Found.', status: 404, detail: 'The requested resource does not exist.' },
    });
  });

  vi.stubGlobal('fetch', fetchMock);

  return {
    requests,
    find: (method: string, path: string) => requests.filter((request) => request.method === method && request.path === path),
  };
}

export function problem(status: number, detail: string, errors?: Record<string, string[]>) {
  return {
    status,
    body: { type: 'https://ticko.dev/problems/x', title: 'Error.', status, detail, ...(errors ? { errors } : {}) },
  };
}
