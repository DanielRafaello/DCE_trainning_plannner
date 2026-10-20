<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import StatusBar from '@/components/stats/StatusBar.vue';
import StatusLegend from '@/components/stats/StatusLegend.vue';
import { apiErrorText } from '@/lib/apiErrors';
import { categoryColor, categoryLabel } from '@/lib/categories';
import { formatDuration } from '@/lib/dates';
import { getStatistics } from '@/services/api';
import type {
  CategoryStatistics,
  StatusTotals,
  SubcategoryStatistics
} from '@/services/api/trainingPlannerAPI.schemas';

// A table first (every number is readable without hovering), with a bar column for the shape.
// A row expands into its subcategories (GET /stats/subcategories?category=).
const props = defineProps<{ categories: CategoryStatistics[]; from: string; to: string }>();

const totalMinutes = (t: StatusTotals): number => t.planned.minutes + t.done.minutes + t.skipped.minutes;

const rows = computed(() =>
  props.categories.filter((c) => totalMinutes(c) > 0).sort((a, b) => totalMinutes(b) - totalMinutes(a))
);
const max = computed(() => Math.max(0, ...rows.value.map(totalMinutes)));

function rate(t: StatusTotals): string {
  const value = t.completion_rate.by_minutes;
  return value === null ? '–' : `${Math.round(value * 100)} %`;
}

// ─── drill-down ───
const expanded = ref<number | null>(null);
const subRows = ref<SubcategoryStatistics[]>([]);
const subLoading = ref(false);
const subError = ref('');

async function toggle(categoryId: number): Promise<void> {
  if (expanded.value === categoryId) {
    expanded.value = null;
    return;
  }
  expanded.value = categoryId;
  await loadSubcategories(categoryId);
}

async function loadSubcategories(categoryId: number): Promise<void> {
  subLoading.value = true;
  subError.value = '';
  subRows.value = [];
  try {
    const response = await getStatistics().getSubcategoryStatistics({
      from: props.from,
      to: props.to,
      category: categoryId
    });
    if (expanded.value !== categoryId) return;
    subRows.value = response.subcategories
      .filter((s) => totalMinutes(s) > 0)
      .sort((a, b) => totalMinutes(b) - totalMinutes(a));
  } catch (e) {
    subError.value = apiErrorText(e);
  } finally {
    subLoading.value = false;
  }
}

// another period – reload the open drill-down
watch(
  () => [props.from, props.to],
  () => {
    if (expanded.value !== null) void loadSubcategories(expanded.value);
  }
);
</script>

<template>
  <section class="card stack">
    <div class="header">
      <h2>Podle kategorií</h2>
      <StatusLegend />
    </div>

    <p v-if="rows.length === 0" class="text-muted text-sm">V tomto období nejsou žádné akce.</p>

    <table v-else class="breakdown">
      <thead>
        <tr>
          <th scope="col">Kategorie</th>
          <th scope="col" class="col-bar"><span class="visually-hidden">Graf</span></th>
          <th scope="col" class="num">Splněno</th>
          <th scope="col" class="num col-optional">Naplánováno</th>
          <th scope="col" class="num col-optional">Vynecháno</th>
          <th scope="col" class="num" title="Splněno / (splněno + vynecháno), podle času">Úspěšnost</th>
        </tr>
      </thead>
      <tbody>
        <template v-for="row in rows" :key="row.category.id">
          <tr class="category-row">
            <th scope="row">
              <button
                type="button"
                class="row-toggle"
                :aria-expanded="expanded === row.category.id"
                @click="toggle(row.category.id)"
              >
                <span class="chevron" :class="{ open: expanded === row.category.id }" aria-hidden="true">›</span>
                <span class="color-dot" :style="{ background: categoryColor(row.category) }" />
                <span class="label">{{ categoryLabel(row.category) }}</span>
              </button>
            </th>
            <td class="col-bar"><StatusBar :totals="row" :max="max" /></td>
            <td class="num">{{ formatDuration(row.done.minutes) }}</td>
            <td class="num col-optional">{{ formatDuration(row.planned.minutes) }}</td>
            <td class="num col-optional">{{ formatDuration(row.skipped.minutes) }}</td>
            <td class="num">{{ rate(row) }}</td>
          </tr>

          <template v-if="expanded === row.category.id">
            <tr v-if="subLoading || subError" class="sub-row">
              <td colspan="6" class="text-muted text-sm sub-status">
                {{ subError || 'Načítám podkategorie…' }}
              </td>
            </tr>
            <tr v-for="sub in subRows" :key="sub.subcategory?.id ?? 'none'" class="sub-row">
              <th scope="row">
                <span class="sub-label">
                  <span
                    class="color-dot"
                    :style="{ background: sub.subcategory?.color ?? 'var(--color-border-strong)' }"
                  />
                  <span class="label">{{ sub.subcategory?.name ?? 'Bez podkategorie' }}</span>
                </span>
              </th>
              <td class="col-bar"><StatusBar :totals="sub" :max="max" /></td>
              <td class="num">{{ formatDuration(sub.done.minutes) }}</td>
              <td class="num col-optional">{{ formatDuration(sub.planned.minutes) }}</td>
              <td class="num col-optional">{{ formatDuration(sub.skipped.minutes) }}</td>
              <td class="num">{{ rate(sub) }}</td>
            </tr>
          </template>
        </template>
      </tbody>
    </table>
  </section>
</template>

<style scoped>
.header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
}

.breakdown {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--font-size-sm);
}

.breakdown th,
.breakdown td {
  padding: var(--space-2) var(--space-2);
  border-bottom: 1px solid var(--color-border);
  text-align: left;
  vertical-align: middle;
}

.breakdown thead th {
  font-size: var(--font-size-xs);
  font-weight: 500;
  color: var(--color-text-muted);
}

.breakdown tbody th {
  font-weight: 500;
  max-width: 0; /* lets the label ellipsize inside the auto table layout */
  width: 32%;
}

.num {
  text-align: right !important;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

.col-bar {
  width: 28%;
}

.row-toggle,
.sub-label {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  width: 100%;
  min-width: 0;
  padding: 0;
  font: inherit;
  text-align: left;
  background: none;
  border: none;
}

.sub-label {
  padding-left: calc(var(--space-5) + var(--space-1));
}

.label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chevron {
  display: inline-block;
  width: 12px;
  color: var(--color-text-subtle);
  transition: transform var(--transition);
}

.chevron.open {
  transform: rotate(90deg);
}

.sub-row th,
.sub-row td {
  background: var(--color-surface-muted);
  font-weight: 400;
}

.sub-status {
  padding-left: calc(var(--space-5) + var(--space-3)) !important;
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
}

/* Phone: planned / skipped stay in the bar's hover title and drop out of the table */
@media (max-width: 639px) {
  .col-optional {
    display: none;
  }

  .breakdown tbody th {
    width: 40%;
  }

  .col-bar {
    width: 22%;
  }
}
</style>
