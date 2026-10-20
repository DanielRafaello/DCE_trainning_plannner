import { isAxiosError } from 'axios';
import type { ValidationErrorResponse } from '@/services/api/trainingPlannerAPI.schemas';

export interface ApiError {
  status: number | null;
  /** General message for the whole form (already translated) */
  message: string;
  /** Field name → message (already translated) */
  fieldErrors: Record<string, string>;
}

// Service-level errors are translation keys
const translations: Record<string, string> = {
  'auth.register.email_taken': 'Účet s tímto e-mailem už existuje.',
  'not.found.user.invalid.credentials': 'Nesprávný e-mail nebo heslo.',
  'auth.account_blocked': 'Tento účet je zablokovaný.',
  'security.origin_not_allowed': 'Požadavek byl zamítnut z bezpečnostních důvodů.',
  'security.token_compromised.relogin_required': 'Z bezpečnostních důvodů se přihlaste znovu.',

  'user.name_length': 'Jméno musí mít 2 až 100 znaků.',
  'user.timezone_invalid': 'Neplatné časové pásmo.',
  'user.password.invalid': 'Nesprávné heslo.',
  'user.delete.last_admin': 'Jediný administrátor nemůže smazat svůj účet.',
  'not.found.user': 'Uživatel nebyl nalezen.',

  'subcategory.name_length': 'Název musí mít 1 až 100 znaků.',
  'subcategory.name_taken': 'Podkategorie s tímto názvem už v kategorii existuje.',
  'subcategory.color_format': 'Barva musí být ve formátu #RRGGBB.',
  'not.found.subcategory': 'Podkategorie nebyla nalezena.',
  'not.found.category': 'Kategorie nebyla nalezena.',

  'event.title_length': 'Název musí mít 1 až 150 znaků.',
  'event.description_too_long': 'Popis může mít nejvýše 5 000 znaků.',
  'event.end_before_start': 'Konec musí být po začátku.',
  'event.too_long': 'Akce může trvat nejvýše 24 hodin.',
  'event.subcategory_category_mismatch': 'Podkategorie nepatří do zvolené kategorie.',
  'not.found.event': 'Akce nebyla nalezena, možná byla mezitím smazána.',

  'day_note.content_too_long': 'Poznámka může mít nejvýše 5 000 znaků.',
  'day_note.date_invalid': 'Neplatné datum.',
  'week.date_invalid': 'Neplatné datum týdne.',

  'todo_list.title_length': 'Název musí mít 1 až 100 znaků.',
  'todo_list.content_too_long': 'Obsah je příliš dlouhý (max. 100 kB).',
  'not.found.todo_list': 'To-do list nebyl nalezen, možná byl mezitím smazán.',

  'stats.period.to_before_from': 'Konec období musí být po jeho začátku.',
  'stats.period.too_long': 'Období může být nejvýše 5 let.',
  'stats.timeline.too_many_buckets': 'Pro toto období zvolte hrubší seskupení (týden nebo měsíc).',

  'auth.forbidden.admin_only': 'Tato stránka je jen pro administrátory.',
  'admin.cannot_demote_self': 'Sami sobě nemůžete odebrat roli administrátora.',
  'admin.cannot_block_self': 'Sami sebe nemůžete zablokovat.',
  'admin.last_admin': 'Poslední administrátor nemůže přijít o svou roli.',
  'admin.nothing_to_update': 'Není co změnit.'
};

// Keys with a parameter, e.g. `subcategory.has_events:3`
const parametrizedPatterns: [RegExp, (match: RegExpMatchArray) => string][] = [
  [
    /^subcategory\.has_events(?::(\d+))?$/,
    (m) =>
      m[1]
        ? `Podkategorii nelze smazat, používá ji ${m[1]} ${Number(m[1]) === 1 ? 'akce' : 'akcí'}.`
        : 'Podkategorii nelze smazat, používají ji některé akce.'
  ]
];

// The request validator returns English sentences ("Name must be at least 2 characters.")
const validatorPatterns: [RegExp, (match: RegExpMatchArray) => string][] = [
  ...parametrizedPatterns,
  [/ is required\.$/, () => 'Toto pole je povinné.'],
  [/ must be a valid email address\.$/, () => 'Zadejte platnou e-mailovou adresu.'],
  [/ must be at least (\d+) characters\.$/, (m) => `Musí mít alespoň ${m[1]} znaků.`],
  [/ may not exceed (\d+) characters\.$/, (m) => `Může mít nejvýše ${m[1]} znaků.`],
  [/ confirmation does not match\.$/, () => 'Hesla se neshodují.']
];

export function translateMessage(message: string): string {
  if (translations[message]) {
    return translations[message];
  }

  for (const [pattern, translate] of validatorPatterns) {
    const match = message.match(pattern);
    if (match) {
      return translate(match);
    }
  }

  return message;
}

/** One line for places without form fields (calendar drag & drop, lists, ...). */
export function apiErrorText(error: unknown): string {
  const apiError = parseApiError(error);
  return apiError.message || Object.values(apiError.fieldErrors).join(' ') || 'Nastala neočekávaná chyba.';
}

export function parseApiError(error: unknown): ApiError {
  if (!isAxiosError(error)) {
    return { status: null, message: 'Nastala neočekávaná chyba.', fieldErrors: {} };
  }

  if (!error.response) {
    return { status: null, message: 'Nepodařilo se spojit se serverem.', fieldErrors: {} };
  }

  const status = error.response.status;
  const body = error.response.data as Partial<ValidationErrorResponse> | undefined;

  if (status === 422 && body?.errors) {
    const fieldErrors = Object.fromEntries(
      Object.entries(body.errors).map(([field, message]) => [field, translateMessage(message)])
    );
    return { status, message: '', fieldErrors };
  }

  if (status === 429) {
    return { status, message: 'Příliš mnoho požadavků, zkuste to za chvíli.', fieldErrors: {} };
  }

  if (status >= 500) {
    return { status, message: 'Na serveru nastala chyba, zkuste to později.', fieldErrors: {} };
  }

  return {
    status,
    message: body?.message ? translateMessage(body.message) : 'Nastala neočekávaná chyba.',
    fieldErrors: {}
  };
}
