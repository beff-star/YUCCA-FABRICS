import { env } from 'cloudflare:workers';

// AES-GCM encryption for third-party secrets stored in D1 (e.g. the Google refresh token).
// TOKEN_ENCRYPTION_KEY is 32 random bytes, base64-encoded, kept as a Worker secret.

const toBase64 = (bytes: Uint8Array) => btoa(String.fromCharCode(...bytes));
const fromBase64 = (text: string) => Uint8Array.from(atob(text), (c) => c.charCodeAt(0));

async function key() {
  const raw = fromBase64(env.TOKEN_ENCRYPTION_KEY ?? '');
  if (raw.length !== 32) throw new Error('TOKEN_ENCRYPTION_KEY must be 32 bytes, base64-encoded');
  return crypto.subtle.importKey('raw', raw, 'AES-GCM', false, ['encrypt', 'decrypt']);
}

/** Returns base64(iv ‖ ciphertext). A fresh random IV is used every time. */
export async function encryptSecret(plain: string): Promise<string> {
  const iv = crypto.getRandomValues(new Uint8Array(12));
  const cipher = new Uint8Array(await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, await key(), new TextEncoder().encode(plain)));
  const out = new Uint8Array(iv.length + cipher.length);
  out.set(iv);
  out.set(cipher, iv.length);
  return toBase64(out);
}

export async function decryptSecret(stored: string): Promise<string> {
  const bytes = fromBase64(stored);
  const plain = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: bytes.slice(0, 12) }, await key(), bytes.slice(12));
  return new TextDecoder().decode(plain);
}
