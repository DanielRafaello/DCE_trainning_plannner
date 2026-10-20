<script setup lang="ts">
import { SUBCATEGORY_PALETTE } from '@/lib/categories';

const model = defineModel<string>({ required: true });
defineProps<{ label?: string }>();
</script>

<template>
  <div class="color-picker" role="radiogroup" :aria-label="label ?? 'Barva'">
    <button
      v-for="color in SUBCATEGORY_PALETTE"
      :key="color"
      type="button"
      role="radio"
      class="swatch"
      :class="{ selected: model.toLowerCase() === color }"
      :style="{ '--swatch': color }"
      :aria-checked="model.toLowerCase() === color"
      :aria-label="color"
      @click="model = color"
    />
    <label class="swatch swatch-custom" title="Vlastní barva">
      <input v-model="model" type="color" aria-label="Vlastní barva" />
    </label>
  </div>
</template>

<style scoped>
.color-picker {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.swatch {
  position: relative;
  width: 28px;
  height: 28px;
  padding: 0;
  border-radius: var(--radius-full);
  border: 2px solid var(--color-surface);
  background: var(--swatch);
  box-shadow: 0 0 0 1px var(--color-border);
  transition: box-shadow var(--transition);
}

.swatch.selected {
  box-shadow: 0 0 0 2px var(--color-primary);
}

.swatch-custom {
  overflow: hidden;
  cursor: pointer;
  background: conic-gradient(#cf9ea4, #d4b98f, #a9b58a, #8fb5a3, #8eaecf, #a99cc9, #cf9ea4);
}

.swatch-custom input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}
</style>
