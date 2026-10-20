<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import DayNoteDialog from '@/components/calendar/DayNoteDialog.vue';
import EventDialog from '@/components/calendar/EventDialog.vue';
import WeekGrid from '@/components/calendar/WeekGrid.vue';
import WeekNotes from '@/components/calendar/WeekNotes.vue';
import WeekSummary from '@/components/calendar/WeekSummary.vue';
import WeekToolbar from '@/components/calendar/WeekToolbar.vue';
import FormAlert from '@/components/form/FormAlert.vue';
import { useMediaQuery } from '@/composables/useMediaQuery';
import { useWeek } from '@/composables/useWeek';
import { apiErrorText, parseApiError } from '@/lib/apiErrors';
import type { EventTimes } from '@/lib/reschedule';
import { getEvents } from '@/services/api';
import {
  MINUTES_PER_DAY,
  addDays,
  formatDayMonth,
  formatWeekRange,
  minutesIntoDay,
  toDateKey,
  todayKey,
  weekdayShort
} from '@/lib/dates';
import type { DayNote, Event } from '@/services/api/trainingPlannerAPI.schemas';
import { useAuthStore } from '@/stores/auth';
import { useCatalogStore } from '@/stores/catalog';

const auth = useAuthStore();
const catalog = useCatalogStore();
const route = useRoute();
const router = useRouter();

const timeZone = computed(
  () => auth.user?.timezone ?? Intl.DateTimeFormat().resolvedOptions().timeZone
);
const today = computed(() => todayKey(timeZone.value));

// ?date=YYYY-MM-DD – any day of the shown week; missing = today
const selectedDate = computed(() => {
  const date = route.query.date;
  return typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date) ? date : today.value;
});

function goTo(date: string): void {
  void router.replace({ query: date === today.value ? {} : { date } });
}

const { week, days, events, notesByDate, loading, error, reload, upsertEvent, removeEvent, setNote } =
  useWeek(selectedDate, timeZone);

// phones and tablets show one day at a time, picked in the day strip
const compact = useMediaQuery('(max-width: 1023px)');
const visibleDays = computed(() => {
  if (!compact.value) return days.value;
  return days.value.includes(selectedDate.value) ? [selectedDate.value] : days.value.slice(0, 1);
});

const title = computed(() => {
  const first = days.value[0];
  const last = days.value[6];
  return first && last ? formatWeekRange(first, last) : 'Načítám…';
});

const daysWithEvents = computed(
  () => new Set(events.value.map((event) => toDateKey(event.start_at, timeZone.value)))
);

const catalogError = ref('');
async function loadCatalog(): Promise<void> {
  catalogError.value = '';
  try {
    await catalog.load();
  } catch (e) {
    catalogError.value = parseApiError(e).message;
  }
}
onMounted(loadCatalog);

// ─── dialogs ───────────────────────────────────────────
const eventDialog = ref<{ event?: Event; date?: string; startMinutes?: number } | null>(null);
const noteDialogDate = ref<string | null>(null);

function openCreate(date: string, startMinutes: number): void {
  eventDialog.value = { date, startMinutes };
}

// toolbar button: the selected day, starting at the next full hour (or 8:00 on other days)
function openCreateFromToolbar(): void {
  const date = days.value.includes(selectedDate.value) ? selectedDate.value : (days.value[0] ?? today.value);
  const nextHour = Math.ceil(minutesIntoDay(new Date(), today.value, timeZone.value) / 60) * 60;
  const start = date === today.value ? Math.min(nextHour, MINUTES_PER_DAY - 60) : 8 * 60;
  openCreate(date, start);
}

function onEventSaved(event: Event): void {
  upsertEvent(event);
  eventDialog.value = null;
}

function onEventDeleted(id: number): void {
  removeEvent(id);
  eventDialog.value = null;
}

// drag & drop: show the new position right away, roll back if the backend refuses it
const rescheduleError = ref('');

async function onReschedule(event: Event, times: EventTimes): Promise<void> {
  rescheduleError.value = '';
  upsertEvent({ ...event, ...times });
  try {
    const response = await getEvents().updateEvent(event.id, times);
    upsertEvent(response.event);
  } catch (e) {
    upsertEvent(event);
    rescheduleError.value = apiErrorText(e);
  }
}

