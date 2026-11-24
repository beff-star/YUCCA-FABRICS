import { and, eq, inArray, ne, sql } from 'drizzle-orm';
import { db, schema } from '~/lib/db';
import { emailBody, sendEmail } from '~/lib/email';
import { formatMoney } from '~/lib/money';
import { MpesaError, stkPush, stkQuery, type StkCallback } from '~/lib/mpesa';

type Payment = typeof schema.payments.$inferSelect;
type WaitUntil = (promise: Promise<unknown>) => void;

// Daraja result codes that mean the customer did not pay (cancelled, timed out, insufficient funds, etc.).
const DEFINITE_FAILURES = new Set(['1', '1032', '1037', '2001', '1001', '1019', '1025', '9999']);

export const paymentById = async (id: number) => (await db.select().from(schema.payments).where(eq(schema.payments.id, id)))[0] ?? null;

async function mergePayload(payment: Payment, patch: Record<string, unknown>) {
  await db.update(schema.payments).set({ providerPayload: { ...(payment.providerPayload ?? {}), ...patch } }).where(eq(schema.payments.id, payment.id));
}

/** Sends the STK prompt and records its CheckoutRequestID on the payment (a retry replaces the previous one). */
export async function requestMpesaPayment(payment: Payment, phone: string, accountReference: string, description: string) {
  if (payment.method !== 'mpesa' || payment.status === 'paid' || payment.status === 'refunded') {
    throw new MpesaError('This payment can no longer be made by M-Pesa.');
  }
  const result = await stkPush({ phone, amountCents: payment.amountCents, accountReference, description });
  await db.update(schema.payments).set({
    status: 'pending',
    checkoutRequestId: result.checkoutRequestId,
    providerPayload: { ...(payment.providerPayload ?? {}), phone, merchantRequestId: result.merchantRequestId, requestedAt: new Date().toISOString() },
  }).where(eq(schema.payments.id, payment.id));
  if (payment.orderId) {
    await db.update(schema.orders).set({ paymentStatus: 'pending' }).where(and(eq(schema.orders.id, payment.orderId), eq(schema.orders.paymentStatus, 'failed')));
  }
  return result;
}

/** Marks a payment paid exactly once, then updates the order or class booking it belongs to. */
export async function markPaid(payment: Payment, receipt: string | null, waitUntil: WaitUntil) {
  const [updated] = await db.update(schema.payments)
    .set({ status: 'paid', reference: receipt ?? payment.reference })
    .where(and(eq(schema.payments.id, payment.id), ne(schema.payments.status, 'paid')))
    .returning();
  if (!updated) return; // already settled (callback and "check payment" can race)

  if (updated.orderId) {
    const [order] = await db.update(schema.orders)
      .set({ paymentStatus: 'paid', status: sql`case when ${schema.orders.status} = 'pending' then 'confirmed' else ${schema.orders.status} end` })
      .where(eq(schema.orders.id, updated.orderId)).returning();
    if (order) {
      waitUntil(sendEmail({
        to: order.email,
        subject: `Payment received for order ${order.orderNumber}`,
        text: emailBody(`Hello ${order.customerName},`, `We’ve received your payment of ${formatMoney(order.totalCents)}${receipt ? ` (M-Pesa receipt ${receipt})` : ''} for order ${order.orderNumber}. We’ll be in touch about delivery.`),
      }));
    }
  }
  if (updated.trainingBookingId) {
    const { onTrainingPaid } = await import('~/lib/training');
    await onTrainingPaid(updated.trainingBookingId, waitUntil);
  }
}

async function markFailed(payment: Payment, reason: string) {
  await db.batch([
    db.update(schema.payments).set({ status: 'failed', providerPayload: { ...(payment.providerPayload ?? {}), failure: reason } })
      .where(and(eq(schema.payments.id, payment.id), eq(schema.payments.status, 'pending'))),
    db.update(schema.orders).set({ paymentStatus: 'failed' })
      .where(and(eq(schema.orders.id, payment.orderId ?? -1), inArray(schema.orders.paymentStatus, ['pending', 'unpaid']))),
  ]);
}

/**
 * Handles Daraja's callback. Callbacks are not signed, so a success is only accepted when the CheckoutRequestID
 * is known, the amount matches, and Daraja's STK Query confirms it.
 */
export async function handleStkCallback(callback: StkCallback, waitUntil: WaitUntil) {
  const [payment] = await db.select().from(schema.payments).where(eq(schema.payments.checkoutRequestId, callback.checkoutRequestId));
  if (!payment) {
    console.warn('M-Pesa callback for unknown CheckoutRequestID', callback.checkoutRequestId);
    return;
  }
  if (payment.status === 'paid') return;
  await mergePayload(payment, {
    callbackResultCode: callback.resultCode, callbackDescription: callback.resultDesc, receipt: callback.receipt,
    callbackAmount: callback.amount, callbackReceivedAt: new Date().toISOString(),
  });
  if (callback.resultCode !== 0) {
    await markFailed(payment, callback.resultDesc);
    return;
  }
  const expected = Math.ceil(payment.amountCents / 100);
  if (callback.amount === null || callback.amount < expected) {
    console.error('M-Pesa amount mismatch', { paymentId: payment.id, expected, received: callback.amount });
    return; // stays pending for the team to review
  }
  await confirmWithDaraja(payment, callback.receipt, waitUntil);
}

/** Final word from Daraja: paid, failed, or still unknown (left pending). Used by the callback and "check payment". */
export async function confirmWithDaraja(payment: Payment, receipt: string | null, waitUntil: WaitUntil): Promise<'paid' | 'failed' | 'pending'> {
  if (payment.status === 'paid') return 'paid';
  if (!payment.checkoutRequestId) return 'pending';
  let result: Awaited<ReturnType<typeof stkQuery>>;
  try {
    result = await stkQuery(payment.checkoutRequestId);
  } catch (error) {
    console.error('M-Pesa STK query failed', { paymentId: payment.id, error: String(error) });
    return 'pending';
  }
  if (result.resultCode === '0') {
    const fresh = (await paymentById(payment.id)) ?? payment;
    await markPaid(fresh, receipt ?? (fresh.providerPayload?.receipt as string | null) ?? null, waitUntil);
    return 'paid';
  }
  if (result.resultCode && DEFINITE_FAILURES.has(result.resultCode)) {
    await markFailed(payment, result.resultDesc);
    return 'failed';
  }
  return 'pending';
}
