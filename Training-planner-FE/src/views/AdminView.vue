<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AdminUserTable from '@/components/admin/AdminUserTable.vue';
import FormAlert from '@/components/form/FormAlert.vue';
import { parseApiError } from '@/lib/apiErrors';
import { getAdmin } from '@/services/api';
import type { AdminUser } from '@/services/api/trainingPlannerAPI.schemas';
import { useAuthStore } from '@/stores/auth';

const PER_PAGE = 20;
const SEARCH_DEBOUNCE_MS = 300;

const auth = useAuthStore();

const search = ref('');
const page = ref(1);
const users = ref<AdminUser[]>([]);
const total = ref(0);
const loading = ref(false);
const loaded = ref(false);
const error = ref('');

let requestId = 0; // only the latest request may write its result (fast typing / paging)

async function load(): Promise<void> {
  const id = ++requestId;
  loading.value = true;
  error.value = '';
  try {
    const query = search.value.trim();
    const response = await getAdmin().adminListUsers({
      ...(query ? { search: query } : {}),
      page: page.value,
      per_page: PER_PAGE
    });
    if (id !== requestId) return;
    users.value = response.users;
    total.value = response.total;
    loaded.value = true;
  } catch (e) {
    if (id === requestId) error.value = parseApiError(e).message;
  } finally {
    if (id === requestId) loading.value = false;
  }
}

let debounceTimer: ReturnType<typeof setTimeout> | undefined;

watch(search, () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    page.value = 1;
    void load();
  }, SEARCH_DEBOUNCE_MS);
});

onMounted(load);
onBeforeUnmount(() => clearTimeout(debounceTimer));

const pageCount = computed(() => Math.max(1, Math.ceil(total.value / PER_PAGE)));
const rangeLabel = computed(() => {
  if (total.value === 0) return '0 z 0';
  const from = (page.value - 1) * PER_PAGE + 1;
  const to = Math.min(page.value * PER_PAGE, total.value);
  return `${from}–${to} z ${total.value}`;
});

function goToPage(target: number): void {
  page.value = Math.min(Math.max(target, 1), pageCount.value);
  void load();
}

function onUpdated(updated: AdminUser): void {
  users.value = users.value.map((user) => (user.id === updated.id ? updated : user));
  // the admin's own role is shown in the layout (admin link), keep the store current
  if (auth.user && updated.id === auth.user.id) {
    auth.setUser({ ...auth.user, role: updated.role });
  }
}
</script>

<template>
  <div class="admin-view">
    <h1 class="page-title">Administrace uživatelů</h1>

    <section class="card stack">
      <div class="toolbar">
        <div class="search">
          <label for="admin-search" class="visually-hidden">Hledat uživatele</label>
          <input
            id="admin-search"
            v-model="search"
            type="search"
            class="input"
            placeholder="Hledat podle jména nebo e-mailu"
            autocomplete="off"
            maxlength="255"
          />
        </div>
        <span class="text-muted text-sm" :class="{ 'is-loading': loading }">{{ rangeLabel }}</span>
      </div>

      <FormAlert :message="error" />
      <button v-if="error" type="button" class="btn btn-secondary retry" @click="load">
        Zkusit znovu
      </button>

      <p v-if="!loaded && loading" class="text-muted text-sm">Načítám uživatele…</p>
      <p v-else-if="loaded && users.length === 0" class="text-muted text-sm empty">
        Žádní uživatelé neodpovídají hledání.
      </p>
      <AdminUserTable
        v-else-if="users.length > 0"
        :class="{ 'is-loading': loading }"
        :users="users"
        :current-user-id="auth.user?.id ?? null"
        :time-zone="auth.user?.timezone"
        @updated="onUpdated"
      />

      <nav v-if="pageCount > 1" class="pagination" aria-label="Stránkování">
        <button
          type="button"
          class="btn btn-secondary btn-sm"
          :disabled="page <= 1 || loading"
          @click="goToPage(page - 1)"
        >
          Předchozí
        </button>
        <span class="text-sm text-muted">Strana {{ page }} z {{ pageCount }}</span>
        <button
          type="button"
          class="btn btn-secondary btn-sm"
          :disabled="page >= pageCount || loading"
          @click="goToPage(page + 1)"
        >
          Další
        </button>
      </nav>
    </section>
  </div>
</template>

<style scoped>
.toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
}

.search {
  flex: 1 1 280px;
  max-width: 420px;
}

.retry {
  align-self: flex-start;
}

.empty {
  padding: var(--space-5) 0;
  text-align: center;
}

.is-loading {
  opacity: 0.6;
  transition: opacity var(--transition);
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-3);
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
