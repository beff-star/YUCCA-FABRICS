// Dates are stored in UTC and shown in Nairobi time (EAT, UTC+3, no daylight saving).
export const TIME_ZONE = 'Africa/Nairobi';
export const EAT_OFFSET_MS = 3 * 60 * 60 * 1000;

const dateFormat = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric', timeZone: TIME_ZONE });
const longDateFormat = new Intl.DateTimeFormat('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: TIME_ZONE });
const timeFormat = new Intl.DateTimeFormat('en-US', { hour: 'numeric', minute: '2-digit', timeZone: TIME_ZONE });

/** "21 Nov 2026" */
export const formatDate = (date: Date | number) => dateFormat.format(date);
/** "Saturday, 21 November 2026" */
export const formatLongDate = (date: Date | number) => longDateFormat.format(date);
/** "9:00 AM" */
export const formatTime = (date: Date | number) => timeFormat.format(date);
/** "21 Nov 2026, 9:00 AM" */
export const formatDateTime = (date: Date | number) => `${formatDate(date)}, ${formatTime(date)}`;

/** 'YYYY-MM-DD' for a calendar date column, formatted like formatDate. */
export const formatIsoDate = (iso: string) => formatDate(new Date(`${iso}T12:00:00+03:00`));

/** "09:30" → "9:30 AM" */
export function formatClock(hhmm: string): string {
  const [h = 0, m = 0] = hhmm.split(':').map(Number);
  return `${h % 12 || 12}:${String(m).padStart(2, '0')} ${h < 12 ? 'AM' : 'PM'}`;
}

/** Today's date in Nairobi as 'YYYY-MM-DD'. */
export const todayInNairobi = (now = Date.now()) => new Date(now + EAT_OFFSET_MS).toISOString().slice(0, 10);

/** Nairobi wall-clock date and time → UTC timestamp (ms). */
export const nairobiToUtc = (date: string, hhmm: string) => Date.parse(`${date}T${hhmm}:00+03:00`);

export const readMinutes = (body: string) => Math.max(1, Math.ceil(body.trim().split(/\s+/).length / 200));

/** Random uppercase hex, e.g. for order numbers and booking references. */
export function randomHex(bytes: number): string {
  const values = crypto.getRandomValues(new Uint8Array(bytes));
  return Array.from(values, (b) => b.toString(16).padStart(2, '0')).join('').toUpperCase();
}

// Crockford base32: no I, L, O or U, so references are easy to read out over the phone.
const REFERENCE_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

/**
 * "YF7K3QJ9X2MD": a 2-letter prefix + 10 random characters (32^10 ≈ 10^15 combinations). Exactly 12 characters,
 * the most M-Pesa accepts as an AccountReference.
 */
export function makeReference(prefix: string): string {
  const bytes = crypto.getRandomValues(new Uint8Array(10));
  return prefix + Array.from(bytes, (b) => REFERENCE_ALPHABET[b % 32]).join('');
}

/** URL-safe slug; a random suffix keeps admin-created slugs unique without a lookup. */
export function slugify(text: string, withSuffix = false): string {
  const base = text.normalize('NFKD').replace(/[̀-ͯ]/g, '').toLowerCase()
    .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80) || 'item';
  return withSuffix ? `${base}-${randomHex(3).toLowerCase()}` : base;
}
