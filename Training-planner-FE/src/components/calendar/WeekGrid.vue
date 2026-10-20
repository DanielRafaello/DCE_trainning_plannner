<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, useTemplateRef } from 'vue';
import EventCard from '@/components/calendar/EventCard.vue';
import { MINUTES_PER_DAY, formatDayMonth, minutesIntoDay, weekdayShort } from '@/lib/dates';
import { layoutDay } from '@/lib/eventLayout';
import {
  SNAP_MINUTES as DRAG_SNAP_MINUTES,
  movedTimes,
  resizedTimes,
  sameTimes,
  type EventTimes
} from '@/lib/reschedule';
import type { DayNote, Event } from '@/services/api/trainingPlannerAPI.schemas';

const HOUR_HEIGHT = 48;
const SNAP_MINUTES = 30;
const SCROLL_TO_HOUR = 7;
const DRAG_THRESHOLD_PX = 4;

const props = defineProps<{
  days: string[];
  events: Event[];
  notesByDate: Map<string, DayNote>;
  timeZone: string;
  today: string;
}>();

const emit = defineEmits<{
  create: [date: string, startMinutes: number];
  'open-event': [event: Event];
  'open-note': [date: string];
  /** Drag & drop finished – the parent saves the new times */
  reschedule: [event: Event, times: EventTimes];
}>();

const hours = Array.from({ length: 24 }, (_, i) => i);

// ─── drag & drop (mouse / pen only – on touch screens a drag would fight with scrolling) ───
interface DragState {
  event: Event;
  mode: 'move' | 'resize';
  pointerX: number;
  pointerY: number;
  fromColumn: number;
  active: boolean;
  deltaMinutes: number;
  dayOffset: number;
}

const drag = ref<DragState | null>(null);
// a drag ends with a click on whatever is under the pointer – that click must not open a dialog
let suppressClick = false;

function draggedTimes(state: DragState): EventTimes {
  return state.mode === 'move'
    ? movedTimes(state.event, state.deltaMinutes, state.dayOffset, props.timeZone)
    : resizedTimes(state.event, state.deltaMinutes);
}

// while dragging, the event is rendered at its new position
const displayedEvents = computed(() => {
  const state = drag.value;
  if (!state?.active) return props.events;
  const times = draggedTimes(state);
  return props.events.map((event) => (event.id === state.event.id ? { ...event, ...times } : event));
});

const columns = computed(() =>
  props.days.map((date) => ({
    date,
    segments: layoutDay(displayedEvents.value, date, props.timeZone)
  }))
);

const gridBody = useTemplateRef<HTMLDivElement>('gridBody');

function columnIndexAt(clientX: number): number {
  const columnElements = gridBody.value?.querySelectorAll<HTMLElement>('.day-column') ?? [];
  for (const element of columnElements) {
    const rect = element.getBoundingClientRect();
    if (clientX >= rect.left && clientX < rect.right) return Number(element.dataset.index);
  }
  return -1;
}

function onEventPointerDown(
  pointerEvent: PointerEvent,
  event: Event,
  columnIndex: number,
  mode: DragState['mode']
): void {
  if (pointerEvent.pointerType === 'touch' || pointerEvent.button !== 0) return;
  pointerEvent.preventDefault(); // no text selection while dragging
  drag.value = {
    event,
    mode,
    pointerX: pointerEvent.clientX,
    pointerY: pointerEvent.clientY,
    fromColumn: columnIndex,
    active: false,
    deltaMinutes: 0,
    dayOffset: 0
  };
  window.addEventListener('pointermove', onPointerMove);
  window.addEventListener('pointerup', onPointerUp);
  window.addEventListener('keydown', onDragKeydown);
}

function onPointerMove(pointerEvent: PointerEvent): void {
  const state = drag.value;
  if (!state) return;
  const dx = pointerEvent.clientX - state.pointerX;
  const dy = pointerEvent.clientY - state.pointerY;
  if (!state.active && Math.hypot(dx, dy) < DRAG_THRESHOLD_PX) return;

  state.active = true;
  state.deltaMinutes =
    Math.round(((dy / HOUR_HEIGHT) * 60) / DRAG_SNAP_MINUTES) * DRAG_SNAP_MINUTES;
  if (state.mode === 'move') {
    const column = columnIndexAt(pointerEvent.clientX);
    if (column !== -1) state.dayOffset = column - state.fromColumn;
  }
}

