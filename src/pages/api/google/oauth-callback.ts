import type { APIRoute } from 'astro';
import { safeEqual } from '~/lib/access';
import { setFlash } from '~/lib/flash';
import { connectGoogle, GoogleError } from '~/lib/google';

/** Google sends the admin back here after consent. Admin-only, and the state must match the one we issued. */
export const GET: APIRoute = async ({ url, cookies, locals, redirect }) => {
  if (locals.user?.role !== 'admin') return new Response('Not found', { status: 404 });
  const state = cookies.get('yf_google_state')?.value ?? '';
  cookies.delete('yf_google_state', { path: '/api/google' });
  const code = url.searchParams.get('code');
  if (!state || !safeEqual(state, url.searchParams.get('state') ?? '') || !code) {
    setFlash(cookies, 'error', url.searchParams.get('error') === 'access_denied' ? 'Google access was not granted.' : 'The Google sign-in could not be verified. Please try again.');
    return redirect('/admin/settings/google', 303);
  }
  try {
    await connectGoogle(code);
    setFlash(cookies, 'success', 'Google connected. Paid Google Meet classes will now get Meet links automatically.');
  } catch (error) {
    if (!(error instanceof GoogleError)) throw error;
    console.error('Google connect failed', error.message);
    setFlash(cookies, 'error', error.message);
  }
  return redirect('/admin/settings/google', 303);
};
