// Safaricom Daraja (M-Pesa Express / STK push), ported from payment/mpesa.php. Plain fetch, no SDK.
// Sandbox unless MPESA_ENV is "production". Keys stay in Worker secrets.
import { env } from 'cloudflare:workers';
import { EAT_OFFSET_MS } from '~/lib/format';

const host = () => (env.MPESA_ENV === 'production' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke');

export class MpesaError extends Error {}

export const mpesaConfigured = () =>
  Boolean(env.MPESA_CONSUMER_KEY && env.MPESA_CONSUMER_SECRET && env.MPESA_SHORTCODE && env.MPESA_PASSKEY && env.MPESA_CALLBACK_TOKEN);

/** 0712 345 678 / +254 712… / 712345678 → 254712345678; null if it isn't a Safaricom-style mobile number. */
export function normalisePhone(input: string): string | null {
  let phone = input.replace(/[\s()+-]/g, '');
  if (phone.startsWith('0')) phone = `254${phone.slice(1)}`;
  else if (/^[71]\d{8}$/.test(phone)) phone = `254${phone}`;
  return /^254[71]\d{8}$/.test(phone) ? phone : null;
}

/** YYYYMMDDHHmmss in Nairobi time, as Daraja expects. */
export const mpesaTimestamp = (now = Date.now()) => new Date(now + EAT_OFFSET_MS).toISOString().replace(/\D/g, '').slice(0, 14);

/** base64(shortcode + passkey + timestamp) */
export const mpesaPassword = (shortcode: string, passkey: string, timestamp: string) => btoa(`${shortcode}${passkey}${timestamp}`);

async function call<T>(path: string, init: RequestInit): Promise<T> {
  let response: Response;
  try {
    response = await fetch(`${host()}${path}`, { ...init, signal: AbortSignal.timeout(20_000) });
  } catch (error) {
    throw new MpesaError(`M-Pesa could not be reached (${String(error)})`);
  }
  const body = await response.json().catch(() => null) as T & { errorMessage?: string } | null;
  if (!response.ok || !body) throw new MpesaError(`M-Pesa error ${response.status}: ${body?.errorMessage ?? 'no details'}`);
  return body;
}

async function accessToken(): Promise<string> {
  const basic = btoa(`${env.MPESA_CONSUMER_KEY}:${env.MPESA_CONSUMER_SECRET}`);
  const body = await call<{ access_token?: string }>('/oauth/v1/generate?grant_type=client_credentials', { headers: { Authorization: `Basic ${basic}` } });
  if (!body.access_token) throw new MpesaError('M-Pesa did not return an access token');
  return body.access_token;
}

function credentials() {
  const timestamp = mpesaTimestamp();
  return { BusinessShortCode: env.MPESA_SHORTCODE, Password: mpesaPassword(env.MPESA_SHORTCODE, env.MPESA_PASSKEY, timestamp), Timestamp: timestamp };
}

export function callbackUrl() {
  return new URL(`/api/mpesa/callback/${env.MPESA_CALLBACK_TOKEN}`, env.SITE_URL).toString();
}

/** Sends the "enter your M-Pesa PIN" prompt to the customer's phone. */
export async function stkPush(input: { phone: string; amountCents: number; accountReference: string; description: string }) {
  const token = await accessToken();
  const body = await call<{ ResponseCode?: string; CheckoutRequestID?: string; MerchantRequestID?: string; ResponseDescription?: string }>(
    '/mpesa/stkpush/v1/processrequest',
    {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ...credentials(),
        TransactionType: 'CustomerPayBillOnline',
        Amount: Math.ceil(input.amountCents / 100), // M-Pesa takes whole shillings
        PartyA: input.phone,
        PartyB: env.MPESA_SHORTCODE,
        PhoneNumber: input.phone,
        CallBackURL: callbackUrl(),
        AccountReference: input.accountReference.slice(0, 12),
        TransactionDesc: input.description.slice(0, 13),
      }),
    },
  );
  if (body.ResponseCode !== '0' || !body.CheckoutRequestID) throw new MpesaError(`STK push refused: ${body.ResponseDescription ?? 'unknown reason'}`);
  return { checkoutRequestId: body.CheckoutRequestID, merchantRequestId: body.MerchantRequestID ?? null };
}

/** Asks Daraja for the final result of an STK push. resultCode "0" means paid. */
export async function stkQuery(checkoutRequestId: string): Promise<{ resultCode: string | null; resultDesc: string }> {
  const token = await accessToken();
  const body = await call<{ ResultCode?: string | number; ResultDesc?: string }>('/mpesa/stkpushquery/v1/query', {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
    body: JSON.stringify({ ...credentials(), CheckoutRequestID: checkoutRequestId }),
  });
  return { resultCode: body.ResultCode === undefined ? null : String(body.ResultCode), resultDesc: body.ResultDesc ?? '' };
}

export interface StkCallback {
  checkoutRequestId: string;
  merchantRequestId: string | null;
  resultCode: number;
  resultDesc: string;
  amount: number | null;
  receipt: string | null;
  phone: string | null;
}

/** Parses Daraja's callback body; null when it isn't a well-formed STK callback. */
export function parseStkCallback(body: unknown): StkCallback | null {
  const cb = (body as { Body?: { stkCallback?: Record<string, unknown> } })?.Body?.stkCallback;
  if (!cb || typeof cb.CheckoutRequestID !== 'string' || cb.ResultCode === undefined) return null;
  const items = ((cb.CallbackMetadata as { Item?: { Name: string; Value?: unknown }[] })?.Item ?? []);
  const meta = Object.fromEntries(items.map((item) => [item.Name, item.Value]));
  return {
    checkoutRequestId: cb.CheckoutRequestID,
    merchantRequestId: typeof cb.MerchantRequestID === 'string' ? cb.MerchantRequestID : null,
    resultCode: Number(cb.ResultCode),
    resultDesc: String(cb.ResultDesc ?? '').slice(0, 255),
    amount: meta.Amount === undefined ? null : Number(meta.Amount),
    receipt: meta.MpesaReceiptNumber === undefined ? null : String(meta.MpesaReceiptNumber),
    phone: meta.PhoneNumber === undefined ? null : String(meta.PhoneNumber),
  };
}
