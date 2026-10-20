import { describe, expect, it } from 'vitest';
import { layoutDay } from '@/lib/eventLayout';
import type { Event } from '@/services/api/trainingPlannerAPI.schemas';

const PRAGUE = 'Europe/Prague';
const DAY = '2026-10-20';

function event(id: number, start: string, end: string): Event {
  return {
    id,
    category_id: 1,
    subcategory_id: null,
    title: `Event ${id}`,
    description: null,
    start_at: start,
    end_at: end,
    duration_minutes: 0,
    status: 'planned',
    created_at: '',
    updated_at: ''
  };
}

const at = (time: string, day = DAY): string => `${day}T${time}:00+02:00`;

describe('layoutDay', () => {
  it('gives non-overlapping events the full width', () => {
    const segments = layoutDay(
      [event(1, at('08:00'), at('09:00')), event(2, at('09:00'), at('10:00'))],
      DAY,
      PRAGUE
    );
    expect(segments.map((s) => [s.column, s.columns])).toEqual([
      [0, 1],
      [0, 1]
    ]);
  });

  it('puts overlapping events side by side', () => {
    const segments = layoutDay(
      [
        event(1, at('08:00'), at('10:00')),
        event(2, at('09:00'), at('11:00')),
        event(3, at('10:00'), at('12:00'))
      ],
      DAY,
      PRAGUE
    );
    const byId = Object.fromEntries(segments.map((s) => [s.event.id, [s.column, s.columns]]));
    // 3 reuses column 0 freed by 1, the cluster still needs 2 columns
    expect(byId).toEqual({ 1: [0, 2], 2: [1, 2], 3: [0, 2] });
  });

  it('clips an event crossing midnight on both days', () => {
    const crossing = event(1, at('22:00'), at('02:00', '2026-10-21'));

    const [today] = layoutDay([crossing], DAY, PRAGUE);
    expect(today).toMatchObject({ start: 22 * 60, end: 24 * 60, continuesAfter: true });

    const [tomorrow] = layoutDay([crossing], '2026-10-21', PRAGUE);
    expect(tomorrow).toMatchObject({ start: 0, end: 2 * 60, continuesBefore: true });
  });

  it('skips events of other days', () => {
    expect(layoutDay([event(1, at('08:00', '2026-10-21'), at('09:00', '2026-10-21'))], DAY, PRAGUE)).toEqual([]);
  });
});
