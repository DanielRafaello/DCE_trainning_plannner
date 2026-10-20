<script setup lang="ts">
import { computed } from 'vue';
import { formatDayMonth, weekdayLong } from '@/lib/dates';
import type { DayNote } from '@/services/api/trainingPlannerAPI.schemas';

const props = defineProps<{
  days: string[];
  notesByDate: Map<string, DayNote>;
  /** Day the "add" button opens – the selected day / today */
  defaultDate: string;
}>();
const emit = defineEmits<{ open: [date: string] }>();

const notes = computed(() =>
  props.days.flatMap((date) => {
    const note = props.notesByDate.get(date);
    return note ? [note] : [];
  })
);

const defaultHasNote = computed(() => props.notesByDate.has(props.defaultDate));
</script>

<template>
  <section class="card stack">
    <div class="header">
      <h2>Poznámky ke dnům</h2>
      <button
        v-if="!defaultHasNote"
        type="button"
        class="btn btn-secondary btn-sm"
        @click="emit('open', defaultDate)"
      >
        + {{ weekdayLong(defaultDate) }}
      </button>
    </div>
    <p v-if="notes.length === 0" class="text-muted text-sm">
      Tento týden nemá žádné poznámky. Přidáte je tlačítkem výše, nebo ikonou
      <svg class="inline-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" />
        <path d="M14 3v6h6M8 13h8M8 17h5" />
      </svg>
      u data kteréhokoli dne v kalendáři.
    </p>
    <ul v-else class="note-list">
      <li v-for="note in notes" :key="note.date">
        <button type="button" class="note-item" @click="emit('open', note.date)">
          <span class="note-day">{{ weekdayLong(note.date) }} {{ formatDayMonth(note.date) }}</span>
          <span class="note-content">{{ note.content }}</span>
        </button>
      </li>
    </ul>
  </section>
</template>

<style scoped>
.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
}

.inline-icon {
  display: inline;
  vertical-align: -2px;
}

.note-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  padding: 0;
  list-style: none;
}

.note-item {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  width: 100%;
  padding: var(--space-2) var(--space-3);
  text-align: left;
  background: var(--color-surface-muted);
  border: 1px solid transparent;
  border-radius: var(--radius-md);
  transition: border-color var(--transition);
}

.note-item:hover {
  border-color: var(--color-border-strong);
}

.note-day {
  font-size: var(--font-size-xs);
  font-weight: 600;
  color: var(--color-text-muted);
}

.note-content {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  font-size: var(--font-size-sm);
  white-space: pre-line;
}
</style>
