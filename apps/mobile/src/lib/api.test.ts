import { ApiError } from '@ticko/api-client';

import { mockApi, problem, respond } from '@/test/api-mock';

import { api, setUnauthorizedHandler, uploadAttachment } from './api';
import { saveSession } from './session';

describe('api client', () => {
  afterEach(() => setUnauthorizedHandler(null));

  it('sends the bearer token and no cookies or CSRF header', async () => {
    const mock = mockApi({ 'GET /api/v1/auth/me': { data: {} } });
    await saveSession('token-123', 60);

    await api.GET('/api/v1/auth/me');

    const [request] = mock.requests;
    expect(request?.headers.get('Authorization')).toBe('Bearer token-123');
    expect(request?.headers.get('X-XSRF-TOKEN')).toBeNull();
    expect(mock.fetch.mock.calls[0]?.[0]).toBeInstanceOf(Request);
  });

  it('reports a rejected token, but not failed sign-ins', async () => {
    mockApi({ 'GET /api/v1/auth/me': problem(401, 'Unauthenticated.'), 'POST /api/v1/auth/tokens': problem(401) });
    const handler = jest.fn();
    setUnauthorizedHandler(handler);

    await api.POST('/api/v1/auth/tokens', { body: { email: 'a@b.c', password: 'x', device_name: 'p' } });
    expect(handler).not.toHaveBeenCalled();

    await saveSession('expired', 60);
    await api.GET('/api/v1/auth/me');
    expect(handler).toHaveBeenCalledTimes(1);
  });

  it('uploads photos as multipart form data', async () => {
    const mock = mockApi({ 'POST /api/v1/tickets/t1/attachments': respond(201, { data: { id: 'att-1' } }) });
    await saveSession('token-123', 60);

    await expect(uploadAttachment('t1', { uri: 'file:///a.jpg', name: 'a.jpg', mimeType: 'image/jpeg' })).resolves.toEqual({
      id: 'att-1',
    });

    const init = mock.fetch.mock.calls[0]?.[1];
    expect(init?.body).toBeInstanceOf(FormData);
    expect(new Headers(init?.headers).get('Authorization')).toBe('Bearer token-123');
  });

  it('turns upload failures into ApiError', async () => {
    mockApi({
      'POST /api/v1/tickets/t1/attachments': problem(422, 'The file field must be an image.', { file: ['Must be an image.'] }),
    });

    const error = await uploadAttachment('t1', { uri: 'file:///a.txt', name: 'a.txt', mimeType: 'text/plain' }).catch(
      (e: unknown) => e,
    );

    expect(error).toBeInstanceOf(ApiError);
    expect((error as ApiError).fieldErrors()).toEqual({ file: 'Must be an image.' });
  });
});
