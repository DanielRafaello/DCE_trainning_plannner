import { describe, expect, it } from 'vitest';
import { formatTimestamp, parseTimestamp } from '@/lib/timestamps';

describe('parseTimestamp', () => {
  it('parses PostgreSQL timestamptz text', () => {
    expect(parseTimestamp('2026-10-18 08:00:00.123456+00')?.toISOString()).toBe(
      '2026-10-18T08:00:00.123Z'
    );
    expect(parseTimestamp('2026-10-18 08:00:00+02')?.toISOString()).toBe('2026-10-18T06:00:00.000Z');
  });

  it('returns null for garbage', () => {
    expect(parseTimestamp('not a date')).toBeNull();
  });
});

describe('formatTimestamp', () => {
  it('formats the date in the given timezone', () => {
    // 23:30 UTC is already the next day in Prague
    expect(formatTimestamp('2026-10-18 23:30:00+00', 'Europe/Prague')).toBe('19. 10. 2026');
  });

  it('handles empty values', () => {
    expect(formatTimestamp(null)).toBe('');
  });
});
