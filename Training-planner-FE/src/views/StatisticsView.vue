<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import CategoryBreakdown from '@/components/stats/CategoryBreakdown.vue';
import TimelineChart from '@/components/stats/TimelineChart.vue';
import FormAlert from '@/components/form/FormAlert.vue';
import { apiErrorText } from '@/lib/apiErrors';
import { addDays, formatDuration, todayKey, zonedToDate } from '@/lib/dates';
import {
  customPeriod,
  daysBetween,
  periodOf,
  shiftAnchor,
  type Period,
  type PeriodKind
} from '@/lib/periods';
import { getStatistics } from '@/services/api';
import type {
  CategoryStatistics,
  TimelineStatisticsResponse
} from '@/services/api/trainingPlannerAPI.schemas';
import { useAuthStore } from '@/stores/auth';
import { useCatalogStore } from '@/stores/catalog';

const MAX_PERIOD_DAYS = 5 * 366;

const auth = useAuthStore();
const catalog = useCatalogStore();

const timeZone = computed(
  () => auth.user?.timezone ?? Intl.DateTimeFormat().resolvedOptions().timeZone
);
const today = computed(() => todayKey(timeZone.value));

// ─── period ───
const kind = ref<PeriodKind>('week');
const anchor = ref(today.value);
const customFrom = ref(addDays(today.value, -29));
const customTo = ref(today.value);
const includePlanned = ref(false);

const kinds: { value: PeriodKind; label: string }[] = [
  { value: 'week', label: 'Týden' },
  { value: 'month', label: 'Měsíc' },
  { value: 'year', label: 'Rok' },
  { value: 'custom', label: 'Vlastní' }
];

const customError = computed(() => {
  if (kind.value !== 'custom') return '';
  if (!customFrom.value || !customTo.value) return 'Zadejte začátek i konec období.';
  if (customTo.value < customFrom.value) return 'Konec období musí být po jeho začátku.';
  if (daysBetween(customFrom.value, customTo.value) > MAX_PERIOD_DAYS) return 'Období může být nejvýše 5 let.';
  return '';
});

const period = computed<Period | null>(() => {
  if (kind.value === 'custom') {
    return customError.value ? null : customPeriod(customFrom.value, customTo.value);
  }
  return periodOf(kind.value, anchor.value);
});

const isCurrent = computed(
  () => period.value !== null && period.value.from <= today.value && today.value < period.value.to
);

function shift(direction: 1 | -1): void {
  if (kind.value !== 'custom') anchor.value = shiftAnchor(kind.value, anchor.value, direction);
}

// ─── data ───
const categories = ref<CategoryStatistics[]>([]);
const timeline = ref<TimelineStatisticsResponse | null>(null);
const loading = ref(false);
const error = ref('');
const range = ref({ from: '', to: '' });
let requestId = 0;

async function load(): Promise<void> {
  const current = period.value;
  if (!current) return;
  const id = ++requestId;
  const from = zonedToDate(current.from, '00:00', timeZone.value).toISOString();
  const to = zonedToDate(current.to, '00:00', timeZone.value).toISOString();

  loading.value = true;
  error.value = '';
  try {
    const [categoryResponse, timelineResponse] = await Promise.all([
      getStatistics().getCategoryStatistics({ from, to }),
      getStatistics().getTimelineStatistics({
        from,
        to,
        groupBy: current.groupBy,
        status: includePlanned.value ? 'done,planned' : 'done'
      })
    ]);
    if (id !== requestId) return;
    categories.value = categoryResponse.categories;
    timeline.value = timelineResponse;
    range.value = { from, to };
  } catch (e) {
    if (id === requestId) error.value = apiErrorText(e);
  } finally {
    if (id === requestId) loading.value = false;
  }
}

watch([period, includePlanned, timeZone], load, { deep: true });
onMounted(() => {
  void catalog.load().catch(() => undefined); // only labels/colors, the page works without them
  void load();
});

// ─── stat tiles ───
const totals = computed(() =>
  categories.value.reduce(
    (sum, c) => ({
      doneMinutes: sum.doneMinutes + c.done.minutes,
      doneCount: sum.doneCount + c.done.count,
      plannedMinutes: sum.plannedMinutes + c.planned.minutes,
      plannedCount: sum.plannedCount + c.planned.count,
      skippedMinutes: sum.skippedMinutes + c.skipped.minutes,
      skippedCount: sum.skippedCount + c.skipped.count
    }),
    { doneMinutes: 0, doneCount: 0, plannedMinutes: 0, plannedCount: 0, skippedMinutes: 0, skippedCount: 0 }
  )
);

// done / (done + skipped) by time (FR-STAT-07)
const completionRate = computed(() => {
  const { doneMinutes, skippedMinutes } = totals.value;
  return doneMinutes + skippedMinutes === 0
    ? '–'
    : `${Math.round((doneMinutes / (doneMinutes + skippedMinutes)) * 100)} %`;
});

