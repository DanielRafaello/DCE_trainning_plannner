import { addDays, formatDayMonth, formatWeekRange, weekdayIndex } from '@/lib/dates';
import type { StatsGranularity } from '@/services/api/trainingPlannerAPI.schemas';

// Statistics periods (FR-STAT-01). Ranges are calendar dates in the user's timezone,
// `to` is exclusive – the same [from, to) the API uses.

export type PeriodKind = 'week' | 'month' | 'year' | 'custom';

export interface Period {
  from: string;
  /** exclusive */
  to: string;
  groupBy: StatsGranularity;
  label: string;
}

const MONTHS = [
  'leden',
  'únor',
  'březen',
  'duben',
  'květen',
  'červen',
  'červenec',
  'srpen',
  'září',
  'říjen',
  'listopad',
  'prosinec'
];

const pad = (value: number): string => String(value).padStart(2, '0');

function splitKey(dateKey: string): [number, number] {
  const [year = 1970, month = 1] = dateKey.split('-').map(Number);
  return [year, month];
}

function monthStart(year: number, month: number): string {
  // month may overflow (13 → January of the next year, 0 → December of the previous one)
  const normalizedYear = year + Math.floor((month - 1) / 12);
  const normalizedMonth = ((((month - 1) % 12) + 12) % 12) + 1;
  return `${normalizedYear}-${pad(normalizedMonth)}-01`;
}

export function daysBetween(from: string, to: string): number {
  return Math.round((Date.parse(`${to}T00:00:00Z`) - Date.parse(`${from}T00:00:00Z`)) / 86_400_000);
}

/** Auto grouping for a custom range – the API allows at most 400 buckets. */
export function groupByForRange(from: string, to: string): StatsGranularity {
  const days = daysBetween(from, to);
  if (days <= 62) return 'day';
  if (days <= 366) return 'week';
  return 'month';
}

/** The week / month / year containing `anchor`. */
export function periodOf(kind: Exclude<PeriodKind, 'custom'>, anchor: string): Period {
  const [year, month] = splitKey(anchor);

  if (kind === 'week') {
    const from = addDays(anchor, -weekdayIndex(anchor));
    const to = addDays(from, 7);
    return { from, to, groupBy: 'day', label: formatWeekRange(from, addDays(to, -1)) };
  }

  if (kind === 'month') {
    return {
      from: monthStart(year, month),
      to: monthStart(year, month + 1),
      groupBy: 'day',
      label: `${MONTHS[month - 1] ?? ''} ${year}`
    };
  }

  return { from: `${year}-01-01`, to: `${year + 1}-01-01`, groupBy: 'month', label: String(year) };
}

export function customPeriod(from: string, toInclusive: string): Period {
  const to = addDays(toInclusive, 1);
  return {
    from,
    to,
    groupBy: groupByForRange(from, to),
    label: `${formatDayMonth(from)} ${splitKey(from)[0]} – ${formatDayMonth(toInclusive)} ${splitKey(toInclusive)[0]}`
  };
}

/** Anchor of the previous / next period. */
export function shiftAnchor(kind: Exclude<PeriodKind, 'custom'>, anchor: string, direction: 1 | -1): string {
  const [year, month] = splitKey(anchor);
  if (kind === 'week') return addDays(anchor, 7 * direction);
  if (kind === 'month') return monthStart(year, month + direction);
  return `${year + direction}-01-01`;
}

/** Short label of one timeline bucket (its local start date). */
export function bucketLabel(bucket: string, groupBy: StatsGranularity): string {
  const [, month] = splitKey(bucket);
  if (groupBy === 'month') return MONTHS[month - 1]?.slice(0, 3) ?? bucket;
  if (groupBy === 'week') return formatDayMonth(bucket);
  return String(Number(bucket.slice(8, 10))); // day of the month
}

/** Long label for the tooltip. */
export function bucketTitle(bucket: string, groupBy: StatsGranularity): string {
  const [year, month] = splitKey(bucket);
  if (groupBy === 'month') return `${MONTHS[month - 1] ?? ''} ${year}`;
  if (groupBy === 'week') return `Týden od ${formatDayMonth(bucket)} ${year}`;
  return `${formatDayMonth(bucket)} ${year}`;
}
