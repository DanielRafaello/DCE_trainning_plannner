<script setup lang="ts">
// TODO: show the admin link only for role === 'admin' once there is an auth store
const navItems = [
  { name: 'calendar', label: 'Kalendář' },
  { name: 'profile', label: 'Profil' },
  { name: 'admin', label: 'Administrace' }
];
</script>

<template>
  <div class="default-layout">
    <header class="app-header">
      <router-link :to="{ name: 'calendar' }" class="app-logo">TrainingPlanner</router-link>
      <nav class="app-nav">
        <router-link v-for="item in navItems" :key="item.name" :to="{ name: item.name }">
          {{ item.label }}
        </router-link>
      </nav>
    </header>

    <main class="app-content">
      <router-view />
    </main>
  </div>
</template>

<style scoped>
.default-layout {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
}

.app-header {
  position: sticky;
  top: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
  height: var(--header-height);
  padding: 0 var(--space-4);
  background: color-mix(in oklab, var(--color-surface) 80%, transparent);
  border-bottom: 1px solid var(--color-border);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

.app-logo {
  font-weight: 600;
  letter-spacing: -0.01em;
  color: var(--color-text);
}

.app-nav {
  display: flex;
  gap: var(--space-1);
}

.app-nav a {
  padding: var(--space-2) var(--space-3);
  font-size: var(--font-size-sm);
  font-weight: 500;
  color: var(--color-text-muted);
  border-radius: var(--radius-md);
  transition:
    background-color var(--transition),
    color var(--transition);
}

.app-nav a:hover {
  color: var(--color-text);
  background: var(--color-surface-muted);
}

.app-nav .router-link-exact-active {
  color: var(--color-primary);
  background: var(--color-primary-soft);
}

.app-content {
  flex: 1;
  width: 100%;
  max-width: var(--content-max-width);
  margin: 0 auto;
  padding: var(--space-4);
}

@media (min-width: 1024px) {
  .app-content {
    padding: var(--space-5);
  }
}

/* Phone: the navigation moves to a fixed bar at the bottom of the screen */
@media (max-width: 639px) {
  .app-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    justify-content: space-around;
    padding: var(--space-2) var(--space-2) calc(var(--space-2) + env(safe-area-inset-bottom));
    background: color-mix(in oklab, var(--color-surface) 85%, transparent);
    border-top: 1px solid var(--color-border);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
  }

  .app-nav a {
    flex: 1;
    text-align: center;
  }

  .app-content {
    padding-bottom: calc(var(--bottom-nav-height) + var(--space-4));
  }
}
</style>
