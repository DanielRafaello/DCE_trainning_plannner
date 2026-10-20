import { computed, ref, watch, type Ref } from 'vue';
import { parseApiError } from '@/lib/apiErrors';
import { addDays, toDateKey } from '@/lib/dates';
import { getWeek } from '@/services/api';
import type { DayNote, Event, WeekInfo } from '@/services/api/trainingPlannerAPI.schemas';

/** Loads the week containing `date` (GET /weeks/{date}) and keeps it in sync with local edits. */
export function useWeek(date: Ref<string>, timeZone: Ref<string>) {
  const week = ref<WeekInfo | null>(null);
  const events = ref<Event[]>([]);
  const notes = ref<DayNote[]>([]);
  const loading = ref(false);
  const error = ref('');

  let loadedTimeZone = '';
  let requestId = 0; // only the latest request may write its result (fast prev/next clicking)

  /** Monday … Sunday as `YYYY-MM-DD` */
  const days = computed<string[]>(() => {
    if (!week.value) return [];
    const monday = toDateKey(week.value.start, timeZone.value);
    return Array.from({ length: 7 }, (_, i) => addDays(monday, i));
  });

  const notesByDate = computed(() => new Map(notes.value.map((note) => [note.date, note])));

  async function load(): Promise<void> {
    const id = ++requestId;
    loading.value = true;
    error.value = '';
    try {
      const response = await getWeek().getWeek(date.value);
      if (id !== requestId) return;
      week.value = response.week;
      events.value = response.events;
      notes.value = response.notes;
      loadedTimeZone = timeZone.value;
    } catch (e) {
      if (id === requestId) error.value = parseApiError(e).message;
    } finally {
      if (id === requestId) loading.value = false;
    }
  }

  // switching days inside the loaded week (phone day picker) needs no request
  watch(
    [date, timeZone],
    () => {
      if (days.value.includes(date.value) && loadedTimeZone === timeZone.value) return;
      void load();
    },
    { immediate: true }
  );

  function overlapsWeek(event: Event): boolean {
    if (!week.value) return false;
    return (
      new Date(event.start_at) < new Date(week.value.end) &&
      new Date(event.end_at) > new Date(week.value.start)
    );
  }

  /** After create / update – an event moved to another week disappears from this one. */
  function upsertEvent(event: Event): void {
    const others = events.value.filter((e) => e.id !== event.id);
    events.value = overlapsWeek(event) ? [...others, event] : others;
  }

  function removeEvent(id: number): void {
    events.value = events.value.filter((e) => e.id !== id);
  }

  function setNote(dateKey: string, note: DayNote | null): void {
    const others = notes.value.filter((n) => n.date !== dateKey);
    notes.value = note ? [...others, note] : others;
  }

  return {
    week,
    days,
    events,
    notesByDate,
    loading,
    error,
    reload: load,
    upsertEvent,
    removeEvent,
    setNote
  };
}