function onNoteSaved(date: string, note: DayNote | null): void {
  setNote(date, note);
  noteDialogDate.value = null;
}
</script>

<template>
  <div class="calendar-view">
    <div class="calendar-main stack">
      <WeekToolbar
        :title="title"
        :is-current-week="days.includes(today)"
        :loading="loading"
        @prev="goTo(addDays(selectedDate, -7))"
        @next="goTo(addDays(selectedDate, 7))"
        @today="goTo(today)"
        @create="openCreateFromToolbar"
      />

      <FormAlert :message="error || catalogError || rescheduleError" />
      <button
        v-if="error || catalogError"
        type="button"
        class="btn btn-secondary"
        @click="error ? reload() : loadCatalog()"
      >
        Zkusit znovu
      </button>

      <nav v-if="compact && days.length" class="day-strip" aria-label="Den v týdnu">
        <button
          v-for="date in days"
          :key="date"
          type="button"
          class="day-chip"
          :class="{ active: date === selectedDate, today: date === today }"
          :aria-current="date === selectedDate ? 'date' : undefined"
          @click="goTo(date)"
        >
          <span class="chip-name">{{ weekdayShort(date) }}</span>
          <span class="chip-date">{{ formatDayMonth(date) }}</span>
          <span class="chip-dot" :class="{ visible: daysWithEvents.has(date) }" />
        </button>
      </nav>

      <WeekGrid
        v-if="days.length"
        :days="visibleDays"
        :events="events"
        :notes-by-date="notesByDate"
        :time-zone="timeZone"
        :today="today"
        @create="openCreate"
        @open-event="(event) => (eventDialog = { event })"
        @open-note="(date) => (noteDialogDate = date)"
        @reschedule="onReschedule"
      />
    </div>

    <aside v-if="week" class="calendar-side stack">
      <WeekSummary :week="week" :events="events" />
      <WeekNotes
        :days="days"
        :notes-by-date="notesByDate"
        :default-date="days.includes(selectedDate) ? selectedDate : (days[0] ?? today)"
        @open="(date) => (noteDialogDate = date)"
      />
    </aside>

    <EventDialog
      v-if="eventDialog"
      :event="eventDialog.event"
      :date="eventDialog.date"
      :start-minutes="eventDialog.startMinutes"
      :time-zone="timeZone"
      @close="eventDialog = null"
      @saved="onEventSaved"
      @deleted="onEventDeleted"
    />

    <DayNoteDialog
      v-if="noteDialogDate"
      :date="noteDialogDate"
      :note="notesByDate.get(noteDialogDate) ?? null"
      @close="noteDialogDate = null"
      @saved="onNoteSaved"
    />
  </div>
</template>

<style scoped>
.calendar-view {
  display: grid;
  gap: var(--space-4);
  align-items: start;
}

.calendar-main {
  min-width: 0;
}

/* Wide screens: timetable + side panel next to each other */
@media (min-width: 1200px) {
  .calendar-view {
    grid-template-columns: minmax(0, 1fr) 300px;
  }

  .calendar-side {
    position: sticky;
    top: calc(var(--header-height) + var(--space-5));
  }
}

/* Tablet: the side cards sit next to each other under the timetable */
@media (min-width: 640px) and (max-width: 1199px) {
  .calendar-side {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: start;
  }
}

.day-strip {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: var(--space-1);
}

.day-chip {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  padding: var(--space-2) 0;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  transition:
    background-color var(--transition),
    border-color var(--transition);
}

.chip-name {
  font-size: var(--font-size-xs);
  color: var(--color-text-muted);
}

.chip-date {
  font-size: var(--font-size-sm);
  font-weight: 600;
}

.day-chip.today .chip-date {
  color: var(--color-primary);
}

.day-chip.active {
  background: var(--color-primary-soft);
  border-color: var(--color-primary);
}

.chip-dot {
  width: 5px;
  height: 5px;
  border-radius: var(--radius-full);
  background: transparent;
}

.chip-dot.visible {
  background: var(--color-primary);
}

@media (max-width: 379px) {
  .chip-date {
    font-size: var(--font-size-xs);
  }
}
</style>
