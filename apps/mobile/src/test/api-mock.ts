/**
 * Fake API behind global fetch. Routes are "METHOD /path" (no query string)
 * and answer with a JSON body (status 200) or a function returning
 * { status, body }. Unknown routes answer 404 so stray calls fail loudly.
 */

export interface MockResponse {
  status?: number;
  body?: unknown;
}

export interface RecordedRequest {
  method: string;
  path: string;
  query: URLSearchParams;
  headers: Headers;
  body: unknown;
}

export type RouteHandler = (request: RecordedRequest) => MockResponse;

type Handler = unknown;

export type Routes = Record<string, Handler>;

export function mockApi(routes: Routes) {
  const requests: RecordedRequest[] = [];
  let current = { ...routes };

  const fetchMock = jest.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
    const request = input instanceof Request ? input : new Request(String(input), init);
    const url = new URL(request.url);
    const text = init?.body instanceof FormData ? null : await request.clone().text();
    let body: unknown = init?.body instanceof FormData ? init.body : null;

    if (text) {
      try {
        body = JSON.parse(text);
      } catch {
        body = text;
      }
    }

    const recorded: RecordedRequest = {
      method: request.method,
      path: url.pathname,
      query: url.searchParams,
      headers: request.headers,
      body,
    };
    requests.push(recorded);

    const handler = current[`${request.method} ${url.pathname}`];
    const result: MockResponse =
      handler === undefined
        ? { status: 404, body: { type: 'about:blank', title: 'Not Found', status: 404, detail: 'Not found.' } }
        : typeof handler === 'function'
          ? (handler as RouteHandler)(recorded)
          : { body: handler };
    const status = result.status ?? 200;

    return new Response(status === 204 ? null : JSON.stringify(result.body ?? {}), {
      status,
      headers: { 'Content-Type': 'application/json' },
    });
  });

  globalThis.fetch = fetchMock;

  return {
    fetch: fetchMock,
    requests,
    /** Requests sent to a route, e.g. calls('POST /api/v1/tickets'). */
    calls: (route: string) => requests.filter((request) => `${request.method} ${request.path}` === route),
    set: (route: string, handler: Handler) => {
      current = { ...current, [route]: handler };
    },
  };
}

/** A response with an explicit status (plain values are 200 JSON bodies). */
export function respond(status: number, body?: unknown): () => MockResponse {
  return () => ({ status, body });
}

/** An RFC 9457 error response, like the API sends. */
export function problem(status: number, title = 'Error', errors?: Record<string, string[]>): () => MockResponse {
  return respond(status, { type: 'about:blank', title, status, detail: title, ...(errors ? { errors } : {}) });
}
