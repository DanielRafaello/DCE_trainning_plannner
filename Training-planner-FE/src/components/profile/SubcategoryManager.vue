<script setup lang="ts">
import { onMounted, ref } from 'vue';
import FormAlert from '@/components/form/FormAlert.vue';
import ConfirmButton from '@/components/ui/ConfirmButton.vue';
import SubcategoryForm from '@/components/profile/SubcategoryForm.vue';
import { parseApiError } from '@/lib/apiErrors';
import { SUBCATEGORY_PALETTE, categoryColor, categoryLabel } from '@/lib/categories';
import type { Subcategory } from '@/services/api/trainingPlannerAPI.schemas';
import { useCatalogStore } from '@/stores/catalog';

const catalog = useCatalogStore();

const loadError = ref('');
const addingTo = ref<number | null>(null);
const editingId = ref<number | null>(null);
// subcategory id → error of the last delete attempt (409 when events use it)
const deleteErrors = ref<Record<number, string>>({});

async function load(): Promise<void> {
  loadError.value = '';
  try {
    await catalog.load();
  } catch (error) {
    loadError.value = parseApiError(error).message;
  }
}

onMounted(load);

function nextFreeColor(): string {
  const used = new Set(catalog.subcategories.map((s) => s.color.toLowerCase()));
  return SUBCATEGORY_PALETTE.find((c) => !used.has(c)) ?? SUBCATEGORY_PALETTE[0] ?? '#8eaecf';
}

function startAdding(categoryId: number): void {
  editingId.value = null;
  addingTo.value = categoryId;
}

function startEditing(subcategory: Subcategory): void {
  addingTo.value = null;
  editingId.value = subcategory.id;
}

async function onDelete(subcategory: Subcategory): Promise<void> {
  delete deleteErrors.value[subcategory.id];
  try {
    await catalog.deleteSubcategory(subcategory.id);
  } catch (error) {
    deleteErrors.value[subcategory.id] = parseApiError(error).message;
  }
}
</script>

<template>
  <section class="card stack">
    <div>
      <h2>Podkategorie</h2>
      <p class="text-muted text-sm">
        Vlastní podkategorie pro každou oblast života, např. Běh a Plavání pod Zdraví a fitness. Barvou
        podkategorie se obarví akce v kalendáři.
      </p>
    </div>

    <FormAlert :message="loadError" />
    <button v-if="loadError" type="button" class="btn btn-secondary" @click="load">
      Zkusit znovu
    </button>

    <div class="category-list">
      <section v-for="category in catalog.categories" :key="category.id" class="category-group">
        <header class="category-header">
          <span class="color-dot" :style="{ background: categoryColor(category) }" />
          <h3>{{ categoryLabel(category) }}</h3>
          <button
            v-if="addingTo !== category.id"
            type="button"
            class="btn btn-ghost btn-sm"
            @click="startAdding(category.id)"
          >
            + Přidat
          </button>
        </header>

        <ul class="subcategory-list">
          <li v-for="subcategory in catalog.subcategoriesOf(category.id)" :key="subcategory.id">
            <SubcategoryForm
              v-if="editingId === subcategory.id"
              :initial-name="subcategory.name"
              :initial-color="subcategory.color"
              submit-label="Uložit"
              :save="(data) => catalog.updateSubcategory(subcategory.id, data)"
              @done="editingId = null"
              @cancel="editingId = null"
            />
            <template v-else>
              <div class="subcategory-row">
                <span class="color-dot" :style="{ background: subcategory.color }" />
                <span class="subcategory-name">{{ subcategory.name }}</span>
                <button type="button" class="btn btn-ghost btn-sm" @click="startEditing(subcategory)">
                  Upravit
                </button>
                <ConfirmButton
                  label="Smazat"
                  confirm-label="Smazat?"
                  @confirm="onDelete(subcategory)"
                />
              </div>
              <p v-if="deleteErrors[subcategory.id]" class="field-error">
                {{ deleteErrors[subcategory.id] }}
              </p>
            </template>
          </li>
        </ul>

        <SubcategoryForm
          v-if="addingTo === category.id"
          :initial-color="nextFreeColor()"
          submit-label="Přidat"
          :save="(data) => catalog.createSubcategory({ category_id: category.id, ...data })"
          @done="addingTo = null"
          @cancel="addingTo = null"
        />
      </section>
    </div>
  </section>
</template>

<style scoped>
.category-list {
  display: flex;
  flex-direction: column;
}

.category-group {
  padding: var(--space-3) 0;
  border-top: 1px solid var(--color-border);
}

.category-header {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  min-height: 36px;
}

.category-header h3 {
  flex: 1;
  font-size: var(--font-size-sm);
}

.subcategory-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  padding: 0;
  list-style: none;
}

.subcategory-list:not(:empty) {
  margin: var(--space-2) 0;
}

.subcategory-row {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding-left: var(--space-5);
}

.subcategory-name {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: var(--font-size-sm);
}

.subcategory-list .field-error {
  padding-left: var(--space-5);
}
</style>
