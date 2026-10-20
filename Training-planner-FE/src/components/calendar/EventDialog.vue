<script lang="ts">
// remembered for the next new event in this session
let lastCategoryId: number | null = null;
</script>

<script setup lang="ts">
import { computed, reactive, ref, useId, watch } from 'vue';
import FormAlert from '@/components/form/FormAlert.vue';
import FormField from '@/components/form/FormField.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import ConfirmButton from '@/components/ui/ConfirmButton.vue';
import { useForm } from '@/composables/useForm';
import { parseApiError } from '@/lib/apiErrors';
import { categoryColor, categoryLabel, statusLabels } from '@/lib/categories';
import {
  MINUTES_PER_DAY,
  addDays,
  formatDuration,
  minutesToTime,
  toDateKey,
  toTimeValue,
  zonedToDate
} from '@/lib/dates';
import { validateLength } from '@/lib/validation';
import { getEvents } from '@/services/api';
import { EventStatus, type Event } from '@/services/api/trainingPlannerAPI.schemas';
import { useCatalogStore } from '@/stores/catalog';

const TITLE_MAX_LENGTH = 150;
const DESCRIPTION_MAX_LENGTH = 5000;

const props = defineProps<{
  /** Edited event; without it a new one is created at `date` + `startMinutes` */
  event?: Event;
  date?: string;
  startMinutes?: number;
  timeZone: string;
}>();

const emit = defineEmits<{
  close: [];
  saved: [event: Event];
  deleted: [id: number];
}>();

const catalog = useCatalogStore();
const id = useId();
const isEdit = computed(() => props.event !== undefined);

function initialValues() {
  const event = props.event;
  if (event) {
    return {
      title: event.title,
      date: toDateKey(event.start_at, props.timeZone),
      start: toTimeValue(event.start_at, props.timeZone),
      end: toTimeValue(event.end_at, props.timeZone),
      category_id: event.category_id as number | null,
      subcategory_id: event.subcategory_id,
      description: event.description ?? '',
      status: event.status as EventStatus
    };
  }
  const start = props.startMinutes ?? 8 * 60;
  return {
    title: '',
    date: props.date ?? toDateKey(new Date(), props.timeZone),
    start: minutesToTime(start),
    end: minutesToTime(Math.min(start + 60, MINUTES_PER_DAY - 1)),
    category_id: lastCategoryId ?? catalog.categories[0]?.id ?? null,
    subcategory_id: null as number | null,
    description: '',
    status: EventStatus.planned as EventStatus
  };
}

const form = reactive(initialValues());

const toMinutes = (time: string): number => {
  const [h = 0, m = 0] = time.split(':').map(Number);
  return h * 60 + m;
};

// end <= start means the event crosses midnight (max. 24 h, FR-EVT-02)
const crossesMidnight = computed(() => form.start !== '' && form.end !== '' && toMinutes(form.end) <= toMinutes(form.start));
const durationMinutes = computed(
  () => (toMinutes(form.end) - toMinutes(form.start) + MINUTES_PER_DAY) % MINUTES_PER_DAY
);

const startDate = computed(() => zonedToDate(form.date, form.start, props.timeZone));
const endDate = computed(() =>
  zonedToDate(crossesMidnight.value ? addDays(form.date, 1) : form.date, form.end, props.timeZone)
);

// a new event defaults to planned in the future and done in the past – until the user picks one
const statusTouched = ref(isEdit.value);
watch(
  startDate,
  (start) => {
    if (!statusTouched.value && !Number.isNaN(start.getTime())) {
      form.status = start > new Date() ? EventStatus.planned : EventStatus.done;
    }
  },
  { immediate: true }
);

function pickStatus(status: EventStatus): void {
  statusTouched.value = true;
  form.status = status;
}

const subcategoryOptions = computed(() =>
  form.category_id !== null ? catalog.subcategoriesOf(form.category_id) : []
);

