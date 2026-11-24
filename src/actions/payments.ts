import { ActionError, defineAction } from 'astro:actions';
import { z } from 'astro/zod';
import { and, count, desc, eq } from 'drizzle-orm';
import { canAccess } from '~/lib/access';
import { constraintFailed, db, schema } from '~/lib/db';
import { mpesaConfigured, MpesaError, normalisePhone } from '~/lib/mpesa';
import { confirmWithDaraja, requestMpesaPayment } from '~/lib/payments';
import { allow } from '~/lib/throttle';

const reference = z.string().trim().regex(/^Y[FT][0-9A-Z]{10}$/, 'Unknown reference.');
type Context = Parameters<Parameters<typeof defineAction>[0]['handler']>[1];

/** Finds the order (YF…) or class booking (YT…) behind a reference, checks access, and returns its latest payment. */
async function payableFor(ref: string, context: Context) {
  const notFound = () => new ActionError({ code: 'NOT_FOUND', message: 'We couldn’t find that order or booking.' });
  if (ref.startsWith('YF')) {
    const [order] = await db.select().from(schema.orders).where(eq(schema.orders.orderNumber, ref));
    if (!order || !(await canAccess({ reference: ref, userId: order.userId, accessTokenHash: order.accessTokenHash }, context.locals, context.cookies))) throw notFound();
    if (order.status === 'cancelled') throw new ActionError({ code: 'BAD_REQUEST', message: 'This order was cancelled.' });
    const [payment] = await db.select().from(schema.payments).where(eq(schema.payments.orderId, order.id)).orderBy(desc(schema.payments.id)).limit(1);
    if (!payment) throw notFound();
    return { payment, description: 'Yucca order' };
  }
  const [booking] = await db.select().from(schema.trainingBookings).where(eq(schema.trainingBookings.reference, ref));
  if (!booking || !(await canAccess({ reference: ref, userId: booking.userId, accessTokenHash: booking.accessTokenHash }, context.locals, context.cookies))) throw notFound();
  if (booking.status === 'cancelled') throw new ActionError({ code: 'BAD_REQUEST', message: 'This booking was cancelled.' });
  const [payment] = await db.select().from(schema.payments).where(eq(schema.payments.trainingBookingId, booking.id)).orderBy(desc(schema.payments.id)).limit(1);
  if (!payment) throw notFound();
  if (payment.status !== 'paid') {
    // An unpaid booking can only be paid while it still holds its time.
    const [held] = await db.select({ n: count() }).from(schema.trainingBookingHours).where(eq(schema.trainingBookingHours.bookingId, booking.id));
    if ((held?.n ?? 0) !== booking.hours) throw new ActionError({ code: 'CONFLICT', message: 'This time has been released because payment wasn’t completed in time. Please book a new slot.' });
  }
  return { payment, description: 'Yucca class' };
}

export const payments = {
  mpesaPay: defineAction({
    accept: 'form',
    input: z.object({ reference, phone: z.string().trim().max(20) }),
    async handler(input, context) {
      if (!mpesaConfigured()) throw new ActionError({ code: 'SERVICE_UNAVAILABLE', message: 'M-Pesa payments are not available right now. Please contact us to pay another way.' });
      const phone = normalisePhone(input.phone);
      if (!phone) throw new ActionError({ code: 'BAD_REQUEST', message: 'Enter a Safaricom number, for example 0712 345 678.' });
      if (!(await allow(`mpesa:${input.reference}`, 5, 10 * 60))) throw new ActionError({ code: 'TOO_MANY_REQUESTS', message: 'Too many payment requests. Please wait a few minutes and try again.' });
      const { payment, description } = await payableFor(input.reference, context);
      if (payment.status === 'paid') return { message: 'This has already been paid. Thank you!', paymentStatus: 'paid' };
      try {
        await requestMpesaPayment(payment, phone, input.reference, description);
      } catch (error) {
        if (error instanceof MpesaError) {
          console.error('STK push failed', { reference: input.reference, error: error.message });
          throw new ActionError({ code: 'BAD_GATEWAY', message: 'We couldn’t reach M-Pesa just now. Please try again in a moment.' });
        }
        throw error;
      }
      return { message: 'Check your phone and enter your M-Pesa PIN to pay.', paymentStatus: 'pending' };
    },
  }),

  paymentCheck: defineAction({
    accept: 'form',
    input: z.object({ reference }),
    async handler(input, context) {
      const { payment } = await payableFor(input.reference, context);
      const status = payment.method === 'mpesa'
        ? await confirmWithDaraja(payment, null, (p) => context.locals.cfContext.waitUntil(p))
        : payment.status;
      const message = {
        paid: 'Payment received. Thank you!',
        failed: 'The payment didn’t go through. You can try again.',
        pending: 'We haven’t received confirmation yet. If you’ve just paid, give it a minute.',
        refunded: 'This payment was refunded.',
      }[status];
      return { message, paymentStatus: status };
    },
  }),

  bankReference: defineAction({
    accept: 'form',
    input: z.object({ reference, transfer_reference: z.string().trim().toUpperCase().regex(/^[A-Z0-9-]{4,80}$/, 'Enter the reference exactly as it appears on your bank confirmation.') }),
    async handler(input, context) {
      const { payment } = await payableFor(input.reference, context);
      if (payment.method !== 'bank_transfer' || payment.status === 'paid') throw new ActionError({ code: 'BAD_REQUEST', message: 'This order isn’t awaiting a bank transfer.' });
      try {
        await db.update(schema.payments).set({ reference: input.transfer_reference, status: 'pending' })
          .where(and(eq(schema.payments.id, payment.id), eq(schema.payments.method, 'bank_transfer')));
      } catch (error) {
        if (constraintFailed(error, 'UNIQUE')) throw new ActionError({ code: 'CONFLICT', message: 'That reference has already been used. Please check it and try again.' });
        throw error;
      }
      return { message: 'Thank you. We’ll confirm your payment once it reaches our account.' };
    },
  }),
};
