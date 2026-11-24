import type { APIRoute } from 'astro';
import { desc, eq } from 'drizzle-orm';
import { canAccess } from '~/lib/access';
import { db, schema } from '~/lib/db';

/** Payment status for the order or class page to poll while the customer approves the M-Pesa prompt. */
export const GET: APIRoute = async ({ params, locals, cookies }) => {
  const ref = params.reference ?? '';
  const notFound = () => Response.json({ error: 'Not found' }, { status: 404 });
  let paymentStatus: string | undefined;
  if (/^YF[0-9A-Z]{10}$/.test(ref)) {
    const [order] = await db.select().from(schema.orders).where(eq(schema.orders.orderNumber, ref));
    if (!order || !(await canAccess({ reference: ref, userId: order.userId, accessTokenHash: order.accessTokenHash }, locals, cookies))) return notFound();
    paymentStatus = order.paymentStatus;
  } else if (/^YT[0-9A-Z]{10}$/.test(ref)) {
    const [booking] = await db.select().from(schema.trainingBookings).where(eq(schema.trainingBookings.reference, ref));
    if (!booking || !(await canAccess({ reference: ref, userId: booking.userId, accessTokenHash: booking.accessTokenHash }, locals, cookies))) return notFound();
    const [payment] = await db.select({ status: schema.payments.status }).from(schema.payments)
      .where(eq(schema.payments.trainingBookingId, booking.id)).orderBy(desc(schema.payments.id)).limit(1);
    paymentStatus = payment?.status;
  } else {
    return notFound();
  }
  return Response.json({ paymentStatus }, { headers: { 'Cache-Control': 'no-store' } });
};
