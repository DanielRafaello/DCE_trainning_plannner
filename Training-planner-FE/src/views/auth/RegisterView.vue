<template>
  <section>
    <h2>Registrace</h2>
    <!-- TODO: form (name, email, password, timezone) -> POST /auth/register -->
    <p>
      Už máte účet? <router-link :to="{ name: 'login' }">Přihlásit se</router-link>
    </p>
  </section>
</template>
