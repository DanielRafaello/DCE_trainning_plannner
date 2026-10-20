<script setup lang="ts">
import { reactive, useId } from 'vue';
import FormAlert from '@/components/form/FormAlert.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import { useForm } from '@/composables/useForm';
import { formatDayMonth, weekdayLong } from '@/lib/dates';
import { getDayNotes } from '@/services/api';
import type { DayNote } from '@/services/api/trainingPlannerAPI.schemas';

const CONTENT_MAX_LENGTH = 5000;

const props = defineProps<{ date: string; note: DayNote | null }>();
const emit = defineEmits<{ close: []; saved: [date: string, note: DayNote | null] }>();

const id = useId();
const form = reactive({ content: props.note?.content ?? '' });

const { errors, loading, message, submit } = useForm({
  values: form,
  fields: ['content'] as const,
  validate: () => ({
    content:
      form.content.length > CONTENT_MAX_LENGTH
        ? `Poznámka může mít nejvýše ${CONTENT_MAX_LENGTH} znaků.`
        : undefined
  })
});

// an empty note deletes it (FR-DAY-02)
async function save(content: string): Promise<void> {
  await submit(async () => {
    const response = await getDayNotes().saveDayNote(props.date, { content });
    emit('saved', props.date, response.note);
  });
}
</script>

<template>
  <BaseModal :title="`${weekdayLong(date)} ${formatDayMonth(date)}`" @close="emit('close')">
    <form :id="`${id}-form`" class="stack" novalidate @submit.prevent="save(form.content.trim())">
      <FormAlert :message="message" />
      <div class="field">
        <label :for="`${id}-content`" class="label">Poznámka ke dni</label>
        <textarea
          :id="`${id}-content`"
          v-model="form.content"
          class="input"
          rows="8"
          :maxlength="CONTENT_MAX_LENGTH"
          :class="{ 'has-error': errors.content }"
          placeholder="Jak den proběhl, na co nezapomenout…"
          autofocus
        />
        <p v-if="errors.content" class="field-error">{{ errors.content }}</p>
        <p v-else class="field-hint">{{ form.content.length }} / {{ CONTENT_MAX_LENGTH }}</p>
      </div>
    </form>

    <template #footer>
      <button
        v-if="note"
        type="button"
        class="btn btn-danger footer-delete"
        :disabled="loading"
        @click="save('')"
      >
        Smazat poznámku
      </button>
      <button type="button" class="btn btn-ghost" @click="emit('close')">Zrušit</button>
      <button type="submit" :form="`${id}-form`" class="btn btn-primary" :disabled="loading">
        {{ loading ? 'Ukládám…' : 'Uložit' }}
      </button>
    </template>
  </BaseModal>
</template>

<style scoped>
textarea.input {
  resize: vertical;
  min-height: 160px;
}

.footer-delete {
  margin-right: auto;
}
</style>