function stopDragging(): DragState | null {
  const state = drag.value;
  drag.value = null;
  window.removeEventListener('pointermove', onPointerMove);
  window.removeEventListener('pointerup', onPointerUp);
  window.removeEventListener('keydown', onDragKeydown);
  return state;
}

function onPointerUp(): void {
  const state = stopDragging();
  if (!state?.active) return;

  suppressClick = true;
  setTimeout(() => (suppressClick = false));

  const times = draggedTimes(state);
  if (!sameTimes(state.event, times)) emit('reschedule', state.event, times);
}

function onDragKeydown(keyboardEvent: KeyboardEvent): void {
  if (keyboardEvent.key === 'Escape' && drag.value?.active) {
    stopDragging();
    suppressClick = true;
    setTimeout(() => (suppressClick = false));
  }
}

function onOpenEvent(event: Event): void {
  if (!suppressClick) emit('open-event', event);
}

// current-time line, refreshed every minute
const now = ref(new Date());
let timer: ReturnType<typeof setInterval> | undefined;
const nowMinutes = computed(() => minutesIntoDay(now.value, props.today, props.timeZone));

const scroller = useTemplateRef<HTMLDivElement>('scroller');

onMounted(() => {
  timer = setInterval(() => (now.value = new Date()), 60_000);
  if (scroller.value) scroller.value.scrollTop = SCROLL_TO_HOUR * HOUR_HEIGHT - 8;
});
onBeforeUnmount(() => {
  clearInterval(timer);
  stopDragging();
});

function onColumnClick(date: string, clickEvent: MouseEvent): void {
  if (suppressClick) return;
  const column = clickEvent.currentTarget as HTMLElement;
  const y = clickEvent.clientY - column.getBoundingClientRect().top;
  const minutes = Math.floor((y / HOUR_HEIGHT) * (60 / SNAP_MINUTES)) * SNAP_MINUTES;
  emit('create', date, Math.min(Math.max(minutes, 0), MINUTES_PER_DAY - SNAP_MINUTES));
}

const px = (minutes: number): string => `${(minutes / 60) * HOUR_HEIGHT}px`;
</script>

<template>
  <div class="week-grid" :style="{ '--days': days.length, '--hour-height': `${HOUR_HEIGHT}px` }">
    <div class="grid-header">
      <div class="gutter" />
      <div
        v-for="date in days"
        :key="date"
        class="day-header"
        :class="{ 'is-today': date === today }"
      >
        <span class="day-name">{{ weekdayShort(date) }}</span>
        <span class="day-date">{{ formatDayMonth(date) }}</span>
        <button
          type="button"
          class="note-button"
          :class="{ 'has-note': notesByDate.has(date) }"
          :title="notesByDate.get(date)?.content ?? 'Přidat poznámku k tomuto dni'"
          :aria-label="notesByDate.has(date) ? 'Upravit poznámku ke dni' : 'Přidat poznámku ke dni'"
          @click="emit('open-note', date)"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" />
            <path d="M14 3v6h6M8 13h8M8 17h5" />
          </svg>
        </button>
      </div>
    </div>

    <div ref="scroller" class="grid-scroll">
      <div ref="gridBody" class="grid-body" :class="{ 'is-dragging': drag?.active }">
        <div class="gutter">
          <span v-for="hour in hours" :key="hour" class="hour-label" :style="{ top: px(hour * 60) }">
            {{ hour === 0 ? '' : `${hour}:00` }}
          </span>
        </div>

        <div
          v-for="(column, columnIndex) in columns"
          :key="column.date"
          class="day-column"
          :class="{ 'is-today': column.date === today }"
          :data-index="columnIndex"
          @click="onColumnClick(column.date, $event)"
        >
          <div
            v-for="segment in column.segments"
            :key="segment.event.id"
            class="event-slot"
            :class="{ 'is-dragged': drag?.active && drag.event.id === segment.event.id }"
            :style="{
              top: px(segment.start),
              height: `max(${px(segment.end - segment.start)}, 22px)`,
              left: `calc(${(segment.column / segment.columns) * 100}% + 2px)`,
              width: `calc(${100 / segment.columns}% - 4px)`
            }"
            @pointerdown="onEventPointerDown($event, segment.event, columnIndex, 'move')"
          >
            <EventCard
              :segment="segment"
              :time-zone="timeZone"
              :hour-height="HOUR_HEIGHT"
              @open="onOpenEvent(segment.event)"
            />
            <div
              v-if="!segment.continuesAfter"
              class="resize-handle"
              aria-hidden="true"
              @pointerdown.stop="onEventPointerDown($event, segment.event, columnIndex, 'resize')"
            />
          </div>

          <div
            v-if="column.date === today"
            class="now-line"
            :style="{ top: px(nowMinutes) }"
            aria-hidden="true"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.week-grid {
  --gutter-width: 48px;

  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: color-mix(in oklab, var(--color-surface) 70%, transparent);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
}

