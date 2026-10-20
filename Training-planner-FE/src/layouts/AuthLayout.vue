<template>
  <div class="auth-layout">
    <main class="auth-card card">
      <h1 class="auth-title">TrainingPlanner</h1>
      <router-view />
    </main>
  </div>
</template>

<style scoped>
.auth-layout {
  min-height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-4);
}

.auth-card {
  width: 100%;
  max-width: 400px;
  padding: var(--space-6) var(--space-5);
  box-shadow: var(--shadow-md);
}

.auth-title {
  margin-bottom: var(--space-5);
  text-align: center;
}
</style>
