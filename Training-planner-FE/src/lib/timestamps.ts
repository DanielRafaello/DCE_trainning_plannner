/**
 * PostgreSQL timestamptz as text (`2026-10-18 08:00:00.123456+00`) – created_at / updated_at /
 * blocked_at. Parsed leniently, as the API docs ask: the space becomes `T`, a short `+00` offset
 * gets its minutes.
 */
export function parseTimestamp(value: string): Date | null {
  const iso = value.trim().replace(' ', 'T').replace(/([+-]\d{2})$/, '$1:00');
  const date = new Date(iso);
  return Number.isNaN(date.getTime()) ? null : date;
}

/** → `18. 10. 2026` */
export function formatTimestamp(value: string | null, timeZone?: string): string {
  if (!value) return '';
  const date = parseTimestamp(value);
  if (!date) return value;
  return new Intl.DateTimeFormat('cs-CZ', {
    day: 'numeric',
    month: 'numeric',
    year: 'numeric',
    timeZone
  }).format(date);
}
