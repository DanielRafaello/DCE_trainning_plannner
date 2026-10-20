import { onBeforeUnmount, ref, type Ref } from 'vue';

export function useMediaQuery(query: string): Ref<boolean> {
  const mediaQuery = window.matchMedia(query);
  const matches = ref(mediaQuery.matches);

  const update = (event: MediaQueryListEvent): void => {
    matches.value = event.matches;
  };

  mediaQuery.addEventListener('change', update);
  onBeforeUnmount(() => mediaQuery.removeEventListener('change', update));

  return matches;
}
