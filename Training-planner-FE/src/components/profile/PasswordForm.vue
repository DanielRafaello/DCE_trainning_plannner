<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import FormAlert from '@/components/form/FormAlert.vue';
import FormField from '@/components/form/FormField.vue';
import { useForm } from '@/composables/useForm';
import {
  USER_PASSWORD_MIN_LENGTH,
  validateNewPassword,
  validatePasswordConfirm
} from '@/lib/validation';
import { getMe } from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();

const form = reactive({ current_password: '', password: '', password_confirm: '' });

const saved = ref(false);
watch(form, () => {
  if (form.current_password || form.password || form.password_confirm) saved.value = false;
});

const { errors, loading, message, submit, reset } = useForm({
  values: form,
  fields: ['current_password', 'password', 'password_confirm'] as const,
  validate: () => ({
    current_password: form.current_password === '' ? 'Zadejte současné heslo.' : undefined,
    password: validateNewPassword(form.password),
    password_confirm: validatePasswordConfirm(form.password, form.password_confirm)
  })
});

async function onSubmit(): Promise<void> {
  const ok = await submit(async () => {
    // the backend sets a fresh token pair, this browser stays logged in
    const response = await getMe().changePassword({ ...form });
    auth.setUser(response.user);
  });
  if (ok) {
    Object.assign(form, { current_password: '', password: '', password_confirm: '' });
    reset();
    saved.value = true;
  }
}
</script>

<template>
  <form class="card stack" novalidate @submit.prevent="onSubmit">
    <div>
      <h2>Změna hesla</h2>
      <p class="text-muted text-sm">Ostatní zařízení budou po změně odhlášena.</p>
    </div>

    <FormAlert :message="message" />

    <div>
      <FormField
        v-model="form.current_password"
        label="Současné heslo"
        type="password"
        autocomplete="current-password"
        :error="errors.current_password"
      />
      <FormField
        v-model="form.password"
        label="Nové heslo"
        type="password"
        autocomplete="new-password"
        :hint="`Alespoň ${USER_PASSWORD_MIN_LENGTH} znaků.`"
        :error="errors.password"
      />
      <FormField
        v-model="form.password_confirm"
        label="Nové heslo znovu"
        type="password"
        autocomplete="new-password"
        :error="errors.password_confirm"
      />
    </div>

    <div class="form-actions">
      <span v-if="saved" class="text-success text-sm" role="status">Heslo bylo změněno</span>
      <button type="submit" class="btn btn-primary" :disabled="loading">
        {{ loading ? 'Měním…' : 'Změnit heslo' }}
      </button>
    </div>
  </form>
</template>
