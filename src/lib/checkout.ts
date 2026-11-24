import { and, eq, sql } from 'drizzle-orm';
import type { PAYMENT_METHODS } from '~/db/schema';
import { newAccessToken } from '~/lib/access';
import { cartCoupon, cartLines, subtotalOf, type CartLine } from '~/lib/cart';
import { checkCoupon } from '~/lib/coupons';
import { constraintFailed, db, runBatch, schema } from '~/lib/db';
import { makeReference } from '~/lib/format';
import { deliveryFeeCents, orderTotals, taxRate, type Totals } from '~/lib/pricing';

export class CheckoutError extends Error {}

export interface CheckoutDetails {
  name: string; email: string; phone: string; addressLine: string; city: string;
  county: string | null; postalCode: string | null; deliveryInstructions: string | null;
  paymentMethod: (typeof PAYMENT_METHODS)[number];
}

/** Current bag priced for checkout: fresh prices and stock, coupon re-checked. */
export async function priceCart(cartId: string | null, userId: string | null) {
  const lines = await cartLines(cartId);
  const subtotalCents = subtotalOf(lines);
  const code = await cartCoupon(cartId);
  const coupon = code ? await checkCoupon(code, subtotalCents, userId) : null;
  const discountCents = coupon?.ok ? coupon.discountCents : 0;
  return { lines, code, coupon, totals: orderTotals(subtotalCents, discountCents, taxRate(), deliveryFeeCents()) };
}

/**
 * Places the order in one atomic D1 batch: order, items, stock decrements, payment row, coupon usage and an
 * emptied bag. The `stock >= 0` CHECK makes an oversell abort the whole batch, and the per-customer coupon
 * UNIQUE index stops a coupon being used twice in parallel.
 */
export async function placeOrder(cartId: string, userId: string | null, details: CheckoutDetails) {
  const { lines, code, coupon, totals } = await priceCart(cartId, userId);
  if (lines.length === 0) throw new CheckoutError('Your bag is empty.');
  // cartLines() lowers quantities to the stock on hand; never place a smaller order than the customer confirmed.
  const requested = await db.select().from(schema.cartItems).where(eq(schema.cartItems.cartId, cartId));
  const changed = requested.filter((item) => lines.find((line) => line.productId === item.productId)?.quantity !== item.quantity);
  if (changed.length > 0) {
    await runBatch(changed.map((item) => {
      const line = lines.find((l) => l.productId === item.productId);
      return line
        ? db.update(schema.cartItems).set({ quantity: line.quantity }).where(and(eq(schema.cartItems.cartId, cartId), eq(schema.cartItems.productId, item.productId)))
        : db.delete(schema.cartItems).where(and(eq(schema.cartItems.cartId, cartId), eq(schema.cartItems.productId, item.productId)));
    }));
    throw new CheckoutError(await soldOutMessage(lines, requested));
  }
  if (code && !coupon?.ok) throw new CheckoutError(`${coupon?.ok === false ? coupon.reason : 'Your coupon is no longer valid.'} Please remove it from your bag to continue.`);

  const orderNumber = makeReference('YF');
  const access = await newAccessToken();
  const orderId = sql`(select ${schema.orders.id} from ${schema.orders} where ${schema.orders.orderNumber} = ${orderNumber})`;
  try {
    await runBatch([
      db.insert(schema.orders).values({
        userId, orderNumber, accessTokenHash: access.hash,
        customerName: details.name, email: details.email, phone: details.phone, addressLine: details.addressLine, city: details.city,
        county: details.county, postalCode: details.postalCode, deliveryInstructions: details.deliveryInstructions,
        ...totals, paymentMethod: details.paymentMethod, status: 'pending', paymentStatus: details.paymentMethod === 'cod' ? 'unpaid' : 'pending',
      }),
      ...lines.map((line) => db.insert(schema.orderItems).values({
        orderId, productId: line.productId, productName: line.name, sku: line.sku,
        unitPriceCents: line.unitPriceCents, quantity: line.quantity, lineTotalCents: line.lineTotalCents,
      })),
      ...lines.map((line) => db.update(schema.products).set({ stock: sql`${schema.products.stock} - ${line.quantity}` }).where(eq(schema.products.id, line.productId))),
      db.insert(schema.payments).values({ orderId, method: details.paymentMethod, amountCents: totals.totalCents }),
      ...(coupon?.ok ? [db.insert(schema.couponUsage).values({ couponId: coupon.coupon.id, orderId, userId })] : []),
      db.delete(schema.cartItems).where(eq(schema.cartItems.cartId, cartId)),
      db.update(schema.carts).set({ couponCode: null }).where(eq(schema.carts.id, cartId)),
    ]);
  } catch (error) {
    if (constraintFailed(error, 'CHECK')) throw new CheckoutError(await soldOutMessage(lines, lines));
    if (constraintFailed(error, 'UNIQUE') && coupon?.ok) throw new CheckoutError('You’ve already used that coupon. Please remove it from your bag to continue.');
    throw error;
  }
  return { orderNumber, accessToken: access.token, totals, lines };
}

/** Names the fabric(s) whose stock is now below what the customer asked for. */
async function soldOutMessage(lines: CartLine[], wanted: { productId: number; quantity: number }[]) {
  const short: string[] = [];
  for (const item of wanted) {
    const [row] = await db.select({ name: schema.products.name, stock: schema.products.stock, active: schema.products.isActive })
      .from(schema.products).where(eq(schema.products.id, item.productId));
    const name = row?.name ?? lines.find((line) => line.productId === item.productId)?.name ?? 'A fabric';
    if (!row || !row.active || row.stock < item.quantity) short.push(row?.active && row.stock > 0 ? `${name} (only ${row.stock} left)` : `${name} (sold out)`);
  }
  return short.length
    ? `Sorry, stock changed while you were checking out: ${short.join(', ')}. Your bag has been updated.`
    : 'Sorry, stock changed while you were checking out. Please review your bag.';
}

export type { Totals };
