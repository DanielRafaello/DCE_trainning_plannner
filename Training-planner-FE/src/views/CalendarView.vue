<template>
  <div class="calendar-view">
    <section class="calendar-main">
      <!-- TODO: week navigation (prev / today / next) -> GET /weeks/{date} -->
      <h2>Týden</h2>
      <!-- TODO: timetable Mon–Sun with events (GET/POST/PATCH/DELETE /events),
           day notes (GET/PUT /days/{date}/note) -->
    </section>

    <aside class="calendar-side">
      <!-- TODO: to-do lists (/todo-lists), subcategories (/subcategories), statistics (/stats/*) -->
      <h2>To-do listy</h2>
    </aside>
  </div>
</template>

<style scoped>
.calendar-view {
  display: grid;
  gap: var(--space-4);
}

/* Desktop: timetable + side panel next to each other */
@media (min-width: 1024px) {
  .calendar-view {
    grid-template-columns: 1fr 320px;
  }
}
</style>
