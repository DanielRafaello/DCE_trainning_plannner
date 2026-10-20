<script setup lang="ts">
import { computed } from 'vue';
import { categoryColor } from '@/lib/categories';
import { toTimeValue } from '@/lib/dates';
import type { DaySegment } from '@/lib/eventLayout';
import { useCatalogStore } from '@/stores/catalog';

const props = defineProps<{
  segment: DaySegment;
  timeZone: string;
  /** px per hour – decides how much text fits */
  hourHeight: number;
}>();
defineEmits<{ open: [] }>();

const catalog = useCatalogStore();

const event = computed(() => props.segment.event);
const subcategory = computed(() =>
  event.value.subcategory_id !== null ? catalog.subcategoryById.get(event.value.subcategory_id) : undefined
);

// the subcategory color wins, events without one get their category's color
const color = computed(
  () => subcategory.value?.color ?? categoryColor(catalog.categoryById.get(event.value.category_id))
);

const timeRange = computed(
  () =>
    `${toTimeValue(event.value.start_at, props.timeZone)} – ${toTimeValue(event.value.end_at, props.timeZone)}`
);

const heightPx = computed(() => ((props.segment.end - props.segment.start) / 60) * props.hourHeight);
</script>

<template>
  <button
    type="button"
    class="event"
    :class="[
      `is-${event.status}`,
      {
        'is-short': heightPx < 44,
        'continues-before': segment.continuesBefore,
        'continues-after': segment.continuesAfter
      }
    ]"
    :style="{ '--event-color': color }"
    :title="[`${event.title} · ${timeRange}`, subcategory?.name, event.description]
      .filter(Boolean)
      .join('\n')"
    @click.stop="$emit('open')"
  >
    <span class="event-title">{{ event.title }}</span>
    <span class="event-time">{{ timeRange }}</span>
    <span v-if="subcategory && heightPx >= 64" class="event-time">{{ subcategory.name }}</span>
  </button>
</template>

<style scoped>
.event {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  width: 100%;
  height: 100%;
  text-align: left;
  font: inherit;
  font-size: var(--font-size-xs);
  line-height: 1.3;
}

.event > span {
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* short events: title and time on one line */
.event.is-short {
  flex-direction: row;
  align-items: center;
  gap: var(--space-1);
  padding-top: 2px;
  padding-bottom: 2px;
}

.continues-before {
  border-top-left-radius: 2px;
  border-top-right-radius: 2px;
}

.continues-after {
  border-bottom-left-radius: 2px;
  border-bottom-right-radius: 2px;
}
</style>
