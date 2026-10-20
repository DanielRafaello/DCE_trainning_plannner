import { computed, ref, watch } from 'vue';
import { parseApiError } from '@/lib/apiErrors';
import { compactErrors, type FieldErrors } from '@/lib/validation';

interface UseFormOptions<F extends string> {
  /** Reactive form values – any change clears the server errors */
  values: object;
  /** Fields shown in the form; server errors for other fields end up in `message` */
  fields: readonly F[];
  /** Client-side checks, `undefined` = valid */
  validate?: () => Record<F, string | undefined>;
}

/**
 * Shared submit / error handling for forms: client errors appear after the first submit and then
 * update live, server errors (422) are mapped to fields, everything else goes to `message`.
 */
export function useForm<F extends string>(options: UseFormOptions<F>) {
  const submitted = ref(false);
  const loading = ref(false);
  const message = ref('');
  const serverErrors = ref<FieldErrors<F>>({});

  const clientErrors = computed<FieldErrors<F>>(() =>
    options.validate ? compactErrors<F>(options.validate()) : {}
  );

  const errors = computed<FieldErrors<F>>(() =>
    submitted.value ? { ...serverErrors.value, ...clientErrors.value } : {}
  );

  watch(
    () => options.values,
    () => {
      serverErrors.value = {};
      message.value = '';
    },
    { deep: true }
  );

  /** Runs `action` when the form is valid. Returns true on success. */
  async function submit(action: () => Promise<void>): Promise<boolean> {
    submitted.value = true;
    if (loading.value || Object.keys(clientErrors.value).length > 0) return false;

    loading.value = true;
    message.value = '';
    try {
      await action();
      return true;
    } catch (error) {
      const apiError = parseApiError(error);
      const known: FieldErrors<F> = {};
      const other: string[] = [];
      for (const [field, text] of Object.entries(apiError.fieldErrors)) {
        if ((options.fields as readonly string[]).includes(field)) {
          known[field as F] = text;
        } else {
          other.push(text);
        }
      }
      serverErrors.value = known;
      message.value = [apiError.message, ...other].filter(Boolean).join(' ');
      return false;
    } finally {
      loading.value = false;
    }
  }

  /** Back to the pristine state, e.g. after a successful save that clears the inputs. */
  function reset(): void {
    submitted.value = false;
    serverErrors.value = {};
    message.value = '';
  }

  return { errors, loading, message, submit, reset };
}
