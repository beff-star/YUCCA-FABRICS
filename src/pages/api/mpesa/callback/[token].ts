import { env } from 'cloudflare:workers';
import type { APIRoute } from 'astro';
import { safeEqual } from '~/lib/access';
import { parseStkCallback } from '~/lib/mpesa';
import { handleStkCallback } from '~/lib/payments';

const accepted = () => Response.json({ ResultCode: 0, ResultDesc: 'Accepted' });

/**
 * Daraja posts STK results here. The secret path token keeps strangers out (Daraja does not sign callbacks);
 * the payment itself is only marked paid after STK Query confirms it (see lib/payments.ts).
 */
export const POST: APIRoute = async ({ params, request, locals }) => {
  if (!env.MPESA_CALLBACK_TOKEN || !safeEqual(params.token ?? '', env.MPESA_CALLBACK_TOKEN)) {
    return new Response('Not found', { status: 404 });
  }
  const callback = parseStkCallback(await request.json().catch(() => null));
  if (!callback) return Response.json({ ResultCode: 1, ResultDesc: 'Malformed callback' }, { status: 400 });
  // Acknowledge quickly; confirmation with Daraja and emails continue in the background.
  locals.cfContext.waitUntil(handleStkCallback(callback, (p) => locals.cfContext.waitUntil(p)).catch((error) => {
    console.error('M-Pesa callback processing failed', { checkoutRequestId: callback.checkoutRequestId, error: String(error) });
  }));
  return accepted();
};
