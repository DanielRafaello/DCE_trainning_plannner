<template>
  <section>
    <h2>Přihlášení</h2>
    <!-- TODO: form (email, password) -> POST /auth/login -->
    <p>
      Nemáte účet? <router-link :to="{ name: 'register' }">Registrovat se</router-link>
    </p>
  </section>
</template>
