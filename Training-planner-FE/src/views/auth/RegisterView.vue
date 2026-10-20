<script setup lang="ts">
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import FormAlert from '@/components/form/FormAlert.vue';
import FormField from '@/components/form/FormField.vue';
import { useForm } from '@/composables/useForm';
import {
  USER_EMAIL_MAX_LENGTH,
  USER_NAME_MAX_LENGTH,
  USER_PASSWORD_MIN_LENGTH,
  validateEmail,
  validateName,
  validateNewPassword,
  validatePasswordConfirm
} from '@/lib/validation';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();

const form = reactive({ name: '', email: '', password: '', password_confirm: '' });

const { errors, loading, message, submit } = useForm({
  values: form,
  fields: ['name', 'email', 'password', 'password_confirm'] as const,
  validate: () => ({
    name: validateName(form.name),
    email: validateEmail(form.email),
    password: validateNewPassword(form.password),
    password_confirm: validatePasswordConfirm(form.password, form.password_confirm)
  })
});

async function onSubmit(): Promise<void> {
  const ok = await submit(() =>
    auth.register({
      name: form.name.trim(),
      email: form.email.trim(),
      password: form.password,
      password_confirm: form.password_confirm
    })
  );
  if (ok) await router.replace({ name: 'calendar' });
}
</script>

<template>
  <form class="stack" novalidate @submit.prevent="onSubmit">
    <div>
      <h2>Registrace</h2>
      <p class="text-muted text-sm">Vytvořte si účet a začněte plánovat svůj týden.</p>
    </div>

    <FormAlert :message="message" />

    <div>
      <FormField
        v-model="form.name"
        label="Jméno"
        autocomplete="name"
        :maxlength="USER_NAME_MAX_LENGTH"
        :error="errors.name"
      />
      <FormField
        v-model="form.email"
        label="E-mail"
        type="email"
        autocomplete="email"
        inputmode="email"
        :maxlength="USER_EMAIL_MAX_LENGTH"
        :error="errors.email"
      />
      <FormField
        v-model="form.password"
        label="Heslo"
        type="password"
        autocomplete="new-password"
        :hint="`Alespoň ${USER_PASSWORD_MIN_LENGTH} znaků.`"
        :error="errors.password"
      />
      <FormField
        v-model="form.password_confirm"
        label="Heslo znovu"
        type="password"
        autocomplete="new-password"
        :error="errors.password_confirm"
      />
    </div>

    <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
      {{ loading ? 'Vytvářím účet…' : 'Vytvořit účet' }}
    </button>

    <p class="text-muted text-sm auth-switch">
      Už máte účet? <router-link :to="{ name: 'login' }">Přihlaste se</router-link>
    </p>
  </form>
</template>
