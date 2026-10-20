<script setup lang="ts">
import { ref } from 'vue';

// Two-step destructive button: the first click asks, the second one confirms.
// Used instead of window.confirm(), which blocks the page.
withDefaults(
  defineProps<{
    label: string;
    confirmLabel?: string;
    disabled?: boolean;
  }>(),
  { confirmLabel: 'Opravdu?', disabled: false }
);
const emit = defineEmits<{ confirm: [] }>();

const asking = ref(false);

function onClick(): void {
  if (asking.value) {
    asking.value = false;
    emit('confirm');
  } else {
    asking.value = true;
  }
}
</script>

<template>
  <span class="confirm-button">
    <button type="button" class="btn btn-danger" :disabled="disabled" @click="onClick">
      {{ asking ? confirmLabel : label }}
    </button>
    <button v-if="asking" type="button" class="btn btn-ghost" @click="asking = false">
      Zrušit
    </button>
  </span>
</template>

<style scoped>
.confirm-button {
  display: inline-flex;
  gap: var(--space-2);
}
</style>
