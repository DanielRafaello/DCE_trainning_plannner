<script setup lang="ts">
import { computed, ref } from 'vue';
import { categoryColor, categoryLabel } from '@/lib/categories';
import { formatDuration } from '@/lib/dates';
import { bucketLabel, bucketTitle } from '@/lib/periods';
import type { TimelineStatisticsResponse } from '@/services/api/trainingPlannerAPI.schemas';
import { useCatalogStore } from '@/stores/catalog';

// One series (total minutes per bucket) – identity is the time axis, so one hue is enough.
// The per-category split lives in the tooltip, as text.
const props = defineProps<{ timeline: TimelineStatisticsResponse; caption: string }>();

const catalog = useCatalogStore();

const PLOT_HEIGHT = 200;
const MAX_X_LABELS = 8;
const NICE_STEPS_HOURS = [0.25, 0.5, 1, 2, 3, 4, 5, 10, 20, 25, 50, 100, 200, 250, 500];

const buckets = computed(() => props.timeline.buckets);
const maxMinutes = computed(() => Math.max(0, ...buckets.value.map((b) => b.total_minutes)));

// ~4 gridlines at round hour values
const ticks = computed(() => {
  const maxHours = maxMinutes.value / 60;
  const step = NICE_STEPS_HOURS.find((s) => maxHours / s <= 4) ?? 1000;
  const top = Math.max(step, Math.ceil(maxHours / step) * step);
  const values: number[] = [];
  for (let hours = 0; hours <= top + 1e-9; hours += step) values.push(hours);
  return { values, top };
});

const labelStride = computed(() => Math.max(1, Math.ceil(buckets.value.length / MAX_X_LABELS)));

function tickLabel(hours: number): string {
  if (hours === 0) return '0';
  return Number.isInteger(hours) ? `${hours} h` : `${Math.round(hours * 60)} min`;
}

const hovered = ref<number | null>(null);

const tooltip = computed(() => {
  if (hovered.value === null) return null;
  const bucket = buckets.value[hovered.value];
  if (!bucket) return null;
  const rows = bucket.categories
    .filter((c) => c.minutes > 0)
    .sort((a, b) => b.minutes - a.minutes)
    .map((c) => ({ category: catalog.categoryById.get(c.category_id), minutes: c.minutes }));
  return {
    title: bucketTitle(bucket.bucket, props.timeline.group_by),
    total: bucket.total_minutes,
    rows,
    // keep the tooltip inside the chart: anchor it left / center / right by the bar position
    position: hovered.value / Math.max(1, buckets.value.length - 1)
  };
});
</script>

<template>
  <figure class="timeline">
    <figcaption class="visually-hidden">{{ caption }}</figcaption>

    <div class="chart" :style="{ '--plot-height': `${PLOT_HEIGHT}px` }" @pointerleave="hovered = null">
      <div class="y-axis" aria-hidden="true">
        <span
          v-for="value in ticks.values"
          :key="value"
          class="tick"
          :style="{ bottom: `${(value / ticks.top) * 100}%` }"
        >
          {{ tickLabel(value) }}
        </span>
      </div>

      <div class="plot">
        <span
          v-for="value in ticks.values"
          :key="value"
          class="gridline"
          :style="{ bottom: `${(value / ticks.top) * 100}%` }"
        />

        <div class="columns">
          <button
            v-for="(bucket, index) in buckets"
            :key="bucket.bucket"
            type="button"
            class="column"
            :class="{ active: hovered === index }"
            :aria-label="`${bucketTitle(bucket.bucket, timeline.group_by)}: ${formatDuration(bucket.total_minutes)}`"
            @pointerenter="hovered = index"
            @focus="hovered = index"
            @blur="hovered = null"
          >
            <span
              v-if="bucket.total_minutes > 0"
              class="bar"
              :style="{ height: `${(bucket.total_minutes / 60 / ticks.top) * 100}%` }"
            />
          </button>
        </div>

        <div
          v-if="tooltip"
          class="tooltip"
          role="status"
          :style="{
            left: `${tooltip.position * 100}%`,
            transform: `translateX(-${tooltip.position * 100}%)`
          }"
        >
          <strong class="tooltip-value">{{ formatDuration(tooltip.total) }}</strong>
          <span class="tooltip-title">{{ tooltip.title }}</span>
          <ul v-if="tooltip.rows.length" class="tooltip-rows">
            <li v-for="row in tooltip.rows" :key="row.category?.id">
              <span class="line-key" :style="{ background: categoryColor(row.category) }" />
              <strong>{{ formatDuration(row.minutes) }}</strong>
              <span class="text-muted">{{ row.category ? categoryLabel(row.category) : '' }}</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="x-axis" aria-hidden="true">
        <span
          v-for="(bucket, index) in buckets"
          :key="bucket.bucket"
          class="x-label"
        >
          {{ index % labelStride === 0 ? bucketLabel(bucket.bucket, timeline.group_by) : '' }}
        </span>
      </div>
    </div>
  </figure>
</template>

<style scoped>
.timeline {
  margin: 0;
}

.chart {
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr);
  grid-template-rows: var(--plot-height) auto;
  column-gap: var(--space-2);
}

.y-axis {
  position: relative;
}

.tick {
  position: absolute;
  right: 0;
  transform: translateY(50%);
  font-size: var(--font-size-xs);
  font-variant-numeric: tabular-nums;
  color: var(--color-text-subtle);
  white-space: nowrap;
}

.plot {
  position: relative;
}

.gridline {
  position: absolute;
  left: 0;
  right: 0;
  height: 1px;
  background: var(--color-border);
}

.columns {
  position: absolute;
  inset: 0;
  display: flex;
  gap: 2px;
}

/* the whole slot is the hit target, the bar inside is at most 24px wide */
.column {
  flex: 1;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  min-width: 0;
  height: 100%;
  padding: 0;
  background: transparent;
  border: none;
  border-radius: var(--radius-sm);
}

.column.active {
  background: color-mix(in oklab, var(--color-primary-soft) 70%, transparent);
}

.bar {
  width: min(100%, 24px);
  min-height: 2px;
  background: var(--color-primary);
  border-radius: 4px 4px 0 0;
  transition: opacity var(--transition);
}

.column.active .bar {
  opacity: 0.85;
}

.x-axis {
  grid-column: 2;
  display: flex;
  gap: 2px;
  padding-top: var(--space-1);
}

.x-label {
  flex: 1;
  min-width: 0;
  overflow: visible;
  white-space: nowrap;
  text-align: center;
  font-size: var(--font-size-xs);
  color: var(--color-text-subtle);
}

.tooltip {
  position: absolute;
  bottom: calc(100% + var(--space-2));
  z-index: 5;
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 180px;
  max-width: 260px;
  padding: var(--space-2) var(--space-3);
  font-size: var(--font-size-xs);
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  pointer-events: none;
}

.tooltip-value {
  font-size: var(--font-size-sm);
}

.tooltip-title {
  color: var(--color-text-muted);
}

.tooltip-rows {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-top: var(--space-1);
  padding: 0;
  list-style: none;
}

.tooltip-rows li {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.line-key {
  flex-shrink: 0;
  width: 10px;
  height: 2px;
  border-radius: 1px;
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
}
</style>
