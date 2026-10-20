import { MINUTES_PER_DAY, addDays, minutesToTime, toDateKey, toTimeValue, zonedToDate } from '@/lib/dates';
import type { Event } from '@/services/api/trainingPlannerAPI.schemas';

export const SNAP_MINUTES = 15;
const MINUTE_MS = 60_000;

export interface EventTimes {
  start_at: string;
  end_at: string;
}

/**
 * Drag & drop move: shifts the start by whole days + minutes on the wall clock of the user's
 * timezone (so 9:00 stays 9:00 across a DST switch) and keeps the duration.
 */
export function movedTimes(
  event: Event,
  deltaMinutes: number,
  dayOffset: number,
  timeZone: string
): EventTimes {
  const [hour = 0, minute = 0] = toTimeValue(event.start_at, timeZone).split(':').map(Number);
  const total = hour * 60 + minute + deltaMinutes;
  const extraDays = Math.floor(total / MINUTES_PER_DAY);

  const start = zonedToDate(
    addDays(toDateKey(event.start_at, timeZone), dayOffset + extraDays),
    minutesToTime(total - extraDays * MINUTES_PER_DAY),
    timeZone
  );
  const duration = new Date(event.end_at).getTime() - new Date(event.start_at).getTime();

  return {
    start_at: start.toISOString(),
    end_at: new Date(start.getTime() + duration).toISOString()
  };
}

/** Drag the bottom edge: changes only the end, between SNAP_MINUTES and 24 h (FR-EVT-02). */
export function resizedTimes(event: Event, deltaMinutes: number): EventTimes {
  const start = new Date(event.start_at).getTime();
  const end = new Date(event.end_at).getTime();
  const minutes = Math.round((end - start) / MINUTE_MS) + deltaMinutes;
  const clamped = Math.min(Math.max(minutes, SNAP_MINUTES), MINUTES_PER_DAY);

  return {
    start_at: new Date(start).toISOString(),
    end_at: new Date(start + clamped * MINUTE_MS).toISOString()
  };
}

export function sameTimes(event: Event, times: EventTimes): boolean {
  return (
    new Date(event.start_at).getTime() === new Date(times.start_at).getTime() &&
    new Date(event.end_at).getTime() === new Date(times.end_at).getTime()
  );
}
