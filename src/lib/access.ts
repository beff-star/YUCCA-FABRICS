import type { AstroCookies } from 'astro';
import { randomHex } from '~/lib/format';

export async function sha256Hex(text: string): Promise<string> {
  const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
  return Array.from(new Uint8Array(digest), (b) => b.toString(16).padStart(2, '0')).join('');
}

/** Constant-time string comparison (for secrets and token hashes). */
export function safeEqual(a: string, b: string): boolean {
  if (a.length !== b.length) return false;
  let diff = 0;
  for (let i = 0; i < a.length; i++) diff |= a.charCodeAt(i) ^ b.charCodeAt(i);
  return diff === 0;
}

/**
 * Guests reopen their order or class booking with a random token: the cookie (and the link in their email) holds
 * the token, the database only its SHA-256 hash.
 */
export async function newAccessToken() {
  const token = randomHex(32).toLowerCase();
  return { token, hash: await sha256Hex(token) };
}

const cookieName = (reference: string) => `yf_access_${reference}`;

export function grantAccess(cookies: AstroCookies, reference: string, token: string) {
  cookies.set(cookieName(reference), token, { path: '/', httpOnly: true, sameSite: 'lax', secure: true, maxAge: 60 * 60 * 24 * 90 });
}

interface Owned { reference: string; userId: string | null; accessTokenHash: string }

/** Admins, the signed-in owner, or a guest holding the token (cookie or ?t= link, which is then remembered). */
export async function canAccess(record: Owned, locals: App.Locals, cookies: AstroCookies, urlToken?: string | null): Promise<boolean> {
  if (locals.user?.role === 'admin') return true;
  if (record.userId && locals.user?.id === record.userId) return true;
  for (const token of [urlToken, cookies.get(cookieName(record.reference))?.value]) {
    if (token && /^[a-f0-9]{64}$/.test(token) && safeEqual(await sha256Hex(token), record.accessTokenHash)) {
      if (token === urlToken) grantAccess(cookies, record.reference, token);
      return true;
    }
  }
  return false;
}
