// Date helpers that work in the USER'S timezone (User.timezone), not the browser's –
// week boundaries and statistics on the backend are computed in that timezone too.
// A "date key" is a plain calendar date `YYYY-MM-DD`.

const MINUTE_MS = 60_000;
const DAY_MS = 24 * 60 * MINUTE_MS;

export const MINUTES_PER_DAY = 24 * 60;

interface ZonedParts {
  year: number;
  month: number;
  day: number;
  hour: number;
  minute: number;
}

const partsFormatters = new Map<string, Intl.DateTimeFormat>();

function partsFormatter(timeZone: string): Intl.DateTimeFormat {
  let formatter = partsFormatters.get(timeZone);
  if (!formatter) {
    formatter = new Intl.DateTimeFormat('en-US', {
      timeZone,
      hourCycle: 'h23',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
    partsFormatters.set(timeZone, formatter);
  }
  return formatter;
}

function zonedParts(date: Date, timeZone: string): ZonedParts {
  const parts: Record<string, number> = {};
  for (const part of partsFormatter(timeZone).formatToParts(date)) {
    if (part.type !== 'literal') parts[part.type] = Number(part.value);
  }
  return {
    year: parts.year ?? 0,
    month: parts.month ?? 1,
    day: parts.day ?? 1,
    hour: parts.hour ?? 0,
    minute: parts.minute ?? 0
  };
}

/** Offset of `timeZone` from UTC at the given instant, in ms (Prague in summer = +2 h). */
function offsetMs(date: Date, timeZone: string): number {
  const p = zonedParts(date, timeZone);
  const asUtc = Date.UTC(p.year, p.month - 1, p.day, p.hour, p.minute);
  return asUtc - (date.getTime() - (date.getTime() % MINUTE_MS));
}

const pad = (value: number): string => String(value).padStart(2, '0');

function parseDateKey(dateKey: string): [number, number, number] {
  const [year = 1970, month = 1, day = 1] = dateKey.split('-').map(Number);
  return [year, month, day];
}

/** Calendar date of an instant in the given timezone. */
export function toDateKey(value: string | Date, timeZone: string): string {
  const p = zonedParts(new Date(value), timeZone);
  return `${p.year}-${pad(p.month)}-${pad(p.day)}`;
}

/** Wall-clock time `HH:MM` of an instant in the given timezone. */
export function toTimeValue(value: string | Date, timeZone: string): string {
  const p = zonedParts(new Date(value), timeZone);
  return `${pad(p.hour)}:${pad(p.minute)}`;
}

export function todayKey(timeZone: string): string {
  return toDateKey(new Date(), timeZone);
}

export function addDays(dateKey: string, days: number): string {
  const [year, month, day] = parseDateKey(dateKey);
  const date = new Date(Date.UTC(year, month - 1, day) + days * DAY_MS);
  return `${date.getUTCFullYear()}-${pad(date.getUTCMonth() + 1)}-${pad(date.getUTCDate())}`;
}

/** Monday-based weekday, 0 = Monday … 6 = Sunday. */
export function weekdayIndex(dateKey: string): number {
  const [year, month, day] = parseDateKey(dateKey);
  return (new Date(Date.UTC(year, month - 1, day)).getUTCDay() + 6) % 7;
}

/**
 * The instant of a wall-clock time (`HH:MM`) on a date in the given timezone.
 * Runs the offset lookup twice so that days with a DST switch come out right.
 */
export function zonedToDate(dateKey: string, time: string, timeZone: string): Date {
  const [year, month, day] = parseDateKey(dateKey);
  const [hour = 0, minute = 0] = time.split(':').map(Number);
  const wallClock = Date.UTC(year, month - 1, day, hour, minute);

  let instant = wallClock - offsetMs(new Date(wallClock), timeZone);
  instant = wallClock - offsetMs(new Date(instant), timeZone);
  return new Date(instant);
}

/** Minutes from the start of `dateKey` (in the timezone) to the instant – can be < 0 or > 1440. */
export function minutesIntoDay(value: string | Date, dateKey: string, timeZone: string): number {
  const dayStart = zonedToDate(dateKey, '00:00', timeZone).getTime();
  return Math.round((new Date(value).getTime() - dayStart) / MINUTE_MS);
}

export function minutesToTime(minutes: number): string {
  const clamped = ((minutes % MINUTES_PER_DAY) + MINUTES_PER_DAY) % MINUTES_PER_DAY;
  return `${pad(Math.floor(clamped / 60))}:${pad(clamped % 60)}`;
}

export function formatDuration(minutes: number): string {
  const hours = Math.floor(minutes / 60);
  const rest = minutes % 60;
  if (hours === 0) return `${rest} min`;
  return rest === 0 ? `${hours} h` : `${hours} h ${rest} min`;
}

const WEEKDAYS_SHORT = ['Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne'];
const WEEKDAYS_LONG = ['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle'];

export function weekdayShort(dateKey: string): string {
  return WEEKDAYS_SHORT[weekdayIndex(dateKey)] ?? '';
}

export function weekdayLong(dateKey: string): string {
  return WEEKDAYS_LONG[weekdayIndex(dateKey)] ?? '';
}

/** `20. 10.` */
export function formatDayMonth(dateKey: string): string {
  const [, month, day] = parseDateKey(dateKey);
  return `${day}. ${month}.`;
}

/** `20. 10. – 26. 10. 2026` */
export function formatWeekRange(firstDay: string, lastDay: string): string {
  const [year] = parseDateKey(lastDay);
  return `${formatDayMonth(firstDay)} – ${formatDayMonth(lastDay)} ${year}`;
}

/** All IANA timezones the browser knows, with the current one guaranteed to be included. */
export function timezoneOptions(current: string): string[] {
  // typed by hand – Intl.supportedValuesOf is missing from the lib of the test tsconfig
  const intl = Intl as { supportedValuesOf?: (key: 'timeZone') => string[] };
  const zones = intl.supportedValuesOf?.('timeZone') ?? [];
  return zones.includes(current) ? zones : [current, ...zones];
}
