// Client-side checks mirroring the backend rules (backend/src/Requests, backend/src/Domain/User.php).
// The backend still validates everything – this only gives faster feedback.

export const USER_NAME_MIN_LENGTH = 2;
export const USER_NAME_MAX_LENGTH = 100;
export const USER_EMAIL_MAX_LENGTH = 255;
export const USER_PASSWORD_MIN_LENGTH = 8;

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export type FieldErrors<T extends string = string> = Partial<Record<T, string>>;

export function validateEmail(value: string): string | undefined {
  const email = value.trim();
  if (email === '') return 'Zadejte e-mail.';
  if (email.length > USER_EMAIL_MAX_LENGTH) return `E-mail může mít nejvýše ${USER_EMAIL_MAX_LENGTH} znaků.`;
  if (!EMAIL_PATTERN.test(email)) return 'Zadejte platnou e-mailovou adresu.';
  return undefined;
}

export function validateName(value: string): string | undefined {
  const name = value.trim();
  if (name === '') return 'Zadejte jméno.';
  if (name.length < USER_NAME_MIN_LENGTH) return `Jméno musí mít alespoň ${USER_NAME_MIN_LENGTH} znaky.`;
  if (name.length > USER_NAME_MAX_LENGTH) return `Jméno může mít nejvýše ${USER_NAME_MAX_LENGTH} znaků.`;
  return undefined;
}

export function validateNewPassword(value: string): string | undefined {
  if (value.trim() === '') return 'Zadejte heslo.';
  if (value.trim().length < USER_PASSWORD_MIN_LENGTH) {
    return `Heslo musí mít alespoň ${USER_PASSWORD_MIN_LENGTH} znaků.`;
  }
  return undefined;
}

export function validatePasswordConfirm(password: string, confirm: string): string | undefined {
  if (confirm === '') return 'Zopakujte heslo.';
  if (confirm !== password) return 'Hesla se neshodují.';
  return undefined;
}

export function validateLength(
  value: string,
  min: number,
  max: number,
  emptyMessage: string
): string | undefined {
  const length = value.trim().length;
  if (length === 0 && min > 0) return emptyMessage;
  if (length < min) return `Musí mít alespoň ${min} znaků.`;
  if (length > max) return `Může mít nejvýše ${max} znaků.`;
  return undefined;
}

/** Drops fields without an error, so `Object.keys(errors).length === 0` means valid. */
export function compactErrors<T extends string>(errors: Record<T, string | undefined>): FieldErrors<T> {
  return Object.fromEntries(
    Object.entries(errors).filter(([, message]) => message !== undefined)
  ) as FieldErrors<T>;
}
