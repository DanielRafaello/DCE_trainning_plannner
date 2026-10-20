<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import FormAlert from '@/components/form/FormAlert.vue';
import FormField from '@/components/form/FormField.vue';
import { useForm } from '@/composables/useForm';
import { timezoneOptions } from '@/lib/dates';
import {
  USER_EMAIL_MAX_LENGTH,
  USER_NAME_MAX_LENGTH,
  validateEmail,
  validateName
} from '@/lib/validation';
import { getMe } from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();

const form = reactive({
  name: auth.user?.name ?? '',
  email: auth.user?.email ?? '',
  timezone: auth.user?.timezone ?? 'Europe/Prague'
});

const timezones = timezoneOptions(form.timezone);
const deviceTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

const saved = ref(false);
watch(form, () => (saved.value = false));

const { errors, loading, message, submit } = useForm({
  values: form,
  fields: ['name', 'email', 'timezone'] as const,
  validate: () => ({
    name: validateName(form.name),
    email: validateEmail(form.email),
    timezone: form.timezone === '' ? 'Vyberte časové pásmo.' : undefined
  })
});

async function onSubmit(): Promise<void> {
  saved.value = await submit(async () => {
    const response = await getMe().updateMe({
      name: form.name.trim(),
      email: form.email.trim(),
      timezone: form.timezone
    });
    auth.setUser(response.user);
  });
}
</script>

<template>
  <form class="card stack" novalidate @submit.prevent="onSubmit">
    <div>
      <h2>Osobní údaje</h2>
      <p class="text-muted text-sm">Časové pásmo určuje začátek týdne i výpočet statistik.</p>
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
        :maxlength="USER_EMAIL_MAX_LENGTH"
        :error="errors.email"
      />
      <div class="field">
        <label for="profile-timezone" class="label">Časové pásmo</label>
        <select
          id="profile-timezone"
          v-model="form.timezone"
          class="input"
          :class="{ 'has-error': errors.timezone }"
        >
          <option v-for="zone in timezones" :key="zone" :value="zone">{{ zone }}</option>
        </select>
        <p v-if="errors.timezone" class="field-error">{{ errors.timezone }}</p>
        <button
          v-else-if="deviceTimezone && deviceTimezone !== form.timezone"
          type="button"
          class="link-button text-sm"
          @click="form.timezone = deviceTimezone"
        >
          Použít pásmo tohoto zařízení ({{ deviceTimezone }})
        </button>
      </div>
    </div>

    <div class="form-actions">
      <span v-if="saved" class="text-success text-sm" role="status">Uloženo</span>
      <button type="submit" class="btn btn-primary" :disabled="loading">
        {{ loading ? 'Ukládám…' : 'Uložit změny' }}
      </button>
    </div>
  </form>
</template>
