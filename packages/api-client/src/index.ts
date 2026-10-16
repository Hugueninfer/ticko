import createClient, { type Client, type Middleware } from 'openapi-fetch';

import type { paths } from './schema';

export type { paths } from './schema';

type JsonData<T> = T extends { content: { 'application/json': infer Body } } ? Body : never;
type Data<T> = T extends { data: infer D } ? D : never;

/** JSON body of a successful response for a path and method. */
export type ResponseBody<P extends keyof paths, M extends keyof paths[P], S extends number = 200> = paths[P][M] extends {
  responses: infer R;
}
  ? S extends keyof R
    ? JsonData<R[S]>
    : never
  : never;

/** JSON body accepted by a path and method. */
export type RequestBody<P extends keyof paths, M extends keyof paths[P]> = paths[P][M] extends {
  requestBody?: { content: { 'application/json': infer B } };
}
  ? B
  : never;

/** Query parameters accepted by a path and method. */
export type QueryParams<P extends keyof paths, M extends keyof paths[P]> = paths[P][M] extends {
  parameters: { query?: infer Q };
}
  ? NonNullable<Q>
  : never;

export type CurrentUser = Data<ResponseBody<'/api/v1/auth/me', 'get'>>;
export type Ticket = Data<ResponseBody<'/api/v1/tickets/{id}', 'get'>>;
export type TicketList = ResponseBody<'/api/v1/tickets', 'get'>;
export type TicketFilters = QueryParams<'/api/v1/tickets', 'get'>;
export type TicketStatus = Ticket['status'];
export type TicketPriority = Ticket['priority'];
export type Sentiment = NonNullable<Ticket['sentiment']>;
export type FieldSource = NonNullable<NonNullable<Ticket['sources']>['category']>;
export type TicketClassification = NonNullable<Ticket['classification']>;
export type ClassificationInput = RequestBody<'/api/v1/tickets/{ticket_id}/classification', 'patch'>;
export type TicketMessage = Data<ResponseBody<'/api/v1/tickets/{ticket_id}/messages', 'get'>>[number];
export type Attachment = Data<ResponseBody<'/api/v1/attachments/{id}', 'get'>>;
export type Category = Data<ResponseBody<'/api/v1/categories', 'get'>>[number];
export type CategoryInput = RequestBody<'/api/v1/categories', 'post'>;
export type TeamMember = Data<ResponseBody<'/api/v1/team', 'get'>>[number];
export type TeamMemberInput = RequestBody<'/api/v1/team', 'post'>;
export type Role = TeamMember['role'];
export type Metrics = Data<ResponseBody<'/api/v1/metrics', 'get'>>;
export type DemoLogin = Data<ResponseBody<'/api/v1/auth/demo', 'get'>>;
export type NotificationPreferences = CurrentUser['notification_preferences'];
export type PushDeviceInput = RequestBody<'/api/v1/push-devices', 'post'>;
export type RealtimeSettings = Data<ResponseBody<'/api/v1/realtime', 'get'>>;
export type KbArticle = Data<ResponseBody<'/api/v1/knowledge/articles/{id}', 'get'>>;
export type KbArticleSummary = Data<ResponseBody<'/api/v1/knowledge/articles', 'get'>>[number];
export type KbArticleInput = RequestBody<'/api/v1/knowledge/articles', 'post'>;
export type KbArticleFilters = QueryParams<'/api/v1/knowledge/articles', 'get'>;
export type KbChunk = Data<ResponseBody<'/api/v1/knowledge/articles/{article_id}/chunks', 'get'>>[number];
export type KnowledgeStats = Data<ResponseBody<'/api/v1/knowledge/stats', 'get'>>;
export type KnowledgeSearchInput = RequestBody<'/api/v1/knowledge/search', 'post'>;
export type KnowledgeSearchHit = Data<ResponseBody<'/api/v1/knowledge/search', 'post'>>[number];
export type AiSuggestion = NonNullable<Data<ResponseBody<'/api/v1/tickets/{ticket_id}/suggestions/latest', 'get'>>>;
export type SuggestionStatus = AiSuggestion['status'];
export type DiscardReason = NonNullable<AiSuggestion['discard_reason']>;

/** RFC 9457 problem details, the shape of every API error. */
export interface ProblemDetails {
  type: string;
  title: string;
  status: number;
  detail: string;
  instance?: string;
  /** Validation errors by field (422 only). */
  errors?: Record<string, string[]>;
}

export class ApiError extends Error {
  readonly status: number;
  readonly problem: ProblemDetails;

  constructor(problem: ProblemDetails) {
    super(problem.detail || problem.title);
    this.name = 'ApiError';
    this.status = problem.status;
    this.problem = problem;
  }

