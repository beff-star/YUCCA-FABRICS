import type { AstroCookies } from 'astro';

export type FlashType = 'error' | 'success' | 'info';
export interface Flash { type: FlashType; message: string }

const COOKIE = 'yf_flash';

/** One-time message shown at the top of the next page (read and cleared by the middleware). */
export function setFlash(cookies: AstroCookies, type: FlashType, message: string) {
  cookies.set(COOKIE, JSON.stringify({ type, message }), { path: '/', httpOnly: true, sameSite: 'lax', maxAge: 120 });
}

export function takeFlash(cookies: AstroCookies): Flash | null {
  const raw = cookies.get(COOKIE)?.value;
  if (!raw) return null;
  cookies.delete(COOKIE, { path: '/' });
  try {
    const parsed = JSON.parse(raw) as Flash;
    return typeof parsed.message === 'string' ? parsed : null;
  } catch {
    return null;
  }
}
