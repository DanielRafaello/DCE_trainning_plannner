<script setup lang="ts">
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import FormAlert from '@/components/form/FormAlert.vue';
import FormField from '@/components/form/FormField.vue';
import ConfirmButton from '@/components/ui/ConfirmButton.vue';
import { useForm } from '@/composables/useForm';
import { getMe } from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();

const form = reactive({ password: '' });

const { errors, loading, message, submit } = useForm({
  values: form,
  fields: ['password'] as const,
  validate: () => ({
    password: form.password === '' ? 'Pro potvrzení zadejte heslo.' : undefined
  })
});

async function onDelete(): Promise<void> {
  const ok = await submit(() => getMe().deleteMe({ password: form.password }));
  if (ok) {
    auth.setUser(null);
    await router.replace({ name: 'login' });
  }
}
</script>

<template>
  <form class="card stack danger-zone" novalidate @submit.prevent>
    <div>
      <h2>Smazání účtu</h2>
      <p class="text-muted text-sm">
        Účet se smaže natrvalo včetně všech akcí, poznámek, podkategorií a to-do listů.
      </p>
    </div>

    <FormAlert :message="message" />

    <FormField
      v-model="form.password"
      label="Heslo pro potvrzení"
      type="password"
      autocomplete="current-password"
      :error="errors.password"
    />

    <div class="form-actions">
      <ConfirmButton
        label="Smazat účet"
        confirm-label="Opravdu smazat natrvalo"
        :disabled="loading"
        @confirm="onDelete"
      />
    </div>
  </form>
</template>

<style scoped>
.danger-zone {
  border-color: color-mix(in oklab, var(--color-danger) 30%, var(--color-border));
}
</style>
