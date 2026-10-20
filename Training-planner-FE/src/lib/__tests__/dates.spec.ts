import { describe, expect, it } from 'vitest';
import {
  addDays,
  minutesIntoDay,
  toDateKey,
  toTimeValue,
  weekdayIndex,
  zonedToDate
} from '@/lib/dates';

const PRAGUE = 'Europe/Prague';

describe('zonedToDate', () => {
  it('converts Prague summer time (UTC+2)', () => {
    expect(zonedToDate('2026-07-15', '10:00', PRAGUE).toISOString()).toBe('2026-07-15T08:00:00.000Z');
  });

  it('converts Prague winter time (UTC+1)', () => {
    expect(zonedToDate('2026-12-15', '10:00', PRAGUE).toISOString()).toBe('2026-12-15T09:00:00.000Z');
  });

  it('handles the day of the DST switch', () => {
    // 2026-10-25 03:00 CEST -> 02:00 CET; noon is already winter time
    expect(zonedToDate('2026-10-25', '12:00', PRAGUE).toISOString()).toBe('2026-10-25T11:00:00.000Z');
    // midnight is still summer time
    expect(zonedToDate('2026-10-25', '00:00', PRAGUE).toISOString()).toBe('2026-10-24T22:00:00.000Z');
  });

  it('works for other timezones', () => {
    expect(zonedToDate('2026-07-15', '10:00', 'America/New_York').toISOString()).toBe(
      '2026-07-15T14:00:00.000Z'
    );
  });
});

describe('toDateKey / toTimeValue', () => {
  it('reads the wall clock in the given timezone', () => {
    // 23:30 UTC is already the next day in Prague
    expect(toDateKey('2026-10-19T23:30:00+00:00', PRAGUE)).toBe('2026-10-20');
    expect(toTimeValue('2026-10-19T23:30:00+00:00', PRAGUE)).toBe('01:30');
  });

  it('round-trips with zonedToDate', () => {
    const date = zonedToDate('2026-03-29', '18:45', PRAGUE);
    expect(toDateKey(date, PRAGUE)).toBe('2026-03-29');
    expect(toTimeValue(date, PRAGUE)).toBe('18:45');
  });
});

describe('calendar helpers', () => {
  it('adds days across months and years', () => {
    expect(addDays('2026-12-30', 3)).toBe('2027-01-02');
    expect(addDays('2026-03-01', -1)).toBe('2026-02-28');
  });

  it('numbers weekdays from Monday', () => {
    expect(weekdayIndex('2026-10-19')).toBe(0); // Monday
    expect(weekdayIndex('2026-10-25')).toBe(6); // Sunday
  });

  it('measures minutes from the local midnight', () => {
    expect(minutesIntoDay('2026-10-20T06:30:00+02:00', '2026-10-20', PRAGUE)).toBe(6 * 60 + 30);
    expect(minutesIntoDay('2026-10-21T01:00:00+02:00', '2026-10-20', PRAGUE)).toBe(25 * 60);
  });
});
