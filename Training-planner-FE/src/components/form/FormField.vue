<script setup lang="ts">
import { useId } from 'vue';

// extra attributes (autocomplete, maxlength, inputmode, ...) go to the <input>, not the wrapper
defineOptions({ inheritAttrs: false });

const model = defineModel<string>({ required: true });

withDefaults(
  defineProps<{
    label: string;
    type?: string;
    error?: string;
    /** Shown under the input while there is no error */
    hint?: string;
  }>(),
  { type: 'text', error: undefined, hint: undefined }
);

const id = useId();
</script>

<template>
  <div class="field">
    <label :for="id" class="label">{{ label }}</label>
    <input
      :id="id"
      v-model="model"
      v-bind="$attrs"
      :type="type"
      class="input"
      :class="{ 'has-error': error }"
      :aria-invalid="error ? true : undefined"
      :aria-describedby="error || hint ? `${id}-desc` : undefined"
    />
    <p v-if="error" :id="`${id}-desc`" class="field-error">{{ error }}</p>
    <p v-else-if="hint" :id="`${id}-desc`" class="field-hint">{{ hint }}</p>
  </div>
</template>