// a subcategory must belong to the chosen category (FR-EVT-03)
watch(
  () => form.category_id,
  () => {
    if (!subcategoryOptions.value.some((s) => s.id === form.subcategory_id)) {
      form.subcategory_id = null;
    }
  }
);

const { errors, loading, message, submit } = useForm({
  values: form,
  fields: ['title', 'start_at', 'end_at', 'category_id', 'subcategory_id', 'description', 'status'] as const,
  validate: () => ({
    title: validateLength(form.title, 1, TITLE_MAX_LENGTH, 'Zadejte název.'),
    start_at: form.date === '' || form.start === '' ? 'Zadejte datum a začátek.' : undefined,
    end_at:
      form.end === ''
        ? 'Zadejte konec.'
        : durationMinutes.value === 0
          ? 'Konec musí být po začátku.'
          : undefined,
    category_id: form.category_id === null ? 'Vyberte kategorii.' : undefined,
    subcategory_id: undefined,
    description:
      form.description.length > DESCRIPTION_MAX_LENGTH
        ? `Popis může mít nejvýše ${DESCRIPTION_MAX_LENGTH} znaků.`
        : undefined,
    status: undefined
  })
});

async function onSubmit(): Promise<void> {
  await submit(async () => {
    const payload = {
      title: form.title.trim(),
      start_at: startDate.value.toISOString(),
      end_at: endDate.value.toISOString(),
      category_id: form.category_id as number,
      subcategory_id: form.subcategory_id,
      description: form.description.trim() === '' ? null : form.description.trim(),
      status: form.status
    };
    const response = props.event
      ? await getEvents().updateEvent(props.event.id, payload)
      : await getEvents().createEvent(payload);

    lastCategoryId = response.event.category_id;
    emit('saved', response.event);
  });
}

const deleteError = ref('');
const deleting = ref(false);

async function onDelete(): Promise<void> {
  if (!props.event) return;
  deleting.value = true;
  deleteError.value = '';
  try {
    await getEvents().deleteEvent(props.event.id);
    emit('deleted', props.event.id);
  } catch (error) {
    deleteError.value = parseApiError(error).message;
  } finally {
    deleting.value = false;
  }
}
</script>

