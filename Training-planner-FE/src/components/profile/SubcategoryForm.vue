<script setup lang="ts">
import { reactive } from 'vue';
import FormAlert from '@/components/form/FormAlert.vue';
import FormField from '@/components/form/FormField.vue';
import ColorPicker from '@/components/ui/ColorPicker.vue';
import { useForm } from '@/composables/useForm';
import { validateLength } from '@/lib/validation';

const SUBCATEGORY_NAME_MAX_LENGTH = 100;

const props = defineProps<{
  initialName?: string;
  initialColor: string;
  submitLabel: string;
  /** Does the API call; a thrown error is shown in the form */
  save: (data: { name: string; color: string }) => Promise<unknown>;
}>();
const emit = defineEmits<{ done: []; cancel: [] }>();

const form = reactive({ name: props.initialName ?? '', color: props.initialColor });

const { errors, loading, message, submit } = useForm({
  values: form,
  fields: ['name', 'color'] as const,
  validate: () => ({
    name: validateLength(form.name, 1, SUBCATEGORY_NAME_MAX_LENGTH, 'Zadejte název.'),
    color: /^#[0-9a-f]{6}$/i.test(form.color) ? undefined : 'Vyberte barvu.'
  })
});

async function onSubmit(): Promise<void> {
  const ok = await submit(async () => {
    await props.save({ name: form.name.trim(), color: form.color.toLowerCase() });
  });
  if (ok) emit('done');
}
</script>

<template>
  <form class="subcategory-form stack" novalidate @submit.prevent="onSubmit" @keydown.esc="emit('cancel')">
    <FormAlert :message="message" />
    <FormField
      v-model="form.name"
      label="Název"
      placeholder="např. Běh"
      :maxlength="SUBCATEGORY_NAME_MAX_LENGTH"
      :error="errors.name"
      autofocus
    />
    <div class="field">
      <span class="label">Barva</span>
      <ColorPicker v-model="form.color" />
      <p v-if="errors.color" class="field-error">{{ errors.color }}</p>
    </div>
    <div class="form-actions">
      <button type="button" class="btn btn-ghost" @click="emit('cancel')">Zrušit</button>
      <button type="submit" class="btn btn-primary" :disabled="loading">
        {{ loading ? 'Ukládám…' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<style scoped>
.subcategory-form {
  gap: var(--space-3);
  padding: var(--space-4);
  background: var(--color-surface-muted);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
}
</style>
