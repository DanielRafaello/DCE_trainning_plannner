<script setup lang="ts">
import { reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import FormAlert from '@/components/form/FormAlert.vue';
import FormField from '@/components/form/FormField.vue';
import { useForm } from '@/composables/useForm';
import { validateEmail } from '@/lib/validation';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const form = reactive({ email: '', password: '' });

const { errors, loading, message, submit } = useForm({
  values: form,
  fields: ['email', 'password'] as const,
  validate: () => ({
    email: validateEmail(form.email),
    password: form.password === '' ? 'Zadejte heslo.' : undefined
  })
});

// only same-app paths, never `//evil.com`
function redirectTarget(): string {
  const redirect = route.query.redirect;
  return typeof redirect === 'string' && redirect.startsWith('/') && !redirect.startsWith('//')
    ? redirect
    : '/';
}

async function onSubmit(): Promise<void> {
  const ok = await submit(() => auth.login({ email: form.email.trim(), password: form.password }));
  if (ok) await router.replace(redirectTarget());
}
</script>

<template>
  <form class="stack" novalidate @submit.prevent="onSubmit">
    <div>
      <h2>Přihlášení</h2>
      <p class="text-muted text-sm">Vítejte zpět, pokračujte do svého plánovače.</p>
    </div>

    <FormAlert :message="message" />
    <p v-if="route.query.expired && !message" class="alert alert-info" role="status">
      Byli jste odhlášeni, přihlaste se prosím znovu.
    </p>

    <div>
      <FormField
        v-model="form.email"
        label="E-mail"
        type="email"
        autocomplete="email"
        inputmode="email"
        :error="errors.email"
      />
      <FormField
        v-model="form.password"
        label="Heslo"
        type="password"
        autocomplete="current-password"
        :error="errors.password"
      />
    </div>

    <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
      {{ loading ? 'Přihlašuji…' : 'Přihlásit se' }}
    </button>

    <p class="text-muted text-sm auth-switch">
      Nemáte účet? <router-link :to="{ name: 'register' }">Zaregistrujte se</router-link>
    </p>
  </form>
</template>
