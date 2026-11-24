import type { APIRoute } from 'astro';
import { auth, redirectWithHeaders } from '~/lib/auth';

/** POST only (a link can't sign someone out); Astro's origin check blocks cross-site posts. */
export const POST: APIRoute = async ({ request }) => {
  const { headers } = await auth.api.signOut({ headers: request.headers, returnHeaders: true });
  return redirectWithHeaders('/', headers);
};

export const GET: APIRoute = () => new Response('Method not allowed', { status: 405, headers: { Allow: 'POST' } });
