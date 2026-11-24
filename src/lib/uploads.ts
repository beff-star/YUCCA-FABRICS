import { env } from 'cloudflare:workers';
import { randomHex } from '~/lib/format';

export const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;
export const UPLOAD_FOLDERS = ['products', 'categories', 'blog', 'gallery', 'services', 'consultations', 'requests'] as const;
export type UploadFolder = (typeof UPLOAD_FOLDERS)[number];

export class UploadError extends Error {}

/** Detects JPEG, PNG or WebP from the file's first bytes (never trust the browser's MIME type or name). */
function sniffImage(bytes: Uint8Array): { ext: string; type: string } | null {
  const starts = (...sig: number[]) => sig.every((b, i) => bytes[i] === b);
  if (starts(0xff, 0xd8, 0xff)) return { ext: 'jpg', type: 'image/jpeg' };
  if (starts(0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a)) return { ext: 'png', type: 'image/png' };
  const ascii = (from: number, to: number) => String.fromCharCode(...bytes.slice(from, to));
  if (ascii(0, 4) === 'RIFF' && ascii(8, 12) === 'WEBP') return { ext: 'webp', type: 'image/webp' };
  return null;
}

/**
 * Validates an uploaded image (JPEG/PNG/WebP, max 5 MB) and stores it in R2 under a random name.
 * Returns its public URL ("/media/<folder>/<name>"), or null when no file was chosen.
 */
export async function saveUploadedImage(file: File | null | undefined, folder: UploadFolder): Promise<string | null> {
  if (!file || file.size === 0) return null;
  if (file.size > MAX_UPLOAD_BYTES) throw new UploadError('Please choose an image smaller than 5 MB.');
  const bytes = new Uint8Array(await file.arrayBuffer());
  const kind = sniffImage(bytes);
  if (!kind) throw new UploadError('Please upload a JPG, PNG or WebP image.');
  const key = `${folder}/${randomHex(20).toLowerCase()}.${kind.ext}`;
  await env.UPLOADS.put(key, bytes, { httpMetadata: { contentType: kind.type } });
  return `/media/${key}`;
}

/** Deletes an upload previously returned by saveUploadedImage (site images in /images are left alone). */
export async function deleteUpload(url: string | null | undefined) {
  const key = url?.startsWith('/media/') ? url.slice('/media/'.length) : null;
  if (key && MEDIA_KEY.test(key)) await env.UPLOADS.delete(key);
}

export const MEDIA_KEY = /^[a-z]+\/[a-f0-9]{40}\.(jpg|png|webp)$/;
