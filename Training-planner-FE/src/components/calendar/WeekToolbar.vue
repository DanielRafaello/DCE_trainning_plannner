<script setup lang="ts">
defineProps<{ title: string; isCurrentWeek: boolean; loading: boolean }>();
const emit = defineEmits<{ prev: []; next: []; today: []; create: [] }>();
</script>

<template>
  <div class="week-toolbar">
    <div class="nav">
      <button type="button" class="btn btn-secondary btn-icon" aria-label="Předchozí týden" @click="emit('prev')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="m15 18-6-6 6-6" />
        </svg>
      </button>
      <button type="button" class="btn btn-secondary btn-icon" aria-label="Další týden" @click="emit('next')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="m9 18 6-6-6-6" />
        </svg>
      </button>
      <button type="button" class="btn btn-secondary" :disabled="isCurrentWeek" @click="emit('today')">
        Dnes
      </button>
    </div>

    <h1 class="week-title" :class="{ 'is-loading': loading }">{{ title }}</h1>

    <button
      type="button"
      class="btn btn-primary create-button"
      aria-label="Nová akce"
      @click="emit('create')"
    >
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
        <path d="M12 5v14M5 12h14" />
      </svg>
      <span>Nová akce</span>
    </button>
  </div>
</template>

<style scoped>
.week-toolbar {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.nav {
  display: flex;
  gap: var(--space-1);
}

.week-title {
  flex: 1;
  min-width: 0;
  font-size: var(--font-size-lg);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: opacity var(--transition);
}

.week-title.is-loading {
  opacity: 0.5;
}

/* Phone: title on its own row, the create button shrinks to an icon */
@media (max-width: 639px) {
  .week-toolbar {
    flex-wrap: wrap;
  }

  .week-title {
    order: -1;
    flex-basis: 100%;
  }

  .nav {
    flex: 1;
  }

  .create-button span {
    display: none;
  }
}
</style>
