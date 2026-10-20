import { describe, expect, it } from 'vitest';
import { movedTimes, resizedTimes } from '@/lib/reschedule';
import type { Event } from '@/services/api/trainingPlannerAPI.schemas';

const PRAGUE = 'Europe/Prague';

function event(start: string, end: string): Event {
  return {
    id: 1,
    category_id: 1,
    subcategory_id: null,
    title: 'Run',
    description: null,
    start_at: start,
    end_at: end,
    duration_minutes: 0,
    status: 'planned',
    created_at: '',
    updated_at: ''
  };
}

describe('movedTimes', () => {
  it('moves within the day and keeps the duration', () => {
    const times = movedTimes(event('2026-10-20T08:00:00+02:00', '2026-10-20T09:30:00+02:00'), 45, 0, PRAGUE);
    expect(times).toEqual({ start_at: '2026-10-20T06:45:00.000Z', end_at: '2026-10-20T08:15:00.000Z' });
  });

  it('moves to another day', () => {
    const times = movedTimes(event('2026-10-20T08:00:00+02:00', '2026-10-20T09:00:00+02:00'), 0, 2, PRAGUE);
    expect(times.start_at).toBe('2026-10-22T06:00:00.000Z');
  });

  it('keeps the wall-clock time across the DST switch (Sat -> Mon)', () => {
    // 2026-10-24 is still summer time (+2), 2026-10-26 is winter time (+1)
    const times = movedTimes(event('2026-10-24T09:00:00+02:00', '2026-10-24T10:00:00+02:00'), 0, 2, PRAGUE);
    expect(times.start_at).toBe('2026-10-26T08:00:00.000Z'); // 9:00 local
  });

  it('rolls over midnight when dragged past the end of the day', () => {
    const times = movedTimes(event('2026-10-20T23:00:00+02:00', '2026-10-21T00:00:00+02:00'), 90, 0, PRAGUE);
    expect(times.start_at).toBe('2026-10-20T22:30:00.000Z'); // 00:30 on the 21st
  });
});

describe('resizedTimes', () => {
  const base = event('2026-10-20T08:00:00+02:00', '2026-10-20T09:00:00+02:00');

  it('changes only the end', () => {
    expect(resizedTimes(base, 30)).toEqual({
      start_at: '2026-10-20T06:00:00.000Z',
      end_at: '2026-10-20T07:30:00.000Z'
    });
  });

  it('never goes below 15 minutes or above 24 hours', () => {
    expect(resizedTimes(base, -120).end_at).toBe('2026-10-20T06:15:00.000Z');
    expect(resizedTimes(base, 5000).end_at).toBe('2026-10-21T06:00:00.000Z');
  });
});
