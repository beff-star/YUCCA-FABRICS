import { env } from 'cloudflare:workers';
import { defineMiddleware } from 'astro:middleware';
import { auth, type SessionUser } from '~/lib/auth';
import { setFlash, takeFlash } from '~/lib/flash';

// Machine-to-machine and file routes need no session, flash or page guards.
const SKIP_SESSION = ['/api/mpesa/', '/media/', '/sitemap.xml', '/robots.txt'];

const SECURITY_HEADERS: Record<string, string> = {
  'X-Content-Type-Options': 'nosniff',
  'Referrer-Policy': 'strict-origin-when-cross-origin',
  'X-Frame-Options': 'DENY',
  'Permissions-Policy': 'camera=(), microphone=(), geolocation=(), payment=()',
  'Strict-Transport-Security': 'max-age=31536000; includeSubDomains',
};

// Search engines should see one host, so www.<domain> permanently redirects to the SITE_URL host.
const canonicalHost = URL.canParse(env.SITE_URL) ? new URL(env.SITE_URL).host : '';

export const onRequest = defineMiddleware(async (context, next) => {
  const { pathname } = context.url;
  if (canonicalHost && context.url.host === `www.${canonicalHost}`) {
    return Response.redirect(new URL(pathname + context.url.search, env.SITE_URL).toString(), 301);
  }
  context.locals.user = null;
  context.locals.flash = null;

  if (!SKIP_SESSION.some((prefix) => pathname.startsWith(prefix))) {
    const session = await auth.api.getSession({ headers: context.request.headers });
    const u = session?.user as Record<string, unknown> | undefined;
    // Suspended customers are treated as signed out on their next request (within the 5-minute cookie cache).
    if (u && u.status === 'active') {
      context.locals.user = {
        id: String(u.id), name: String(u.name), email: String(u.email),
        phone: (u.phone as string | null) ?? null, username: (u.username as string | null) ?? null,
        role: u.role === 'admin' ? 'admin' : 'customer',
      } satisfies SessionUser;
    }
    context.locals.flash = takeFlash(context.cookies);

    const user = context.locals.user;
    const needsLogin = pathname.startsWith('/account') || pathname.startsWith('/admin');
    if (needsLogin && !user) {
      setFlash(context.cookies, 'info', 'Please sign in to continue.');
      return context.redirect(`/auth/login?next=${encodeURIComponent(pathname + context.url.search)}`);
    }
    if (pathname.startsWith('/admin') && user?.role !== 'admin') {
      return context.rewrite('/403');
    }
  }

  // Re-wrap so headers are mutable (redirect responses can have immutable headers).
  const original = await next();
  const response = new Response(original.body, original);
  for (const [name, value] of Object.entries(SECURITY_HEADERS)) {
    if (!response.headers.has(name)) response.headers.set(name, value);
  }
  // Signed-in and form pages must never be cached by a shared cache.
  if (context.locals.user && !response.headers.has('Cache-Control')) response.headers.set('Cache-Control', 'private, no-store');
  return response;
});
