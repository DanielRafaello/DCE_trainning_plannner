<script setup lang="ts">
import { statusLabels } from '@/lib/categories';
</script>

<template>
  <ul class="status-legend" aria-label="Legenda">
    <li v-for="status in ['done', 'planned', 'skipped'] as const" :key="status">
      <span class="swatch" :class="`swatch-${status}`" />
      {{ statusLabels[status] }}
    </li>
  </ul>
</template>

<style scoped>
.status-legend {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-4);
  padding: 0;
  list-style: none;
  font-size: var(--font-size-xs);
  color: var(--color-text-muted);
}

.status-legend li {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.swatch {
  width: 14px;
  height: 10px;
  border-radius: 2px;
}

.swatch-done {
  background: var(--color-primary);
}

.swatch-planned {
  background: color-mix(in oklab, var(--color-primary) 35%, var(--color-surface));
}

.swatch-skipped {
  background: repeating-linear-gradient(
    45deg,
    color-mix(in oklab, var(--color-primary) 55%, var(--color-surface)) 0 2px,
    var(--color-surface) 2px 5px
  );
  box-shadow: inset 0 0 0 1px color-mix(in oklab, var(--color-primary) 30%, transparent);
}
</style>