const pluralEvents = (count: number): string =>
  count === 1 ? '1 akce' : count >= 2 && count <= 4 ? `${count} akce` : `${count} akcí`;
</script>

<template>
  <div class="stats-view stack">
    <h1>Statistiky</h1>

    <!-- one row of filters above everything they scope -->
    <div class="filters">
      <div class="segmented period-kind" role="radiogroup" aria-label="Období">
        <button
          v-for="option in kinds"
          :key="option.value"
          type="button"
          role="radio"
          :aria-checked="kind === option.value"
          :class="{ active: kind === option.value }"
          @click="kind = option.value"
        >
          {{ option.label }}
        </button>
      </div>

      <div v-if="kind !== 'custom'" class="period-nav">
        <button type="button" class="btn btn-secondary btn-icon" aria-label="Předchozí období" @click="shift(-1)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m15 18-6-6 6-6" />
          </svg>
        </button>
        <span class="period-label">{{ period?.label }}</span>
        <button type="button" class="btn btn-secondary btn-icon" aria-label="Další období" @click="shift(1)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m9 18 6-6-6-6" />
          </svg>
        </button>
        <button type="button" class="btn btn-ghost btn-sm" :disabled="isCurrent" @click="anchor = today">
          Aktuální
        </button>
      </div>

      <div v-else class="field-row custom-range">
        <label class="field">
          <span class="label">Od</span>
          <input v-model="customFrom" type="date" class="input" />
        </label>
        <label class="field">
          <span class="label">Do</span>
          <input v-model="customTo" type="date" class="input" />
        </label>
      </div>
    </div>

    <FormAlert :message="customError || error" />
    <button v-if="error" type="button" class="btn btn-secondary retry" @click="load">Zkusit znovu</button>

    <!-- while reloading, the previous render stays at reduced opacity -->
    <div v-if="timeline" class="stats-content stack" :class="{ 'is-loading': loading }">
      <div class="tiles">
        <div class="card tile tile-hero">
          <span class="tile-label">Splněno</span>
          <span class="tile-value">{{ formatDuration(totals.doneMinutes) }}</span>
          <span class="tile-sub">{{ pluralEvents(totals.doneCount) }}</span>
        </div>
        <div class="card tile">
          <span class="tile-label">Úspěšnost</span>
          <span class="tile-value">{{ completionRate }}</span>
          <span class="tile-sub">vynecháno {{ formatDuration(totals.skippedMinutes) }}</span>
        </div>
        <div class="card tile">
          <span class="tile-label">Naplánováno</span>
          <span class="tile-value">{{ formatDuration(totals.plannedMinutes) }}</span>
          <span class="tile-sub">{{ pluralEvents(totals.plannedCount) }}</span>
        </div>
      </div>

      <section class="card stack">
        <div class="chart-header">
          <div>
            <h2>Čas v průběhu období</h2>
            <p class="text-muted text-sm">
              {{ includePlanned ? 'Splněné a naplánované akce' : 'Splněné akce' }}, po
              {{ timeline.group_by === 'day' ? 'dnech' : timeline.group_by === 'week' ? 'týdnech' : 'měsících' }}.
              Najetím na sloupec zobrazíte rozpis podle kategorií.
            </p>
          </div>
          <label class="toggle text-sm">
            <input v-model="includePlanned" type="checkbox" />
            Včetně naplánovaných
          </label>
        </div>
        <TimelineChart :timeline="timeline" :caption="`Čas podle ${period?.label ?? 'období'}`" />
      </section>

      <CategoryBreakdown :categories="categories" :from="range.from" :to="range.to" />
    </div>

    <p v-else-if="loading" class="text-muted text-sm">Načítám statistiky…</p>
  </div>
</template>

<style scoped>
.filters {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: var(--space-3);
}

.period-kind {
  flex: 0 1 340px;
}

.period-nav {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.period-label {
  min-width: 150px;
  text-align: center;
  font-weight: 600;
}

.custom-range {
  gap: var(--space-2);
}

.retry {
  align-self: flex-start;
}

.is-loading {
  opacity: 0.55;
  transition: opacity var(--transition);
}

.tiles {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--space-3);
}

.tile {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  padding: var(--space-4);
}

.tile-label {
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
}

.tile-value {
  font-size: 1.75rem;
  font-weight: 600;
  letter-spacing: -0.02em;
  line-height: 1.1;
}

.tile-hero .tile-value {
  font-size: 3rem;
}

.tile-sub {
  font-size: var(--font-size-xs);
  color: var(--color-text-subtle);
}

.chart-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-3);
}

.toggle {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  cursor: pointer;
}

/* Phone: hero tile on its own row, the other two below */
@media (max-width: 639px) {
  .tiles {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .tile-hero {
    grid-column: 1 / -1;
  }

  .tile-hero .tile-value {
    font-size: 2.5rem;
  }

  .period-kind {
    flex-basis: 100%;
  }

  .period-nav {
    flex: 1;
    justify-content: space-between;
  }
}
</style>
