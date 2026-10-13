import { describe, expect, it } from 'vitest';

import { makeTicket } from '@/test/fixtures';

import { tabFilters } from './queue';
import { slaState } from './ticket-meta';

const now = new Date('2026-10-12T10:00:00Z');
const sla = (overrides: object) => ({ ...makeTicket().sla, ...overrides });

describe('slaState', () => {
  it('tracks the first response first, then resolution', () => {
    expect(
      slaState(
        {
          status: 'open',
          sla: sla({ first_response_due_at: '2026-10-12T10:30:00Z', resolution_due_at: '2026-10-12T18:00:00Z' }),
        },
        now,
      ),
    ).toEqual({ kind: 'running', label: 'firstResponseIn', minutes: 30, tone: 'warn' });

    expect(
      slaState(
        {
          status: 'pending',
          sla: sla({
            first_response_at: '2026-10-12T09:10:00Z',
            first_response_due_at: '2026-10-12T09:15:00Z',
            resolution_due_at: '2026-10-12T18:00:00Z',
          }),
        },
        now,
      ),
    ).toEqual({ kind: 'running', label: 'resolutionIn', minutes: 480, tone: 'ok' });
  });

  it('reports overdue, finished and missing deadlines', () => {
    expect(slaState({ status: 'open', sla: sla({ first_response_due_at: '2026-10-12T09:00:00Z' }) }, now)).toEqual({
      kind: 'overdue',
      minutes: 60,
    });
    expect(slaState({ status: 'resolved', sla: sla({}) }, now)).toEqual({ kind: 'done' });
    expect(slaState({ status: 'open', sla: sla({ first_response_due_at: null, resolution_due_at: null }) }, now)).toEqual({
      kind: 'none',
    });
  });
});

describe('tabFilters', () => {
  it('maps queue tabs to API filters', () => {
    expect(tabFilters('mine', 'u1')).toEqual({ assignee_id: 'u1' });
    expect(tabFilters('mine', undefined)).toEqual({});
    expect(tabFilters('unassigned', 'u1')).toEqual({ unassigned: true });
    expect(tabFilters('overdue', 'u1')).toEqual({ overdue: true });
    expect(tabFilters('all', 'u1')).toEqual({});
  });
});
