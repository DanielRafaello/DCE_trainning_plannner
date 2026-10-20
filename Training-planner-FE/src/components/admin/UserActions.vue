<script setup lang="ts">
import { ref } from 'vue';
import ConfirmButton from '@/components/ui/ConfirmButton.vue';
import { parseApiError } from '@/lib/apiErrors';
import { getAdmin } from '@/services/api';
import {
  Role,
  type AdminUpdateUserRequest,
  type AdminUser
} from '@/services/api/trainingPlannerAPI.schemas';

const props = defineProps<{ user: AdminUser; isSelf: boolean }>();
const emit = defineEmits<{ updated: [user: AdminUser] }>();

const busy = ref(false);
const error = ref('');

async function update(data: AdminUpdateUserRequest): Promise<void> {
  busy.value = true;
  error.value = '';
  try {
    const response = await getAdmin().adminUpdateUser(props.user.id, data);
    emit('updated', response.user);
  } catch (e) {
    // 409 admin.cannot_demote_self / admin.cannot_block_self / admin.last_admin
    error.value = parseApiError(e).message;
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <div class="user-actions">
    <div class="buttons">
      <button
        type="button"
        class="btn btn-secondary btn-sm"
        :disabled="busy || (isSelf && user.role === Role.admin)"
        :title="isSelf ? 'Sami sobě roli změnit nemůžete' : undefined"
        @click="update({ role: user.role === Role.admin ? Role.user : Role.admin })"
      >
        {{ user.role === Role.admin ? 'Odebrat admina' : 'Udělat adminem' }}
      </button>

      <button
        v-if="user.blocked_at"
        type="button"
        class="btn btn-secondary btn-sm"
        :disabled="busy"
        @click="update({ blocked: false })"
      >
        Odblokovat
      </button>
      <ConfirmButton
        v-else
        label="Zablokovat"
        confirm-label="Opravdu zablokovat?"
        :disabled="busy || isSelf"
        @confirm="update({ blocked: true })"
      />
    </div>
    <p v-if="error" class="field-error" role="alert">{{ error }}</p>
  </div>
</template>

<style scoped>
.user-actions {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.buttons {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

/* ConfirmButton renders full-size buttons – match the small ones next to it */
.buttons :deep(.btn) {
  min-height: 32px;
  padding: 0 var(--space-3);
  font-size: var(--font-size-xs);
}
</style>
