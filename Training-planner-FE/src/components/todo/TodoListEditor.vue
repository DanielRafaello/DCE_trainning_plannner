<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import FormAlert from '@/components/form/FormAlert.vue';
import ConfirmButton from '@/components/ui/ConfirmButton.vue';
import RichTextEditor from '@/components/todo/RichTextEditor.vue';
import { apiErrorText } from '@/lib/apiErrors';
import { getTodoLists } from '@/services/api';
import type {
  TodoList,
  TodoListSummary,
  UpdateTodoListRequest
} from '@/services/api/trainingPlannerAPI.schemas';

const TITLE_MAX_LENGTH = 100;
const AUTOSAVE_DELAY_MS = 800;

// Mount it with :key="listId"
const props = defineProps<{ listId: number }>();
const emit = defineEmits<{ saved: [summary: TodoListSummary]; deleted: [id: number] }>();

const list = ref<TodoList | null>(null);
const title = ref('');
const loadError = ref('');
const saveError = ref('');
const status = ref<'idle' | 'pending' | 'saving' | 'saved'>('idle');

async function load(): Promise<void> {
  loadError.value = '';
  try {
    const response = await getTodoLists().getTodoList(props.listId);
    list.value = response.todo_list;
    title.value = response.todo_list.title;
  } catch (e) {
    loadError.value = apiErrorText(e);
  }
}
onMounted(load);

// ─── autosave: changes are collected and sent as one PATCH after a short pause ───
let pending: UpdateTodoListRequest = {};
let timer: ReturnType<typeof setTimeout> | undefined;
let queue: Promise<void> = Promise.resolve(); // PATCHes run one after another, never in parallel

function schedule(patch: UpdateTodoListRequest): void {
  Object.assign(pending, patch);
  status.value = 'pending';
  clearTimeout(timer);
  timer = setTimeout(flush, AUTOSAVE_DELAY_MS);
}

function flush(): Promise<void> {
  clearTimeout(timer);
  if (Object.keys(pending).length === 0) return queue;
  const body = pending;
  pending = {};

  queue = queue.then(async () => {
    status.value = 'saving';
    saveError.value = '';
    try {
      const response = await getTodoLists().updateTodoList(props.listId, body);
      const { id, title: savedTitle, updated_at } = response.todo_list;
      emit('saved', { id, title: savedTitle, updated_at });
      if (Object.keys(pending).length === 0) status.value = 'saved';
    } catch (e) {
      pending = { ...body, ...pending }; // keep the unsaved changes for the next attempt
      status.value = 'idle';
      saveError.value = apiErrorText(e);
    }
  });
  return queue;
}

// leaving the list (another list, another page) saves what is left
onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', warnUnsaved);
  void flush();
});

// closing the tab can't wait for a request – ask the browser to warn instead
function warnUnsaved(event: BeforeUnloadEvent): void {
  if (status.value === 'pending' || status.value === 'saving') {
    void flush();
    event.preventDefault();
  }
}
onMounted(() => window.addEventListener('beforeunload', warnUnsaved));

const titleError = computed(() => {
  const length = title.value.trim().length;
  if (length === 0) return 'Zadejte název.';
  if (length > TITLE_MAX_LENGTH) return `Název může mít nejvýše ${TITLE_MAX_LENGTH} znaků.`;
  return '';
});

function onTitleInput(): void {
  if (!titleError.value) schedule({ title: title.value.trim() });
}

const statusText = computed(() => {
  switch (status.value) {
    case 'pending':
    case 'saving':
      return 'Ukládám…';
    case 'saved':
      return 'Uloženo';
    default:
      return '';
  }
});

const deleting = ref(false);
async function onDelete(): Promise<void> {
  clearTimeout(timer);
  pending = {};
  deleting.value = true;
  try {
    await queue;
    await getTodoLists().deleteTodoList(props.listId);
    emit('deleted', props.listId);
  } catch (e) {
    saveError.value = apiErrorText(e);
  } finally {
    deleting.value = false;
  }
}
</script>

<template>
  <section class="todo-editor stack">
    <FormAlert :message="loadError" />
    <button v-if="loadError" type="button" class="btn btn-secondary" @click="load">Zkusit znovu</button>

    <template v-if="list">
      <div class="editor-header">
        <div class="title-field">
          <label for="todo-title" class="visually-hidden">Název listu</label>
          <input
            id="todo-title"
            v-model="title"
            class="title-input"
            :class="{ 'has-error': titleError }"
            :maxlength="TITLE_MAX_LENGTH"
            placeholder="Název listu"
            @input="onTitleInput"
            @blur="flush"
          />
          <p v-if="titleError" class="field-error">{{ titleError }}</p>
        </div>
        <span class="save-status text-sm text-muted" role="status">{{ statusText }}</span>
        <ConfirmButton
          label="Smazat"
          confirm-label="Smazat list?"
          :disabled="deleting"
          @confirm="onDelete"
        />
      </div>

      <FormAlert :message="saveError" />

      <RichTextEditor
        :initial-html="list.content"
        placeholder="Začněte psát… Seznam s odškrtáváním přidáte tlačítkem ☑ v liště."
        @change="(html) => schedule({ content: html })"
      />
    </template>

    <p v-else-if="!loadError" class="text-muted text-sm">Načítám…</p>
  </section>
</template>

<style scoped>
.editor-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: var(--space-3);
}

.title-field {
  flex: 1 1 240px;
  min-width: 0;
}

.title-input {
  width: 100%;
  padding: var(--space-1) 0;
  font-size: var(--font-size-xl);
  font-weight: 600;
  letter-spacing: -0.01em;
  background: transparent;
  border: none;
  border-bottom: 1px solid transparent;
  transition: border-color var(--transition);
}

.title-input:hover,
.title-input:focus {
  outline: none;
  border-bottom-color: var(--color-border-strong);
}

.title-input.has-error {
  border-bottom-color: var(--color-danger);
}

.save-status {
  align-self: center;
  min-width: 70px;
  text-align: right;
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
}
</style>
