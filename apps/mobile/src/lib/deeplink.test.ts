import { isTicketId, ticketIdFromNotification, ticketPath } from './deeplink';

const ID = '01m503y8y87hra2tfv9vqthppt';

describe('deep links', () => {
  it('accepts only well-formed ticket ids', () => {
    expect(isTicketId(ID)).toBe(true);
    expect(isTicketId(ID.toUpperCase())).toBe(true);
    expect(isTicketId('../../auth/me')).toBe(false);
    expect(isTicketId(`${ID}?x=1`)).toBe(false);
    expect(isTicketId('1')).toBe(false);
    expect(isTicketId(42)).toBe(false);
  });

  it('reads the ticket id from a notification payload', () => {
    expect(ticketIdFromNotification({ ticket_id: ID.toUpperCase(), url: 'ticko://tickets/x' })).toBe(ID);
    expect(ticketIdFromNotification({ ticket_id: 'javascript:alert(1)' })).toBeNull();
    expect(ticketIdFromNotification(null)).toBeNull();
    expect(ticketIdFromNotification('x')).toBeNull();
  });

  it('builds in-app routes from valid ids only', () => {
    expect(ticketPath(ID)).toBe(`/tickets/${ID}`);
    expect(ticketPath('https://evil.example')).toBeNull();
  });
});
