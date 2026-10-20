<script setup lang="ts">
import { computed } from 'vue';
import { statusLabels } from '@/lib/categories';
import { formatDuration } from '@/lib/dates';
import type { StatusTotals } from '@/services/api/trainingPlannerAPI.schemas';

// Horizontal bar split by status. Statuses differ by lightness + texture of ONE hue,
// not by different colors, so the split stays readable for color-blind users and in print.
const props = defineProps<{ totals: StatusTotals; max: number }>();

const parts = computed(() =>
  (['done', 'planned', 'skipped'] as const)
    .map((status) => ({ status, minutes: props.totals[status].minutes }))
    .filter((part) => part.minutes > 0)
);

const total = computed(() => parts.value.reduce((sum, part) => sum + part.minutes, 0));
</script>

<template>
  <div class="status-bar" :style="{ width: `${props.max > 0 ? (total / props.max) * 100 : 0}%` }">
    <span
      v-for="part in parts"
      :key="part.status"
      class="segment"
      :class="`segment-${part.status}`"
      :style="{ flexGrow: part.minutes }"
      :title="`${statusLabels[part.status]}: ${formatDuration(part.minutes)}`"
    />
  </div>
</template>

<style scoped>
.status-bar {
  display: flex;
  gap: 2px;
  min-width: 4px;
  height: 12px;
}

.segment {
  flex-basis: 0;
  min-width: 2px;
}

.segment:last-child {
  border-radius: 0 4px 4px 0;
}

.segment-done {
  background: var(--color-primary);
}

.segment-planned {
  background: color-mix(in oklab, var(--color-primary) 35%, var(--color-surface));
}

.segment-skipped {
  background: repeating-linear-gradient(
    45deg,
    color-mix(in oklab, var(--color-primary) 55%, var(--color-surface)) 0 2px,
    var(--color-surface) 2px 5px
  );
  box-shadow: inset 0 0 0 1px color-mix(in oklab, var(--color-primary) 30%, transparent);
}
</style>
