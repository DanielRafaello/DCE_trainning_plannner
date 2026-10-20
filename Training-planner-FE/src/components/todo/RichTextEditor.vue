<script setup lang="ts">
import { onBeforeUnmount, onMounted, useTemplateRef } from 'vue';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

// Quill 2 wrapper. The HTML is read from quill.root.innerHTML – not getSemanticHTML(), which has
// a known XSS issue – and the backend sanitizes it against a Quill-aligned allowlist (NFR-10).
// Mount it with a :key per document; it reads `initialHtml` only once.
const props = defineProps<{ initialHtml: string; placeholder?: string }>();
const emit = defineEmits<{ change: [html: string] }>();

const container = useTemplateRef<HTMLDivElement>('container');
let quill: Quill | null = null;

const EMPTY_DOCUMENT = '<p><br></p>';

onMounted(() => {
  if (!container.value) return;
  quill = new Quill(container.value, {
    theme: 'snow',
    placeholder: props.placeholder,
    modules: {
      toolbar: [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'check' }, { list: 'bullet' }, { list: 'ordered' }],
        ['blockquote', 'link'],
        ['clean']
      ]
    }
  });

  quill.setContents(quill.clipboard.convert({ html: props.initialHtml }), 'silent');

  quill.on('text-change', () => {
    if (!quill) return;
    const html = quill.root.innerHTML;
    emit('change', html === EMPTY_DOCUMENT ? '' : html);
  });
});

onBeforeUnmount(() => {
  quill?.off('text-change');
  quill = null;
});
</script>

<template>
  <div class="rich-text-editor">
    <div ref="container" />
  </div>
</template>

<style scoped>
/* Quill's snow theme, toned down to the app's tokens */
.rich-text-editor {
  display: flex;
  flex-direction: column;
  min-height: 0;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface);
}

.rich-text-editor :deep(.ql-toolbar.ql-snow) {
  border: none;
  border-bottom: 1px solid var(--color-border);
  font-family: var(--font-sans);
}

.rich-text-editor :deep(.ql-container.ql-snow) {
  flex: 1;
  border: none;
  font-family: var(--font-sans);
  font-size: var(--font-size-md);
}

.rich-text-editor :deep(.ql-editor) {
  min-height: 320px;
  line-height: 1.6;
  color: var(--color-text);
}

.rich-text-editor :deep(.ql-editor.ql-blank::before) {
  color: var(--color-text-subtle);
  font-style: normal;
}

.rich-text-editor :deep(.ql-snow .ql-stroke) {
  stroke: var(--color-text-muted);
}

.rich-text-editor :deep(.ql-snow .ql-fill) {
  fill: var(--color-text-muted);
}

.rich-text-editor :deep(.ql-snow .ql-picker) {
  color: var(--color-text-muted);
}

.rich-text-editor :deep(.ql-snow button:hover .ql-stroke),
.rich-text-editor :deep(.ql-snow button.ql-active .ql-stroke) {
  stroke: var(--color-primary);
}

.rich-text-editor :deep(.ql-snow button:hover .ql-fill),
.rich-text-editor :deep(.ql-snow button.ql-active .ql-fill) {
  fill: var(--color-primary);
}

.rich-text-editor :deep(.ql-snow .ql-picker-options),
.rich-text-editor :deep(.ql-snow .ql-tooltip) {
  color: var(--color-text);
  background: var(--color-surface);
  border-color: var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
}

/* checked items of a checklist are crossed out */
.rich-text-editor :deep(.ql-editor li[data-list='checked']) {
  color: var(--color-text-muted);
  text-decoration: line-through;
}
</style>
