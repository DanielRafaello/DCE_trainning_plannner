import { describe, expect, it } from 'vitest';
import { bucketLabel, customPeriod, groupByForRange, periodOf, shiftAnchor } from '@/lib/periods';

describe('periodOf', () => {
  it('week starts on Monday', () => {
    expect(periodOf('week', '2026-10-22')).toMatchObject({
      from: '2026-10-19',
      to: '2026-10-26',
      groupBy: 'day'
    });
  });

  it('month covers the whole calendar month', () => {
    expect(periodOf('month', '2026-12-15')).toMatchObject({
      from: '2026-12-01',
      to: '2027-01-01',
      label: 'prosinec 2026'
    });
  });

  it('year is grouped by month', () => {
    expect(periodOf('year', '2026-05-05')).toMatchObject({
      from: '2026-01-01',
      to: '2027-01-01',
      groupBy: 'month'
    });
  });
});

describe('shiftAnchor', () => {
  it('moves months across the year boundary', () => {
    expect(shiftAnchor('month', '2026-01-31', -1)).toBe('2025-12-01');
    expect(shiftAnchor('month', '2026-12-01', 1)).toBe('2027-01-01');
  });

  it('moves weeks and years', () => {
    expect(shiftAnchor('week', '2026-10-20', 1)).toBe('2026-10-27');
    expect(shiftAnchor('year', '2026-10-20', -1)).toBe('2025-01-01');
  });
});

describe('custom periods', () => {
  it('makes the end date inclusive', () => {
    expect(customPeriod('2026-10-01', '2026-10-31')).toMatchObject({
      from: '2026-10-01',
      to: '2026-11-01',
      groupBy: 'day'
    });
  });

  it('keeps the bucket count under the API limit', () => {
    expect(groupByForRange('2026-01-01', '2026-03-01')).toBe('day');
    expect(groupByForRange('2026-01-01', '2027-01-01')).toBe('week');
    expect(groupByForRange('2024-01-01', '2027-01-01')).toBe('month');
  });
});

describe('bucketLabel', () => {
  it('formats each granularity', () => {
    expect(bucketLabel('2026-10-05', 'day')).toBe('5');
    expect(bucketLabel('2026-10-05', 'week')).toBe('5. 10.');
    expect(bucketLabel('2026-10-01', 'month')).toBe('říj');
  });
});
