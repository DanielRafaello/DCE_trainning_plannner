<script setup lang="ts">
import { onMounted, useTemplateRef } from 'vue';

// Mount it with v-if – it opens on mount and emits `close` (Esc, backdrop, × button).
// The native <dialog> gives focus trapping and Esc handling for free.
defineProps<{ title: string }>();
const emit = defineEmits<{ close: [] }>();

const dialog = useTemplateRef<HTMLDialogElement>('dialog');

onMounted(() => dialog.value?.showModal());

function close(): void {
  dialog.value?.close();
}
</script>

<template>
  <dialog ref="dialog" class="modal" @close="emit('close')" @click.self="close">
    <div class="modal-panel">
      <header class="modal-header">
        <h2>{{ title }}</h2>
        <button type="button" class="btn btn-ghost btn-icon" aria-label="Zavřít" @click="close">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M18 6 6 18M6 6l12 12" />
          </svg>
        </button>
      </header>
      <div class="modal-body">
        <slot />
      </div>
      <footer v-if="$slots.footer" class="modal-footer">
        <slot name="footer" />
      </footer>
    </div>
  </dialog>
</template>

<style scoped>
.modal {
  width: min(520px, calc(100vw - 32px));
  max-height: calc(100dvh - 32px);
  padding: 0;
  margin: auto;
  color: var(--color-text);
  background: transparent;
  border: none;
  overflow: visible;
}

.modal::backdrop {
  background: rgba(20, 22, 28, 0.35);
  backdrop-filter: blur(3px);
}

.modal-panel {
  display: flex;
  flex-direction: column;
  max-height: calc(100dvh - 32px);
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  padding: var(--space-4) var(--space-4) var(--space-2) var(--space-5);
}

.modal-body {
  padding: var(--space-2) var(--space-5) var(--space-5);
  overflow-y: auto;
}

.modal-footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: var(--space-2);
  padding: var(--space-4) var(--space-5);
  border-top: 1px solid var(--color-border);
}

/* Phone: bottom sheet */
@media (max-width: 639px) {
  .modal {
    width: 100vw;
    max-width: 100vw;
    margin: auto 0 0;
  }

  .modal-panel {
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    padding-bottom: env(safe-area-inset-bottom);
  }
}
</style>
