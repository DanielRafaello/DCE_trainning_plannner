import type { Category, EventStatus } from '@/services/api/trainingPlannerAPI.schemas';

// Categories are fixed on the backend (FR-CAT-01); the stable slug drives the label and color here.
const categoryMeta: Record<string, { label: string; color: string }> = {
  adventure_travel: { label: 'Dobrodružství a cestování', color: '#86b3b8' },
  wealth_lifestyle_legacy: { label: 'Bohatství, životní styl a odkaz', color: '#d4b98f' },
  creative_projects_artistic_expression: { label: 'Kreativní projekty a umění', color: '#cf9ea4' },
  career_creative_work: { label: 'Kariéra a tvůrčí práce', color: '#8eaecf' },
  relationship_love_family: { label: 'Vztahy, láska a rodina', color: '#dcae96' },
  health_fitness: { label: 'Zdraví a fitness', color: '#8fb5a3' },
  spirituality_soul_inner_work: { label: 'Spiritualita a vnitřní práce', color: '#a99cc9' },
  connection_community: { label: 'Přátelé a komunita', color: '#a9b58a' },
  other: { label: 'Ostatní', color: '#a3abb8' }
};

const FALLBACK_COLOR = '#a3abb8';

export function categoryLabel(category: Pick<Category, 'slug' | 'name'>): string {
  return categoryMeta[category.slug]?.label ?? category.name;
}

export function categoryColor(category: Pick<Category, 'slug'> | undefined): string {
  return (category && categoryMeta[category.slug]?.color) || FALLBACK_COLOR;
}

/**
 * Muted colors offered for subcategories. The backend's own default palette is quite saturated,
 * so the frontend always sends a color from this one when creating a subcategory.
 */
export const SUBCATEGORY_PALETTE = [
  '#8fb5a3',
  '#8eaecf',
  '#a99cc9',
  '#d4b98f',
  '#cf9ea4',
  '#86b3b8',
  '#dcae96',
  '#a9b58a',
  '#b7a3c4',
  '#9fb8d6',
  '#c9b48f',
  '#a3abb8'
];

export const statusLabels: Record<EventStatus, string> = {
  planned: 'Naplánováno',
  done: 'Splněno',
  skipped: 'Vynecháno'
};
