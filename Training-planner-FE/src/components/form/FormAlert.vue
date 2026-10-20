<script setup lang="ts">
defineProps<{ message?: string }>();
</script>

<template>
  <div v-if="message" class="alert alert-danger" role="alert">{{ message }}</div>
</template>
