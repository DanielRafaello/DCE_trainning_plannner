import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { getCategories, getSubcategories } from '@/services/api';
import type {
  Category,
  CreateSubcategoryRequest,
  Subcategory,
  UpdateSubcategoryRequest
} from '@/services/api/trainingPlannerAPI.schemas';

/** Categories (fixed) and the user's subcategories – shared by the calendar and the profile. */
export const useCatalogStore = defineStore('catalog', () => {
  const categories = ref<Category[]>([]);
  const subcategories = ref<Subcategory[]>([]);

  let loadPromise: Promise<void> | null = null;

  const categoryById = computed(() => new Map(categories.value.map((c) => [c.id, c])));
  const subcategoryById = computed(() => new Map(subcategories.value.map((s) => [s.id, s])));

  function subcategoriesOf(categoryId: number): Subcategory[] {
    return subcategories.value
      .filter((s) => s.category_id === categoryId)
      .sort((a, b) => a.name.localeCompare(b.name, 'cs'));
  }

  function load(): Promise<void> {
    loadPromise ??= Promise.all([
      getCategories().listCategories(),
      getSubcategories().listSubcategories()
    ])
      .then(([categoryResponse, subcategoryResponse]) => {
        categories.value = [...categoryResponse.categories].sort((a, b) => a.position - b.position);
        subcategories.value = subcategoryResponse.subcategories;
      })
      .catch((error: unknown) => {
        loadPromise = null; // allow a retry
        throw error;
      });
    return loadPromise;
  }

  async function createSubcategory(data: CreateSubcategoryRequest): Promise<Subcategory> {
    const { subcategory } = await getSubcategories().createSubcategory(data);
    subcategories.value.push(subcategory);
    return subcategory;
  }

  async function updateSubcategory(id: number, data: UpdateSubcategoryRequest): Promise<void> {
    const { subcategory } = await getSubcategories().updateSubcategory(id, data);
    subcategories.value = subcategories.value.map((s) => (s.id === id ? subcategory : s));
  }

  async function deleteSubcategory(id: number): Promise<void> {
    await getSubcategories().deleteSubcategory(id);
    subcategories.value = subcategories.value.filter((s) => s.id !== id);
  }

  /** Called when the user changes (logout / another login) */
  function reset(): void {
    categories.value = [];
    subcategories.value = [];
    loadPromise = null;
  }

  return {
    categories,
    subcategories,
    categoryById,
    subcategoryById,
    subcategoriesOf,
    load,
    createSubcategory,
    updateSubcategory,
    deleteSubcategory,
    reset
  };
});
