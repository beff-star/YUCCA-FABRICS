import { env } from 'cloudflare:workers';
import type { APIRoute } from 'astro';
import { MEDIA_KEY } from '~/lib/uploads';

/** Serves uploaded images from R2. Names are random and never reused, so they can be cached forever. */
export const GET: APIRoute = async ({ params }) => {
  const key = params.key ?? '';
  if (!MEDIA_KEY.test(key)) return new Response('Not found', { status: 404 });
  const object = await env.UPLOADS.get(key);
  if (!object) return new Response('Not found', { status: 404 });
  return new Response(object.body, {
    headers: {
      'Content-Type': object.httpMetadata?.contentType ?? 'application/octet-stream',
      'Cache-Control': 'public, max-age=31536000, immutable',
      'Content-Security-Policy': "default-src 'none'",
      ETag: object.httpEtag,
    },
  });
};
