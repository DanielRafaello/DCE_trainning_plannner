<script setup lang="ts">
import { computed } from 'vue';
import { categoryColor, categoryLabel, statusLabels } from '@/lib/categories';
import { formatDuration } from '@/lib/dates';
import type { Event, EventStatus, WeekInfo } from '@/services/api/trainingPlannerAPI.schemas';
import { useCatalogStore } from '@/stores/catalog';

const props = defineProps<{ week: WeekInfo; events: Event[] }>();

const catalog = useCatalogStore();

type StatusMinutes = Record<EventStatus, number>;

// minutes per category and status, only the part of an event inside the week (like FR-STAT-06)
const rows = computed(() => {
  const weekStart = new Date(props.week.start).getTime();
  const weekEnd = new Date(props.week.end).getTime();
  const byCategory = new Map<number, StatusMinutes>();

  for (const event of props.events) {
    const start = Math.max(new Date(event.start_at).getTime(), weekStart);
    const end = Math.min(new Date(event.end_at).getTime(), weekEnd);
    const minutes = Math.max(0, Math.round((end - start) / 60_000));
    const totals = byCategory.get(event.category_id) ?? { planned: 0, done: 0, skipped: 0 };
    totals[event.status] += minutes;
    byCategory.set(event.category_id, totals);
  }

  return catalog.categories
    .filter((category) => byCategory.has(category.id))
    .map((category) => {
      const minutes = byCategory.get(category.id) as StatusMinutes;
      return {
        category,
        minutes,
        total: minutes.planned + minutes.done + minutes.skipped
      };
    })
    .sort((a, b) => b.total - a.total);
});

const maxTotal = computed(() => Math.max(1, ...rows.value.map((row) => row.total)));

const totals = computed(() =>
  rows.value.reduce(
    (sum, row) => ({
      planned: sum.planned + row.minutes.planned,
      done: sum.done + row.minutes.done,
      skipped: sum.skipped + row.minutes.skipped
    }),
    { planned: 0, done: 0, skipped: 0 }
  )
);

const statuses: EventStatus[] = ['done', 'planned', 'skipped'];
</script>

<template>
  <section class="card stack week-summary">
    <h2>Přehled týdne</h2>

    <dl class="totals">
      <div v-for="status in statuses" :key="status" class="total" :class="`total-${status}`">
        <dt>{{ statusLabels[status] }}</dt>
        <dd>{{ formatDuration(totals[status]) }}</dd>
      </div>
    </dl>

    <p v-if="rows.length === 0" class="text-muted text-sm">
      Tento týden zatím nemá žádné akce. Klikněte do kalendáře a přidejte první.
    </p>

    <ul v-else class="category-bars">
      <li v-for="row in rows" :key="row.category.id">
        <div class="bar-label">
          <span class="color-dot" :style="{ background: categoryColor(row.category) }" />
          <span class="bar-name">{{ categoryLabel(row.category) }}</span>
          <span class="bar-value">{{ formatDuration(row.total) }}</span>
        </div>
        <div class="bar-track" :style="{ width: `${(row.total / maxTotal) * 100}%` }">
          <span
            v-for="status in statuses"
            :key="status"
            class="bar-part"
            :class="`part-${status}`"
            :style="{
              flexGrow: row.minutes[status],
              '--bar-color': categoryColor(row.category)
            }"
            :title="`${statusLabels[status]}: ${formatDuration(row.minutes[status])}`"
          />
        </div>
      </li>
    </ul>
  </section>
</template>

<style scoped>
.totals {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--space-2);
}

.total {
  padding: var(--space-2) var(--space-3);
  background: var(--color-surface-muted);
  border-radius: var(--radius-md);
}

.total dt {
  font-size: var(--font-size-xs);
  color: var(--color-text-muted);
}

.total dd {
  margin: 0;
  font-weight: 600;
  font-size: var(--font-size-sm);
}

.category-bars {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  padding: 0;
  list-style: none;
}

.bar-label {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  margin-bottom: var(--space-1);
  font-size: var(--font-size-xs);
}

.bar-name {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.bar-value {
  color: var(--color-text-muted);
}

.bar-track {
  display: flex;
  min-width: 8px;
  height: 8px;
  overflow: hidden;
  border-radius: var(--radius-full);
  background: var(--color-surface-muted);
}

.bar-part {
  flex-basis: 0;
}

.part-done {
  background: var(--bar-color);
}

.part-planned {
  background: color-mix(in oklab, var(--bar-color) 45%, transparent);
}

.part-skipped {
  background: repeating-linear-gradient(
    -45deg,
    color-mix(in oklab, var(--bar-color) 35%, transparent) 0 3px,
    transparent 3px 6px
  );
}
</style>
