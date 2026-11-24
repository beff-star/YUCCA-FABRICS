import { ActionError, defineAction } from 'astro:actions';
import { z } from 'astro/zod';
import { optional, optionalText } from './fields';
import { and, eq } from 'drizzle-orm';
import { addToCart, CartError, cartCount, cartLines, ensureCartId, setCartCoupon, subtotalOf } from '~/lib/cart';
import { checkCoupon } from '~/lib/coupons';
import { formatMoney } from '~/lib/money';
import { db, schema } from '~/lib/db';

const productId = z.coerce.number().int().positive();

const asActionError = (error: unknown): never => {
  if (error instanceof CartError) {
    throw new ActionError({ code: error.status === 404 ? 'NOT_FOUND' : error.status === 409 ? 'CONFLICT' : 'BAD_REQUEST', message: error.message });
  }
  throw error;
};

export const shop = {
  cartAdd: defineAction({
    accept: 'form',
    input: z.object({ product_id: productId, quantity: optional(z.coerce.number().int().min(1).max(999)) }),
    async handler({ product_id, quantity }, context) {
      const product = await addToCart(context, product_id, quantity ?? 1).catch(asActionError);
      return { message: `${product.name} added to your bag.`, cartCount: await cartCount(context) };
    },
  }),

  coupon: defineAction({
    accept: 'form',
    input: z.object({ intent: z.enum(['apply', 'remove']), code: optional(z.string().trim().toUpperCase().regex(/^[A-Z0-9_-]{3,40}$/, 'That coupon code isn’t valid.')) }),
    async handler({ intent, code }, context) {
      const cartId = await ensureCartId(context);
      if (intent === 'remove' || !code) {
        await setCartCoupon(cartId, null);
        return { message: 'Coupon removed.', reload: true };
      }
      const lines = await cartLines(cartId);
      if (lines.length === 0) throw new ActionError({ code: 'BAD_REQUEST', message: 'Add something to your bag first.' });
      const check = await checkCoupon(code, subtotalOf(lines), context.locals.user?.id ?? null);
      if (!check.ok) throw new ActionError({ code: 'BAD_REQUEST', message: check.reason });
      await setCartCoupon(cartId, check.coupon.code);
      return { message: `Coupon applied: you save ${formatMoney(check.discountCents)}.`, reload: true };
    },
  }),

  /** Verified reviews only: the customer needs a delivered order containing the product. Held for moderation. */
  review: defineAction({
    accept: 'form',
    input: z.object({
      product_id: productId,
      rating: z.coerce.number().int().min(1).max(5),
      title: optionalText(150),
      review: z.string().trim().min(10, 'Please write at least 10 characters.').max(2000),
    }),
    async handler(input, { locals }) {
      if (!locals.user) throw new ActionError({ code: 'UNAUTHORIZED', message: 'Please sign in to leave a review.' });
      const [order] = await db.select({ id: schema.orders.id }).from(schema.orders)
        .innerJoin(schema.orderItems, eq(schema.orderItems.orderId, schema.orders.id))
        .where(and(eq(schema.orders.userId, locals.user.id), eq(schema.orderItems.productId, input.product_id), eq(schema.orders.status, 'delivered')))
        .limit(1);
      if (!order) throw new ActionError({ code: 'FORBIDDEN', message: 'Reviews open once an order with this fabric has been delivered.' });
      const inserted = await db.insert(schema.productReviews).values({
        productId: input.product_id, userId: locals.user.id, orderId: order.id, rating: input.rating,
        title: input.title || null, review: input.review,
      }).onConflictDoNothing().returning({ id: schema.productReviews.id });
      if (inserted.length === 0) throw new ActionError({ code: 'CONFLICT', message: 'You have already reviewed this fabric.' });
      return { message: 'Thank you! Your review will appear once it has been checked.' };
    },
  }),

  wishlist: defineAction({
    accept: 'form',
    input: z.object({ product_id: productId, intent: z.enum(['add', 'remove']) }),
    async handler({ product_id, intent }, { locals }) {
      if (!locals.user) throw new ActionError({ code: 'UNAUTHORIZED', message: 'Please sign in to save favourites.' });
      if (intent === 'remove') {
        await db.delete(schema.wishlistItems).where(and(eq(schema.wishlistItems.userId, locals.user.id), eq(schema.wishlistItems.productId, product_id)));
        return { message: 'Removed from your favourites.', removed: true };
      }
      const [product] = await db.select({ id: schema.products.id }).from(schema.products)
        .where(and(eq(schema.products.id, product_id), eq(schema.products.isActive, true)));
      if (!product) throw new ActionError({ code: 'NOT_FOUND', message: 'That fabric is no longer available.' });
      await db.insert(schema.wishlistItems).values({ userId: locals.user.id, productId: product_id }).onConflictDoNothing();
      return { message: 'Saved to your favourites.' };
    },
  }),
};
