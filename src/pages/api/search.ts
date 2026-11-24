import type { APIRoute } from 'astro';
import { suggestProducts } from '~/lib/catalogue';

/** Live search suggestions for the shop: GET /api/search?q=linen → { results: [{ name, slug, colour }] } */
export const GET: APIRoute = async ({ url }) => {
  const q = (url.searchParams.get('q') ?? '').trim();
  const results = q.length >= 2 && q.length <= 80 ? await suggestProducts(q) : [];
  return Response.json({ results }, { headers: { 'Cache-Control': 'public, max-age=60' } });
};
