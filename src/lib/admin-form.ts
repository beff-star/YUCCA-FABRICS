// Small, strict readers for admin form posts. Every value is trimmed and length-limited.
import type { AstroGlobal } from 'astro';
import { setFlash, type FlashType } from '~/lib/flash';
import { parseKes } from '~/lib/money';

export const text = (form: FormData, key: string, max = 255) => String(form.get(key) ?? '').trim().slice(0, max);
export const optionalText = (form: FormData, key: string, max = 255) => text(form, key, max) || null;
export const checked = (form: FormData, key: string) => form.get(key) !== null;

export function int(form: FormData, key: string): number | null {
  const raw = text(form, key, 20);
  return /^-?\d+$/.test(raw) ? Number(raw) : null;
}

/** KES amount typed by staff → cents (null if blank or invalid). */
export const cents = (form: FormData, key: string) => (text(form, key, 20) === '' ? null : parseKes(text(form, key, 20)));

/** datetime-local value (Nairobi time) → Date, or null. */
export function nairobiDateTime(form: FormData, key: string): Date | null {
  const raw = text(form, key, 20);
  return /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(raw) ? new Date(`${raw}:00+03:00`) : null;
}

/** Date → value for a datetime-local input in Nairobi time. */
export const toNairobiInput = (date: Date | null | undefined) =>
  date ? new Date(date.getTime() + 3 * 3_600_000).toISOString().slice(0, 16) : '';

/** Post/redirect/get: show a message on the next page and send the browser back. */
export function done(Astro: AstroGlobal, type: FlashType, message: string, to = Astro.url.pathname + Astro.url.search) {
  setFlash(Astro.cookies, type, message);
  return Astro.redirect(to, 303);
}
