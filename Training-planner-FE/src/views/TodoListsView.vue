<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import FormAlert from '@/components/form/FormAlert.vue';
import TodoListEditor from '@/components/todo/TodoListEditor.vue';
import { apiErrorText } from '@/lib/apiErrors';
import { formatTimestamp, parseTimestamp } from '@/lib/timestamps';
import { getTodoLists } from '@/services/api';
import type { TodoListSummary } from '@/services/api/trainingPlannerAPI.schemas';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const lists = ref<TodoListSummary[]>([]);
const loaded = ref(false);
const error = ref('');
const creating = ref(false);

// /todo/:id – the open list lives in the URL
const selectedId = computed(() => {
  const id = Number(route.params.id);
  return Number.isInteger(id) && id > 0 ? id : null;
});

const time = (timestamp: string): number => parseTimestamp(timestamp)?.getTime() ?? 0;

// most recently edited first (FR-TODO-03)
const sortedLists = computed(() =>
  [...lists.value].sort((a, b) => time(b.updated_at) - time(a.updated_at))
);

async function load(): Promise<void> {
  error.value = '';
  try {
    const response = await getTodoLists().listTodoLists();
    lists.value = response.todo_lists;
    loaded.value = true;
  } catch (e) {
    error.value = apiErrorText(e);
  }
}
onMounted(load);

function open(id: number | null): void {
  void router.push(id === null ? { name: 'todo' } : { name: 'todo', params: { id } });
}

async function createList(): Promise<void> {
  creating.value = true;
  error.value = '';
  try {
    const { todo_list } = await getTodoLists().createTodoList({ title: 'Nový list', content: '' });
    lists.value.push({ id: todo_list.id, title: todo_list.title, updated_at: todo_list.updated_at });
    open(todo_list.id);
  } catch (e) {
    error.value = apiErrorText(e);
  } finally {
    creating.value = false;
  }
}

function onSaved(summary: TodoListSummary): void {
  lists.value = lists.value.map((list) => (list.id === summary.id ? summary : list));
}

function onDeleted(id: number): void {
  lists.value = lists.value.filter((list) => list.id !== id);
  void router.replace({ name: 'todo' });
}
</script>

<template>
  <div class="todo-view" :class="{ 'has-selection': selectedId !== null }">
    <aside class="todo-sidebar card stack">
      <div class="sidebar-header">
        <h1>To-do listy</h1>
        <button type="button" class="btn btn-primary btn-sm" :disabled="creating" @click="createList">
          + Nový
        </button>
      </div>

      <FormAlert :message="error" />
      <button v-if="error && !loaded" type="button" class="btn btn-secondary" @click="load">
        Zkusit znovu
      </button>

      <p v-if="loaded && lists.length === 0" class="text-muted text-sm">
        Zatím nemáte žádný list. Vytvořte první, třeba nákupní seznam nebo cíle na tento měsíc.
      </p>

      <ul class="list-items">
        <li v-for="list in sortedLists" :key="list.id">
          <button
            type="button"
            class="list-item"
            :class="{ active: list.id === selectedId }"
            :aria-current="list.id === selectedId ? 'page' : undefined"
            @click="open(list.id)"
          >
            <span class="list-title">{{ list.title }}</span>
            <span class="list-meta">
              Upraveno {{ formatTimestamp(list.updated_at, auth.user?.timezone) }}
            </span>
          </button>
        </li>
      </ul>
    </aside>

    <main class="todo-main card">
      <template v-if="selectedId !== null">
        <button type="button" class="btn btn-ghost btn-sm back-button" @click="open(null)">
          ← Všechny listy
        </button>
        <TodoListEditor
          :key="selectedId"
          :list-id="selectedId"
          @saved="onSaved"
          @deleted="onDeleted"
        />
      </template>
      <div v-else class="placeholder text-muted">
        <p>Vyberte list vlevo, nebo vytvořte nový.</p>
      </div>
    </main>
  </div>
</template>

<style scoped>
.todo-view {
  display: grid;
  gap: var(--space-4);
  align-items: start;
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
}

.sidebar-header h1 {
  font-size: var(--font-size-lg);
}

.list-items {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  padding: 0;
  list-style: none;
}

.list-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
  width: 100%;
  padding: var(--space-2) var(--space-3);
  text-align: left;
  background: transparent;
  border: 1px solid transparent;
  border-radius: var(--radius-md);
  transition:
    background-color var(--transition),
    border-color var(--transition);
}

.list-item:hover {
  background: var(--color-surface-muted);
}

.list-item.active {
  background: var(--color-primary-soft);
  border-color: color-mix(in oklab, var(--color-primary) 30%, transparent);
}

.list-title {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 500;
  font-size: var(--font-size-sm);
}

.list-meta {
  font-size: var(--font-size-xs);
  color: var(--color-text-subtle);
}

.placeholder {
  display: grid;
  place-items: center;
  min-height: 320px;
  text-align: center;
}

.back-button {
  margin-bottom: var(--space-3);
}

/* Tablet + desktop: list on the left, editor on the right */
@media (min-width: 768px) {
  .todo-view {
    grid-template-columns: 280px minmax(0, 1fr);
  }

  .todo-sidebar {
    position: sticky;
    top: calc(var(--header-height) + var(--space-4));
  }

  .back-button {
    display: none;
  }
}

/* Phone: either the list or the open list */
@media (max-width: 767px) {
  .todo-view.has-selection .todo-sidebar,
  .todo-view:not(.has-selection) .todo-main {
    display: none;
  }
}
</style>