.grid-header,
.grid-body {
  display: grid;
  grid-template-columns: var(--gutter-width) repeat(var(--days), minmax(0, 1fr));
}

.grid-header {
  border-bottom: 1px solid var(--color-border);
}

.day-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-1);
  min-width: 0;
  padding: var(--space-2) var(--space-1);
  font-size: var(--font-size-sm);
}

.day-name {
  color: var(--color-text-muted);
}

.day-date {
  font-weight: 600;
}

.day-header.is-today .day-date {
  padding: 0 var(--space-2);
  color: var(--color-on-primary);
  background: var(--color-primary);
  border-radius: var(--radius-full);
}

.note-button {
  display: inline-flex;
  padding: var(--space-1);
  color: var(--color-text-muted);
  background: none;
  border: none;
  border-radius: var(--radius-sm);
  transition:
    color var(--transition),
    background-color var(--transition);
}

.note-button:hover {
  color: var(--color-text);
  background: var(--color-primary-soft);
}

.note-button.has-note {
  color: var(--color-primary);
  background: var(--color-primary-soft);
}

.grid-scroll {
  height: clamp(420px, calc(100dvh - 210px), 1400px);
  overflow-y: auto;
  overscroll-behavior: contain;
}

.grid-body {
  height: calc(24 * var(--hour-height));
}

.gutter {
  position: relative;
}

.hour-label {
  position: absolute;
  right: var(--space-2);
  transform: translateY(-50%);
  font-size: var(--font-size-xs);
  color: var(--color-text-subtle);
}

.day-column {
  position: relative;
  border-left: 1px solid var(--color-border);
  /* hour lines + fainter half-hour lines */
  background-image:
    linear-gradient(var(--color-border) 1px, transparent 1px),
    linear-gradient(color-mix(in oklab, var(--color-border) 45%, transparent) 1px, transparent 1px);
  background-size:
    100% var(--hour-height),
    100% calc(var(--hour-height) / 2);
  cursor: cell;
}

.day-column.is-today {
  background-color: color-mix(in oklab, var(--color-primary-soft) 45%, transparent);
}

.event-slot {
  position: absolute;
  z-index: 1;
  padding-bottom: 1px;
}

.event-slot:hover {
  z-index: 2;
}

.event-slot.is-dragged {
  z-index: 4;
  filter: drop-shadow(0 10px 18px rgba(31, 36, 48, 0.25));
}

.grid-body.is-dragging,
.grid-body.is-dragging * {
  cursor: grabbing !important;
  user-select: none;
}

.resize-handle {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 7px;
  cursor: ns-resize;
}

.resize-handle::after {
  content: '';
  position: absolute;
  left: 50%;
  bottom: 3px;
  width: 18px;
  height: 2px;
  margin-left: -9px;
  border-radius: var(--radius-full);
  background: var(--color-text-subtle);
  opacity: 0;
  transition: opacity var(--transition);
}

.event-slot:hover .resize-handle::after {
  opacity: 0.8;
}

/* touch screens: no drag handles, editing goes through the dialog */
@media (pointer: coarse) {
  .resize-handle {
    display: none;
  }
}

.now-line {
  position: absolute;
  left: 0;
  right: 0;
  z-index: 3;
  height: 2px;
  background: var(--color-danger);
  pointer-events: none;
}

.now-line::before {
  content: '';
  position: absolute;
  left: -4px;
  top: -3px;
  width: 8px;
  height: 8px;
  border-radius: var(--radius-full);
  background: var(--color-danger);
}

@media (max-width: 1023px) {
  .grid-scroll {
    height: clamp(380px, calc(100dvh - 290px), 1400px);
  }
}
</style>
