import { and, count, eq } from 'drizzle-orm';
import { db, schema } from '~/lib/db';

type Coupon = typeof schema.coupons.$inferSelect;

/** Discount in cents: percent of the subtotal or a fixed amount, never more than the subtotal. */
export function couponDiscount(coupon: Pick<Coupon, 'discountType' | 'discountValue'>, subtotalCents: number): number {
  const raw = coupon.discountType === 'percent' ? Math.round((subtotalCents * coupon.discountValue) / 100) : coupon.discountValue;
  return Math.max(0, Math.min(raw, subtotalCents));
}

export type CouponCheck = { ok: true; coupon: Coupon; discountCents: number } | { ok: false; reason: string };

/**
 * Checks a code against the bag: active, inside its date window, minimum order met, total uses below the limit,
 * and not used before by this customer (guests are limited only by the total).
 */
export async function checkCoupon(code: string, subtotalCents: number, userId: string | null, now = Date.now()): Promise<CouponCheck> {
  const [coupon] = await db.select().from(schema.coupons).where(eq(schema.coupons.code, code.toUpperCase()));
  if (!coupon || !coupon.isActive) return { ok: false, reason: 'That coupon code isn’t valid.' };
  if (coupon.startsAt && coupon.startsAt.getTime() > now) return { ok: false, reason: 'That coupon isn’t active yet.' };
  if (coupon.expiresAt && coupon.expiresAt.getTime() < now) return { ok: false, reason: 'That coupon has expired.' };
  if (subtotalCents < coupon.minimumOrderCents) {
    return { ok: false, reason: `That coupon needs an order of at least KES ${(coupon.minimumOrderCents / 100).toLocaleString('en-KE')}.` };
  }
  const [[used], [mine]] = await db.batch([
    db.select({ n: count() }).from(schema.couponUsage).where(eq(schema.couponUsage.couponId, coupon.id)),
    db.select({ n: count() }).from(schema.couponUsage).where(and(eq(schema.couponUsage.couponId, coupon.id), eq(schema.couponUsage.userId, userId ?? ''))),
  ]);
  if (coupon.usageLimit !== null && (used?.n ?? 0) >= coupon.usageLimit) return { ok: false, reason: 'That coupon has been fully used.' };
  if (userId && (mine?.n ?? 0) > 0) return { ok: false, reason: 'You’ve already used that coupon.' };
  return { ok: true, coupon, discountCents: couponDiscount(coupon, subtotalCents) };
}