<template>
  <BaseModal :title="isEdit ? 'Upravit akci' : 'Nová akce'" @close="emit('close')">
    <form :id="`${id}-form`" class="stack" novalidate @submit.prevent="onSubmit">
      <FormAlert :message="message || deleteError" />

      <FormField
        v-model="form.title"
        label="Název"
        placeholder="např. Ranní běh"
        :maxlength="TITLE_MAX_LENGTH"
        :error="errors.title"
        autofocus
      />

      <div class="field-row time-row">
        <div class="field">
          <label :for="`${id}-date`" class="label">Datum</label>
          <input :id="`${id}-date`" v-model="form.date" type="date" class="input" required />
        </div>
        <div class="field">
          <label :for="`${id}-start`" class="label">Od</label>
          <input
            :id="`${id}-start`"
            v-model="form.start"
            type="time"
            step="300"
            class="input"
            :class="{ 'has-error': errors.start_at }"
          />
        </div>
        <div class="field">
          <label :for="`${id}-end`" class="label">Do</label>
          <input
            :id="`${id}-end`"
            v-model="form.end"
            type="time"
            step="300"
            class="input"
            :class="{ 'has-error': errors.end_at }"
          />
        </div>
      </div>
      <p v-if="errors.start_at || errors.end_at" class="field-error">
        {{ errors.start_at ?? errors.end_at }}
      </p>
      <p v-else-if="durationMinutes > 0" class="field-hint">
        Délka {{ formatDuration(durationMinutes) }}<template v-if="crossesMidnight">, končí následující den</template>
      </p>

      <div class="field-row category-row">
        <div class="field">
          <label :for="`${id}-category`" class="label">Kategorie</label>
          <div class="select-with-dot">
            <span
              class="color-dot"
              :style="{ background: categoryColor(catalog.categoryById.get(form.category_id ?? -1)) }"
            />
            <select
              :id="`${id}-category`"
              v-model="form.category_id"
              class="input"
              :class="{ 'has-error': errors.category_id }"
            >
              <option v-for="category in catalog.categories" :key="category.id" :value="category.id">
                {{ categoryLabel(category) }}
              </option>
            </select>
          </div>
          <p v-if="errors.category_id" class="field-error">{{ errors.category_id }}</p>
        </div>

        <div class="field">
          <label :for="`${id}-subcategory`" class="label">Podkategorie</label>
          <select
            :id="`${id}-subcategory`"
            v-model="form.subcategory_id"
            class="input"
            :class="{ 'has-error': errors.subcategory_id }"
            :disabled="subcategoryOptions.length === 0"
          >
            <option :value="null">
              {{ subcategoryOptions.length === 0 ? 'Žádné podkategorie' : '— bez podkategorie —' }}
            </option>
            <option v-for="subcategory in subcategoryOptions" :key="subcategory.id" :value="subcategory.id">
              {{ subcategory.name }}
            </option>
          </select>
          <p v-if="errors.subcategory_id" class="field-error">{{ errors.subcategory_id }}</p>
          <router-link v-else :to="{ name: 'profile' }" class="text-sm">Spravovat podkategorie</router-link>
        </div>
      </div>

      <div class="field">
        <span class="label">Stav</span>
        <div class="segmented" role="radiogroup" aria-label="Stav">
          <button
            v-for="status in Object.values(EventStatus)"
            :key="status"
            type="button"
            role="radio"
            :aria-checked="form.status === status"
            :class="{ active: form.status === status }"
            @click="pickStatus(status)"
          >
            {{ statusLabels[status] }}
          </button>
        </div>
      </div>

      <div class="field">
        <label :for="`${id}-description`" class="label">Popis</label>
        <textarea
          :id="`${id}-description`"
          v-model="form.description"
          class="input"
          rows="3"
          :maxlength="DESCRIPTION_MAX_LENGTH"
          :class="{ 'has-error': errors.description }"
          placeholder="Volitelné poznámky k akci"
        />
        <p v-if="errors.description" class="field-error">{{ errors.description }}</p>
      </div>
    </form>

    <template #footer>
      <ConfirmButton
        v-if="isEdit"
        class="footer-delete"
        label="Smazat"
        confirm-label="Smazat akci?"
        :disabled="deleting"
        @confirm="onDelete"
      />
      <button type="button" class="btn btn-ghost" @click="emit('close')">Zrušit</button>
      <button type="submit" :form="`${id}-form`" class="btn btn-primary" :disabled="loading">
        {{ loading ? 'Ukládám…' : isEdit ? 'Uložit' : 'Vytvořit' }}
      </button>
    </template>
  </BaseModal>
</template>

<style scoped>
.time-row {
  grid-auto-flow: row;
  grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr) minmax(0, 1fr);
  gap: var(--space-2);
}

.time-row + .field-error,
.time-row + .field-hint {
  margin-top: calc(-1 * var(--space-3));
}

.category-row {
  grid-auto-flow: row;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
}

.select-with-dot {
  position: relative;
}

.select-with-dot .color-dot {
  position: absolute;
  left: var(--space-3);
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
}

.select-with-dot .input {
  padding-left: calc(var(--space-3) + 16px);
}

textarea.input {
  resize: vertical;
  min-height: 72px;
}

.footer-delete {
  margin-right: auto;
}

@media (max-width: 479px) {
  .category-row {
    grid-template-columns: 1fr;
  }

  .time-row {
    grid-template-columns: 1fr 1fr;
  }

  .time-row .field:first-child {
    grid-column: 1 / -1;
  }
}
</style>
