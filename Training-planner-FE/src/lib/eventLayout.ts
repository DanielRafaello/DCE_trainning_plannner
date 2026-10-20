import { MINUTES_PER_DAY, minutesIntoDay } from '@/lib/dates';
import type { Event } from '@/services/api/trainingPlannerAPI.schemas';

/** The part of an event that falls into one day column. */
export interface DaySegment {
  event: Event;
  /** Minutes from the day's midnight, clipped to 0–1440 */
  start: number;
  end: number;
  /** Side-by-side placement of overlapping events */
  column: number;
  columns: number;
  /** The event started the day before / continues into the next day (crosses midnight) */
  continuesBefore: boolean;
  continuesAfter: boolean;
}

/**
 * Events of one day, laid out like a calendar app: overlapping events form a cluster and share
 * its width, each one in the first column that is free at its start.
 */
export function layoutDay(events: Event[], dateKey: string, timeZone: string): DaySegment[] {
  const segments: DaySegment[] = [];

  for (const event of events) {
    const rawStart = minutesIntoDay(event.start_at, dateKey, timeZone);
    const rawEnd = minutesIntoDay(event.end_at, dateKey, timeZone);
    if (rawEnd <= 0 || rawStart >= MINUTES_PER_DAY) continue;

    segments.push({
      event,
      start: Math.max(rawStart, 0),
      end: Math.min(rawEnd, MINUTES_PER_DAY),
      column: 0,
      columns: 1,
      continuesBefore: rawStart < 0,
      continuesAfter: rawEnd > MINUTES_PER_DAY
    });
  }

  // earlier first, longer first on the same start
  segments.sort((a, b) => a.start - b.start || b.end - a.end);

  let cluster: DaySegment[] = [];
  let columnEnds: number[] = [];
  let clusterEnd = -1;

  const closeCluster = (): void => {
    for (const segment of cluster) segment.columns = columnEnds.length;
    cluster = [];
    columnEnds = [];
  };

  for (const segment of segments) {
    if (segment.start >= clusterEnd) closeCluster();

    let column = columnEnds.findIndex((end) => end <= segment.start);
    if (column === -1) {
      column = columnEnds.length;
      columnEnds.push(segment.end);
    } else {
      columnEnds[column] = segment.end;
    }

    segment.column = column;
    cluster.push(segment);
    clusterEnd = Math.max(clusterEnd, segment.end);
  }
  closeCluster();

  return segments;
}