  /** First validation message per field, convenient for forms. */
  fieldErrors(): Record<string, string> {
    return Object.fromEntries(
      Object.entries(this.problem.errors ?? {}).flatMap(([field, messages]) =>
        messages[0] === undefined ? [] : [[field, messages[0]]],
      ),
    );
  }
}

function isProblem(value: unknown): value is ProblemDetails {
  return (
    typeof value === 'object' &&
    value !== null &&
    typeof (value as { status?: unknown }).status === 'number' &&
    typeof (value as { title?: unknown }).title === 'string'
  );
}

/** Turns any error body into problem details, so callers handle one shape. */
export function toProblem(body: unknown, response: Response): ProblemDetails {
  if (isProblem(body)) {
    return { ...body, detail: typeof body.detail === 'string' ? body.detail : body.title };
  }

  return {
    type: 'about:blank',
    title: response.statusText || 'Error',
    status: response.status,
    detail: response.statusText || 'Request failed.',
  };
}

/**
 * Returns the data of an openapi-fetch result or throws an {@link ApiError}.
 * Use it inside TanStack Query functions so failures reach `error`.
 */
export function unwrap<T>(result: { data?: T; error?: unknown; response: Response }): T {
  if (result.error !== undefined || !result.response.ok) {
    throw new ApiError(toProblem(result.error, result.response));
  }

  return result.data as T;
}

const SAFE_METHODS = new Set(['GET', 'HEAD', 'OPTIONS']);

export interface TickoClientOptions {
  /** Origin of the API. Empty (the default) means the page's own origin. */
  baseUrl?: string;
  fetch?: typeof globalThis.fetch;
  /**
   * Reads a cookie by name; used for Laravel's XSRF-TOKEN in browsers.
   * Defaults to document.cookie when available.
   */
  readCookie?: (name: string) => string | null;
  /**
   * Bearer token for non-browser clients (mobile). Browsers use the session
   * cookie instead and must never store tokens.
   */
  getAccessToken?: () => string | null | Promise<string | null>;
}

export function readDocumentCookie(name: string, source?: string): string | null {
  const cookies = source ?? (typeof document === 'undefined' ? '' : document.cookie);

  for (const part of cookies.split(';')) {
    const [key, ...value] = part.trim().split('=');

    if (key === name) {
      return decodeURIComponent(value.join('='));
    }
  }

  return null;
}

/** Adds the CSRF header (cookie sessions) or the bearer token (mobile) to each request. */
export function authMiddleware(options: TickoClientOptions): Middleware {
  const readCookie = options.readCookie ?? ((name: string) => readDocumentCookie(name));

  return {
    async onRequest({ request }) {
      request.headers.set('Accept', 'application/json');
      request.headers.set('X-Requested-With', 'XMLHttpRequest');

      const token = options.getAccessToken ? await options.getAccessToken() : null;

      if (token) {
        request.headers.set('Authorization', `Bearer ${token}`);
      } else if (!SAFE_METHODS.has(request.method)) {
        const xsrf = readCookie('XSRF-TOKEN');

        if (xsrf) {
          request.headers.set('X-XSRF-TOKEN', xsrf);
        }
      }

      return request;
    },
  };
}

/**
 * Query strings the Laravel API understands: booleans as 1/0 (its "boolean"
 * rule rejects "true"), arrays as key[]=value, empty values omitted.
 */
export function serializeQuery(query: Record<string, unknown>): string {
  const params = new URLSearchParams();

  for (const [key, value] of Object.entries(query)) {
    if (value === undefined || value === null || value === '') {
      continue;
    }

    for (const item of Array.isArray(value) ? value : [value]) {
      const name = Array.isArray(value) ? `${key}[]` : key;
      params.append(name, typeof item === 'boolean' ? (item ? '1' : '0') : String(item));
    }
  }

  return params.toString();
}

export type TickoClient = Client<paths>;

export function createTickoClient(options: TickoClientOptions = {}): TickoClient {
  const client = createClient<paths>({
    baseUrl: options.baseUrl ?? '',
    // Cookies only ever go to the API's own origin; there is no cross-origin use.
    credentials: 'same-origin',
    querySerializer: serializeQuery,
    ...(options.fetch ? { fetch: options.fetch } : {}),
  });

  client.use(authMiddleware(options));

  return client;
}

/** Sanctum sets the XSRF-TOKEN cookie; call it once before signing in. */
export async function ensureCsrfCookie(options: Pick<TickoClientOptions, 'baseUrl' | 'fetch'> = {}): Promise<void> {
  const fetchImpl = options.fetch ?? globalThis.fetch;
  const response = await fetchImpl(`${options.baseUrl ?? ''}/sanctum/csrf-cookie`, {
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  });

  if (!response.ok) {
    throw new ApiError(toProblem(null, response));
  }
}
