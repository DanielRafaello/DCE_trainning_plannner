<script setup lang="ts">
import UserActions from '@/components/admin/UserActions.vue';
import { formatTimestamp } from '@/lib/timestamps';
import { Role, type AdminUser } from '@/services/api/trainingPlannerAPI.schemas';

defineProps<{ users: AdminUser[]; currentUserId: number | null; timeZone?: string }>();
const emit = defineEmits<{ updated: [user: AdminUser] }>();
</script>

<template>
  <!-- Desktop/tablet: a table. Phone: every row becomes a card (CSS below). -->
  <table class="user-table">
    <thead>
      <tr>
        <th scope="col">Uživatel</th>
        <th scope="col">Role</th>
        <th scope="col">Stav</th>
        <th scope="col">Registrace</th>
        <th scope="col"><span class="visually-hidden">Akce</span></th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="user in users" :key="user.id" :class="{ 'is-blocked': user.blocked_at }">
        <td class="cell-user">
          <span class="user-name">
            {{ user.name }}
            <span v-if="user.id === currentUserId" class="badge badge-self">vy</span>
          </span>
          <span class="user-email">{{ user.email }}</span>
          <span class="user-timezone">{{ user.timezone }}</span>
        </td>
        <td data-label="Role">
          <span class="badge" :class="user.role === Role.admin ? 'badge-admin' : 'badge-user'">
            {{ user.role === Role.admin ? 'Administrátor' : 'Uživatel' }}
          </span>
        </td>
        <td data-label="Stav">
          <span v-if="user.blocked_at" class="status status-blocked">
            Zablokován od {{ formatTimestamp(user.blocked_at, timeZone) }}
          </span>
          <span v-else class="status status-active">Aktivní</span>
        </td>
        <td data-label="Registrace">{{ formatTimestamp(user.created_at, timeZone) }}</td>
        <td class="cell-actions">
          <UserActions
            :user="user"
            :is-self="user.id === currentUserId"
            @updated="(updated) => emit('updated', updated)"
          />
        </td>
      </tr>
    </tbody>
  </table>
</template>

<style scoped>
.user-table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--font-size-sm);
}

th {
  padding: var(--space-2) var(--space-3);
  font-size: var(--font-size-xs);
  font-weight: 500;
  text-align: left;
  color: var(--color-text-muted);
  border-bottom: 1px solid var(--color-border);
}

td {
  padding: var(--space-3);
  vertical-align: top;
  border-bottom: 1px solid var(--color-border);
}

tbody tr:last-child td {
  border-bottom: none;
}

.cell-user {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.user-name {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-weight: 600;
}

.user-email {
  color: var(--color-text-muted);
  overflow-wrap: anywhere;
}

.user-timezone {
  font-size: var(--font-size-xs);
  color: var(--color-text-subtle);
}

.cell-actions {
  width: 1%;
  white-space: nowrap;
}

.badge {
  display: inline-block;
  padding: 2px var(--space-2);
  font-size: var(--font-size-xs);
  font-weight: 500;
  border-radius: var(--radius-full);
}

.badge-admin {
  color: var(--color-primary);
  background: var(--color-primary-soft);
}

.badge-user {
  color: var(--color-text-muted);
  background: var(--color-surface-muted);
}

.badge-self {
  color: var(--color-on-primary);
  background: var(--color-primary);
}

.status-active {
  color: var(--color-success);
}

.status-blocked {
  color: var(--color-danger);
}

tr.is-blocked .cell-user {
  opacity: 0.7;
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
}

/* Phone + small tablet: stacked cards, no horizontal scrolling */
@media (max-width: 767px) {
  thead {
    display: none;
  }

  .user-table,
  tbody,
  tr,
  td {
    display: block;
    width: 100%;
  }

  tr {
    padding: var(--space-3) 0;
    border-bottom: 1px solid var(--color-border);
  }

  tbody tr:last-child {
    border-bottom: none;
  }

  td {
    display: flex;
    align-items: baseline;
    gap: var(--space-2);
    padding: var(--space-1) 0;
    border-bottom: none;
  }

  td[data-label]::before {
    content: attr(data-label);
    flex: 0 0 90px;
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
  }

  .cell-user {
    flex-direction: column;
    align-items: flex-start;
    padding-bottom: var(--space-2);
  }

  .cell-actions {
    width: 100%;
    padding-top: var(--space-2);
    white-space: normal;
  }
}
</style>
